<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        return view('profile', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'negara' => 'required|string|max:100',
                'provinsi' => 'nullable|string|max:100',
                'kota' => 'nullable|string|max:100',
                'kecamatan' => 'nullable|string|max:100',
                'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            ], [
                'name.required' => 'Nama lengkap dan negara wajib diisi.',
                'negara.required' => 'Nama lengkap dan negara wajib diisi.',
                'avatar.image' => 'File avatar harus berupa gambar.',
                'avatar.mimes' => 'Format avatar harus jpg, jpeg, png, atau webp.',
                'avatar.max' => 'Ukuran avatar maksimal 2 MB.',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update([
            'name' => $validated['name'],
            'negara' => $validated['negara'],
            'provinsi' => $validated['provinsi'] ?? null,
            'kota' => $validated['kota'] ?? null,
            'kecamatan' => $validated['kecamatan'] ?? null,
            'avatar' => $validated['avatar'] ?? $user->avatar,
        ]);

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'negara' => $user->negara,
                'provinsi' => $user->provinsi,
                'kota' => $user->kota,
                'kecamatan' => $user->kecamatan,
                'avatar_url' => $user->avatarUrl(),
            ],
        ]);
    }
}
