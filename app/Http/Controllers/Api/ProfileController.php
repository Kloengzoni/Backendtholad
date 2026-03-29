<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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
            'first_name'   => 'sometimes|nullable|string|max:100',   // FIX: accepter first_name
            'last_name'    => 'sometimes|nullable|string|max:100',   // FIX: accepter last_name
            'email'        => 'sometimes|nullable|email|unique:users,email,' . $user->id,
            'phone'        => 'sometimes|string|unique:users,phone,' . $user->id,
            'country_code' => 'sometimes|string',
            'country'      => 'sometimes|string',
        ]);

        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }

        // FIX: Reconstruire 'name' depuis first_name + last_name si fournis
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

        // Supprimer l'ancien avatar
        if ($user->avatar && !str_starts_with($user->avatar, 'http')) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        // FIX: retourner 'avatar_url' (URL complète) pour que Flutter puisse l'utiliser
        return response()->json([
            'success'    => true,
            'avatar_url' => $user->avatar_url,  // Accessor défini dans le modèle User
            'data'       => $this->userResource($user),
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
        // FIX: retourner first_name et last_name séparément
        $nameParts  = explode(' ', trim($user->name ?? ''));
        $firstName  = $nameParts[0] ?? '';
        $lastName   = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';

        return [
            'id'           => $user->id,
            'name'         => $user->name,
            'first_name'   => $firstName,       // FIX: champ ajouté
            'last_name'    => $lastName,        // FIX: champ ajouté
            'email'        => $user->email,
            'phone'        => $user->phone,
            'country_code' => $user->country_code,
            'country'      => $user->country,
            'avatar_url'   => $user->avatar_url,  // FIX: URL complète
            'avatar'       => $user->avatar,
            'role'         => $user->role,
            'is_verified'  => $user->is_verified,
            'is_active'    => $user->is_active,
            'created_at'   => $user->created_at,
        ];
    }
}
