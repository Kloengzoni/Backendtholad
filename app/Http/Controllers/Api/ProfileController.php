<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * ProfileController
 *
 * FIX AVATAR: Quand Cloudinary n'est pas configuré, on stocke en local MAIS
 *             on retourne une URL absolue complète (avec APP_URL) pour que
 *             Flutter puisse afficher l'image.
 *             Côté Flutter, le cache-buster ?v=N force le rechargement.
 *
 * SOLUTION PERMANENTE: Configurer ces variables dans Railway :
 *   CLOUDINARY_CLOUD_NAME=xxx
 *   CLOUDINARY_API_KEY=xxx
 *   CLOUDINARY_API_SECRET=xxx
 */
class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return response()->json(['success' => true, 'data' => $this->userResource($request->user())]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $v = Validator::make($request->all(), [
            'name'         => 'sometimes|string|max:191',
            'first_name'   => 'sometimes|nullable|string|max:100',
            'last_name'    => 'sometimes|nullable|string|max:100',
            'email'        => 'sometimes|nullable|email|unique:users,email,' . $user->id,
            'phone'        => 'sometimes|string|unique:users,phone,' . $user->id,
            'country_code' => 'sometimes|string',
            'country'      => 'sometimes|string',
        ]);

        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $updateData = $request->only(['email', 'phone', 'country_code', 'country']);

        if ($request->has('first_name') || $request->has('last_name')) {
            $firstName = $request->input('first_name', $user->name ? explode(' ', $user->name)[0] : '');
            $lastName  = $request->input('last_name',  $user->name ? implode(' ', array_slice(explode(' ', $user->name), 1)) : '');
            $updateData['name'] = trim("$firstName $lastName");
        } elseif ($request->has('name')) {
            $updateData['name'] = $request->name;
        }

        $user->update($updateData);

        return response()->json([
            'success' => true,
            'data'    => $this->userResource($user),
            'message' => 'Profil mis à jour.',
        ]);
    }

    public function updateAvatar(Request $request)
    {
        $request->validate(['avatar' => 'required|image|max:4096']);
        $user = $request->user();

        // ── Tentative upload Cloudinary (si configuré) ──────────────────
        $cloudName = config('services.cloudinary.cloud_name');
        $apiKey    = config('services.cloudinary.api_key');
        $apiSecret = config('services.cloudinary.api_secret');

        if ($cloudName && $apiKey && $apiSecret) {
            try {
                $file      = $request->file('avatar');
                $timestamp = time();
                $folder    = 'tholadimmo/avatars';

                // FIX SIGNATURE: Utiliser le même algorithme que CloudinaryService
                // http_build_query() encode le '/' en '%2F' → signature rejetée par Cloudinary
                // Correct : construire "key=value&key=value" avec valeurs NON encodées, puis SHA-1
                $params = ['folder' => $folder, 'timestamp' => $timestamp];
                ksort($params);
                $parts = [];
                foreach ($params as $k => $v) {
                    $parts[] = "{$k}={$v}";
                }
                $signature = sha1(implode('&', $parts) . $apiSecret);

                $response = Http::attach(
                    'file',
                    file_get_contents($file->getRealPath()),
                    $file->getClientOriginalName()
                )->post("https://api.cloudinary.com/v1_1/{$cloudName}/image/upload", [
                    'api_key'   => $apiKey,
                    'timestamp' => $timestamp,
                    'signature' => $signature,
                    'folder'    => $folder,
                ]);

                if ($response->successful()) {
                    $avatarUrl = $response->json('secure_url');
                    $user->update(['avatar' => $avatarUrl]);

                    Log::info('[Avatar] Cloudinary upload OK', ['url' => $avatarUrl, 'user' => $user->id]);

                    return response()->json([
                        'success'    => true,
                        'avatar_url' => $avatarUrl,
                        'data'       => $this->userResource($user->fresh()),
                    ]);
                }

                Log::warning('[Avatar] Cloudinary upload failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('[Avatar] Cloudinary exception: ' . $e->getMessage());
                // Fallback vers storage local
            }
        } else {
            Log::warning('[Avatar] Cloudinary non configuré — utilisation du stockage local (éphémère sur Railway). ' .
                         'Configurez CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY, CLOUDINARY_API_SECRET dans Railway.');
        }

        // ── Fallback : storage local ─────────────────────────────────────
        // ⚠️  Les fichiers locaux sont perdus au redémarrage Railway.
        // Ce fallback permet quand même à l'image d'être visible EN ATTENTE
        // que Cloudinary soit configuré. L'URL retournée est absolue pour Flutter.

        // Supprimer l'ancienne photo locale si elle existe
        if ($user->avatar && !str_starts_with($user->avatar, 'http')) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');

        // FIX: Construire l'URL absolue correcte pour que Flutter puisse afficher l'image
        // Storage::url() retourne un chemin relatif (/storage/avatars/...) → on préfixe APP_URL
        $storagePath = Storage::disk('public')->url($path);
        // S'assurer que c'est une URL absolue
        if (!str_starts_with($storagePath, 'http')) {
            $appUrl = rtrim(config('app.url', 'http://localhost'), '/');
            $storagePath = $appUrl . $storagePath;
        }

        $user->update(['avatar' => $storagePath]);

        Log::info('[Avatar] Local storage fallback', ['path' => $storagePath, 'user' => $user->id]);

        return response()->json([
            'success'    => true,
            'avatar_url' => $storagePath,
            'data'       => $this->userResource($user->fresh()),
            'warning'    => 'Photo stockée localement. Configurez Cloudinary pour un stockage permanent.',
        ]);
    }

    public function changePassword(Request $request)
    {
        $v = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password'         => 'required|string|min:6|confirmed',
        ]);

        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mot de passe actuel incorrect.',
            ], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json(['success' => true, 'message' => 'Mot de passe modifié.']);
    }

    private function userResource($user): array
    {
        $nameParts  = explode(' ', trim($user->name ?? ''));
        $firstName  = $nameParts[0] ?? '';
        $lastName   = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';

        // FIX: Garantir que avatar_url est toujours une URL absolue
        $avatarUrl = $user->avatar;
        if ($avatarUrl && !str_starts_with($avatarUrl, 'http')) {
            // C'est un chemin relatif (stockage local ancien format)
            $storagePath = Storage::disk('public')->url($avatarUrl);
            if (!str_starts_with($storagePath, 'http')) {
                $appUrl = rtrim(config('app.url', 'http://localhost'), '/');
                $avatarUrl = $appUrl . $storagePath;
            } else {
                $avatarUrl = $storagePath;
            }
        }

        return [
            'id'           => $user->id,
            'name'         => $user->name,
            'first_name'   => $firstName,
            'last_name'    => $lastName,
            'email'        => $user->email,
            'phone'        => $user->phone,
            'country_code' => $user->country_code,
            'country'      => $user->country,
            'avatar_url'   => $avatarUrl,
            'avatar'       => $user->avatar,
            'role'         => $user->role,
            'is_verified'  => $user->is_verified,
            'is_active'    => $user->is_active,
            'created_at'   => $user->created_at,
        ];
    }
}
