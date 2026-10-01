<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\RegionResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function update(Request $request, RegionResolver $resolver)
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user instanceof User, 401);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'negara' => 'nullable|string|max:100',
            'negara_kode' => 'nullable|string|size:2',
            'provinsi' => 'nullable|string|max:100',
            'provinsi_id' => 'nullable|string|max:20',
            'kota' => 'nullable|string|max:100',
            'kota_id' => 'nullable|string|max:20',
            'kecamatan' => 'nullable|string|max:100',
            'kecamatan_id' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'negara.required' => 'Negara wajib diisi.',
            'avatar.image' => 'File avatar harus berupa gambar.',
            'avatar.mimes' => 'Format avatar harus jpg, jpeg, png, atau webp.',
            'avatar.max' => 'Ukuran avatar maksimal 2 MB.',
        ]);

        // Fallback wilayah lama dilakukan mode-aware: bila form mengganti mode
        // (Indonesia ↔ luar negeri), sisi lama tidak diikutkan — cegah data
        // campuran seperti "Germany + kecamatan Coblong".
        $region = $resolver->resolveForUpdate(
            $request->only([
                'negara', 'negara_kode', 'provinsi', 'provinsi_id',
                'kota', 'kota_id', 'kecamatan', 'kecamatan_id',
            ]),
            $user->only([
                'negara', 'negara_kode', 'provinsi', 'provinsi_id',
                'kota', 'kota_id', 'kecamatan', 'kecamatan_id',
            ]),
        );

        if ($region['errors'] !== []) {
            throw ValidationException::withMessages($region['errors']);
        }

        if (filled($region['negara_kode']) && $region['negara_kode'] !== 'ID') {
            $labels = $resolver->fillForeignLabels($region);
            $region['negara'] = $region['negara'] ?? $labels['negara'];
            $region['provinsi'] = $region['provinsi'] ?? $labels['provinsi'];
            $region['kota'] = $region['kota'] ?? $labels['kota'];
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update([
            'name' => $validated['name'],
            'negara' => $region['negara'],
            'negara_kode' => $region['negara_kode'],
            'provinsi' => $region['provinsi'],
            'provinsi_id' => $region['provinsi_id'],
            'kota' => $region['kota'],
            'kota_id' => $region['kota_id'],
            'kecamatan' => $region['kecamatan'],
            'kecamatan_id' => $region['kecamatan_id'],
            'avatar' => $validated['avatar'] ?? $user->avatar,
        ]);

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user' => $this->userPayload($user->fresh()),
        ]);
    }

    public function changePassword(Request $request)
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user instanceof User, 401);

        $validated = $request->validate([
            'current_password' => 'nullable|string',
            'password' => [
                'required',
                'string',
                'min:12',
                'regex:/[A-Za-z]/',
                'regex:/\d/',
                'not_regex:/\s/',
                'confirmed',
            ],
        ], [
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 12 karakter.',
            'password.regex' => 'Password baru harus mengandung huruf dan angka.',
            'password.not_regex' => 'Password baru tidak boleh mengandung spasi.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        if ($user->password && ! Hash::check($validated['current_password'] ?? '', $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Password saat ini tidak benar.',
            ]);
        }

        $user->forceFill([
            'password' => $validated['password'],
        ])->save();

        // Putuskan token API lama agar password baru langsung berlaku di semua sesi API.
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Password berhasil diubah. Silakan login kembali.',
        ]);
    }

    public function destroy(Request $request)
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user instanceof User, 401);

        $validated = $request->validate([
            'current_password' => 'nullable|string',
            'confirmation' => 'required|in:HAPUS AKUN',
        ], [
            'confirmation.required' => 'Ketik HAPUS AKUN untuk melanjutkan.',
            'confirmation.in' => 'Konfirmasi penghapusan akun tidak sesuai.',
        ]);

        if ($user->password && ! Hash::check($validated['current_password'] ?? '', $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Password saat ini tidak benar.',
            ]);
        }

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->tokens()->delete();
        $user->delete();

        // Guard sanctum (RequestGuard) tidak memiliki metode logout —
        // sesi hanya dapat diakhiri lewat guard web (session).
        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'message' => 'Akun berhasil dihapus.',
            'redirect' => '/login',
        ]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'negara' => $user->negara,
            'negara_kode' => $user->negara_kode,
            'provinsi' => $user->provinsi,
            'provinsi_id' => $user->provinsi_id,
            'kota' => $user->kota,
            'kota_id' => $user->kota_id,
            'kecamatan' => $user->kecamatan,
            'kecamatan_id' => $user->kecamatan_id,
            'avatar_url' => $user->avatarUrl(),
        ];
    }
}
