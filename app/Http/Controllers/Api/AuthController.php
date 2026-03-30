<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * AuthController
 *
 * FIX AVATAR (Bug 1) :
 *   L'ancien userResource() retournait `'avatar_url' => $user->avatar_url`
 *   où `avatar_url` est l'accessor Eloquent qui utilise asset() → renvoie un
 *   chemin RELATIF sur Railway (/storage/avatars/...) donc Flutter ne l'affiche pas.
 *
 *   On construit maintenant l'URL absolue manuellement avec APP_URL,
 *   exactement comme ProfileController::userResource() — les deux endpoints
 *   retournent désormais le même format.
 */
class AuthController extends Controller
{
    // ────────────────────────────────────────────────────────────────────────
    //  POST /api/v1/auth/register
    // ────────────────────────────────────────────────────────────────────────
    public function register(Request $request)
    {
        $request->validate([
            'name'                  => 'nullable|string|max:191',
            'first_name'            => 'nullable|string|max:100',
            'last_name'             => 'nullable|string|max:100',
            'email'                 => 'nullable|email|unique:users,email',
            'phone'                 => 'required|string|unique:users,phone',
            'country_code'          => 'nullable|string',
            'country'               => 'nullable|string',
            'password'              => 'required|string|min:6|confirmed',
        ]);

        $firstName = trim($request->input('first_name', ''));
        $lastName  = trim($request->input('last_name',  ''));
        $name      = trim($request->input('name', "$firstName $lastName"));
        if (empty($name)) $name = $request->phone;

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user = User::create([
            'name'         => $name,
            'first_name'   => $firstName ?: null,
            'last_name'    => $lastName  ?: null,
            'email'        => $request->email ?: null,
            'phone'        => $request->phone,
            'country_code' => $request->country_code ?? 'CG',
            'country'      => $request->country      ?? 'Congo (Brazzaville)',
            'password'     => Hash::make($request->password),
            'otp_code'     => $otp,
            'otp_expires_at' => now()->addMinutes(10),
            'is_verified'  => false,
            'is_active'    => false,
            'role'         => 'client',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Compte créé. Vérifiez votre OTP.',
            'otp'     => $otp,   // mode gratuit — à supprimer en prod SMS
            'user'    => $this->userResource($user),
        ], 201);
    }

