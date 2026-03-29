<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $v = Validator::make($request->all(), [
            'name'         => 'required|string|max:191',
            'phone'        => 'required|string|unique:users',
            'email'        => 'nullable|email|unique:users',
            'country_code' => 'required|string',
            'country'      => 'required|string',
            'password'     => 'required|string|min:6|confirmed',
        ]);

        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $user = User::create([
            'name'         => $request->name,
            'email'        => $request->email,
            'phone'        => $request->phone,
            'country_code' => $request->country_code,
            'country'      => $request->country,
            'password'     => Hash::make($request->password),
            'role'         => 'client',
            'is_active'    => false,
            'is_verified'  => false,
        ]);

        // Générer l'OTP et le retourner directement (mode gratuit — pas de SMS)
        $otp = $this->generateOtp($user);

        return response()->json([
            'success' => true,
            'message' => 'Compte créé. Vérifiez votre numéro via OTP.',
            'phone'   => $user->phone,
            'otp'     => $otp,           // ← OTP retourné directement dans la réponse
            'otp_expires_in' => 10,      // minutes
        ], 201);
    }

    public function login(Request $request)
    {
        $v = Validator::make($request->all(), [
            'phone'    => 'required|string',
            'password' => 'required|string',
        ]);

        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $user = User::where('phone', $request->phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Identifiants incorrects.'], 401);
        }

        if (!$user->is_active) {
            if (!$user->is_verified) {
                // Régénérer et retourner l'OTP pour comptes non vérifiés
                $otp = $this->generateOtp($user);
                return response()->json([
                    'success' => false,
                    'message' => 'Compte non vérifié. Vérifiez votre numéro via OTP.',
                    'otp'     => $otp,       // ← OTP retourné pour redirection vers l'écran OTP
                    'phone'   => $user->phone,
                ], 403);
            }
            return response()->json(['success' => false, 'message' => 'Compte suspendu. Contactez le support.'], 403);
        }

        $user->update(['last_login_at' => now()]);

        if ($request->device_token) {
            $user->update(['device_token' => $request->device_token]);
        }

        $token = $user->createToken('mobile_app')->plainTextToken;

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => $this->userResource($user),
        ]);
    }

    public function sendOtp(Request $request)
    {
        $v = Validator::make($request->all(), ['phone' => 'required|string']);
        if ($v->fails()) return response()->json(['success' => false, 'errors' => $v->errors()], 422);

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json(['success' => true, 'message' => 'Si ce numéro existe, un OTP a été envoyé.']);
        }

        // Générer et retourner l'OTP directement (mode gratuit)
        $otp = $this->generateOtp($user);

        return response()->json([
            'success'        => true,
            'message'        => 'Code OTP généré.',
            'otp'            => $otp,       // ← Retourné directement dans l'app
            'otp_expires_in' => 10,         // minutes
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $v = Validator::make($request->all(), [
            'phone' => 'required|string',
            'otp'   => 'required|string|size:6',
        ]);
        if ($v->fails()) return response()->json(['success' => false, 'errors' => $v->errors()], 422);

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Utilisateur introuvable.'], 404);
        }

        if ($user->otp_code !== $request->otp || now()->isAfter($user->otp_expires_at)) {
            return response()->json(['success' => false, 'message' => 'OTP invalide ou expiré.'], 422);
        }

        $user->update([
            'is_verified'    => true,
            'is_active'      => true,
            'otp_code'       => null,
            'otp_expires_at' => null,
        ]);

        $token = $user->createToken('mobile_app')->plainTextToken;

        return response()->json([
            'success' => true,
            'token'   => $token,
            'user'    => $this->userResource($user),
        ]);
    }

    public function me(Request $request)
    {
        return response()->json(['success' => true, 'user' => $this->userResource($request->user())]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true, 'message' => 'Déconnecté.']);
    }

    public function forgotPassword(Request $request)
    {
        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json(['success' => true, 'message' => 'Si ce numéro existe, un OTP a été envoyé.']);
        }

        $otp = $this->generateOtp($user);

        return response()->json([
            'success'        => true,
            'message'        => 'Code OTP généré.',
            'otp'            => $otp,
            'otp_expires_in' => 10,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $v = Validator::make($request->all(), [
            'phone'    => 'required',
            'otp'      => 'required',
            'password' => 'required|min:6|confirmed',
        ]);
        if ($v->fails()) return response()->json(['success' => false, 'errors' => $v->errors()], 422);

        $user = User::where('phone', $request->phone)->first();

        if (!$user || $user->otp_code !== $request->otp || now()->isAfter($user->otp_expires_at)) {
            return response()->json(['success' => false, 'message' => 'OTP invalide ou expiré.'], 422);
        }

        $user->update([
            'password'       => Hash::make($request->password),
            'otp_code'       => null,
            'otp_expires_at' => null,
        ]);

        return response()->json(['success' => true, 'message' => 'Mot de passe réinitialisé.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Générer l'OTP, le sauvegarder en base et le retourner
    // ─────────────────────────────────────────────────────────────────────────
    private function generateOtp(User $user): string
    {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->update([
            'otp_code'       => $otp,
            'otp_expires_at' => Carbon::now()->addMinutes(10),
        ]);
        \Log::info("OTP [{$user->phone}]: $otp");
        return $otp;
    }

    private function userResource(User $user): array
    {
        $nameParts = explode(' ', trim($user->name ?? ''));
        return [
            'id'           => $user->id,
            'name'         => $user->name,
            'first_name'   => $nameParts[0] ?? '',
            'last_name'    => count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '',
            'email'        => $user->email,
            'phone'        => $user->phone,
            'country_code' => $user->country_code,
            'country'      => $user->country,
            'avatar_url'   => $user->avatar_url,
            'role'         => $user->role,
            'is_verified'  => $user->is_verified,
            'is_active'    => $user->is_active,
            'created_at'   => $user->created_at,
        ];
    }
}