    // ────────────────────────────────────────────────────────────────────────
    //  POST /api/v1/auth/login
    // ────────────────────────────────────────────────────────────────────────
    public function login(Request $request)
    {
        $request->validate([
            'phone'    => 'nullable|string',
            'email'    => 'nullable|string',
            'password' => 'required|string',
        ]);

        $user = null;
        if ($request->filled('phone')) {
            $user = User::where('phone', $request->phone)->first();
        } elseif ($request->filled('email')) {
            $user = User::where('email', $request->email)->first();
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants incorrects.',
            ], 401);
        }

        if (!$user->is_verified) {
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $user->update([
                'otp_code'       => $otp,
                'otp_expires_at' => now()->addMinutes(10),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Compte non vérifié. Un OTP a été envoyé.',
                'otp'     => $otp,
            ], 403);
        }

        $user->update(['is_active' => true, 'last_login_at' => now()]);
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => $this->userResource($user),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    //  POST /api/v1/auth/verify-otp
    // ────────────────────────────────────────────────────────────────────────
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'otp'   => 'required|string',
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Utilisateur introuvable.'], 404);
        }

        if ($user->otp_code !== $request->otp) {
            return response()->json(['success' => false, 'message' => 'Code OTP incorrect.'], 422);
        }

        if ($user->otp_expires_at && now()->isAfter($user->otp_expires_at)) {
            return response()->json(['success' => false, 'message' => 'Code OTP expiré.'], 422);
        }

        $user->update([
            'is_verified'    => true,
            'is_active'      => true,
            'otp_code'       => null,
            'otp_expires_at' => null,
        ]);

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Compte vérifié.',
            'token'   => $token,
            'user'    => $this->userResource($user),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    //  POST /api/v1/auth/send-otp
    // ────────────────────────────────────────────────────────────────────────
    public function sendOtp(Request $request)
    {
        $request->validate(['phone' => 'required|string']);

        $user = User::where('phone', $request->phone)->first();
        if (!$user) {
            return response()->json(['success' => true, 'message' => 'OTP envoyé si le compte existe.']);
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->update(['otp_code' => $otp, 'otp_expires_at' => now()->addMinutes(10)]);

        return response()->json([
            'success' => true,
            'message' => 'OTP envoyé.',
            'otp'     => $otp,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    //  GET /api/v1/auth/me
    // ────────────────────────────────────────────────────────────────────────
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'user'    => $this->userResource($request->user()),
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    //  POST /api/v1/auth/logout
    // ────────────────────────────────────────────────────────────────────────
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true, 'message' => 'Déconnecté.']);
    }

    // ────────────────────────────────────────────────────────────────────────
    //  POST /api/v1/auth/forgot-password
    // ────────────────────────────────────────────────────────────────────────
    public function forgotPassword(Request $request)
    {
        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json(['success' => true, 'message' => 'Si ce numéro existe, un OTP a été envoyé.']);
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->update(['otp_code' => $otp, 'otp_expires_at' => now()->addMinutes(15)]);

        return response()->json([
            'success' => true,
            'message' => 'OTP de réinitialisation envoyé.',
            'otp'     => $otp,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    //  POST /api/v1/auth/reset-password
    // ────────────────────────────────────────────────────────────────────────
    public function resetPassword(Request $request)
    {
        $request->validate([
            'phone'                 => 'required|string',
            'otp'                   => 'required|string',
            'password'              => 'required|string|min:6|confirmed',
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (!$user || $user->otp_code !== $request->otp) {
            return response()->json(['success' => false, 'message' => 'OTP invalide.'], 422);
        }

        if ($user->otp_expires_at && now()->isAfter($user->otp_expires_at)) {
            return response()->json(['success' => false, 'message' => 'OTP expiré.'], 422);
        }

        $user->update([
            'password'       => Hash::make($request->password),
            'otp_code'       => null,
            'otp_expires_at' => null,
        ]);

        return response()->json(['success' => true, 'message' => 'Mot de passe réinitialisé.']);
    }

    // ────────────────────────────────────────────────────────────────────────
    //  Helper — userResource
    //  FIX BUG 1 : construit avatar_url en URL absolue (même logique que
    //              ProfileController::userResource) pour que Flutter l'affiche.
    // ────────────────────────────────────────────────────────────────────────
    private function userResource(User $user): array
    {
        $nameParts = explode(' ', trim($user->name ?? ''));
        $firstName = $user->first_name ?: ($nameParts[0] ?? '');
        $lastName  = $user->last_name  ?: (count($nameParts) > 1
            ? implode(' ', array_slice($nameParts, 1))
            : '');

        // ── FIX : construire l'URL absolue de l'avatar ──────────────────
        // L'ancien code utilisait $user->avatar_url (accessor Eloquent) qui
        // appelle asset() → retourne un chemin RELATIF sur Railway.
        // On reconstruit manuellement l'URL absolue ici.
        $avatarUrl = $user->avatar;
        if ($avatarUrl && !str_starts_with($avatarUrl, 'http')) {
            // Chemin relatif stocké en base → construire l'URL absolue
            $storagePath = Storage::disk('public')->url($avatarUrl);
            if (!str_starts_with($storagePath, 'http')) {
                $appUrl    = rtrim(config('app.url', 'https://backendtholad-production.up.railway.app'), '/');
                $avatarUrl = $appUrl . $storagePath;
            } else {
                $avatarUrl = $storagePath;
            }
        }
        // Si null → laisser null (Flutter affichera les initiales)

        return [
            'id'           => $user->id,
            'name'         => $user->name,
            'first_name'   => $firstName,
            'last_name'    => $lastName,
            'email'        => $user->email,
            'phone'        => $user->phone,
            'country_code' => $user->country_code,
            'country'      => $user->country,
            'avatar_url'   => $avatarUrl,   // ← URL absolue garantie
            'avatar'       => $user->avatar,
            'role'         => $user->role,
            'is_verified'  => $user->is_verified,
            'is_active'    => $user->is_active,
            'created_at'   => $user->created_at,
        ];
    }
}
