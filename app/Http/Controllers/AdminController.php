<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\RegionResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()->latest();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $like = "%{$search}%";

                $q->where('id', 'like', $like)
                    ->orWhere('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('role', 'like', $like)
                    ->orWhere('negara', 'like', $like)
                    ->orWhere('provinsi', 'like', $like)
                    ->orWhere('kota', 'like', $like)
                    ->orWhere('kecamatan', 'like', $like)
                    ->orWhereDate('created_at', $search);
            });
        }

        if ($role = $request->string('role')->trim()->toString()) {
            if (in_array($role, ['admin', 'user'], true)) {
                $query->where('role', $role);
            }
        }

        $statsQuery = clone $query;
        $filteredStats = [
            'total' => (clone $statsQuery)->count(),
            'admin' => (clone $statsQuery)->where('role', 'admin')->count(),
            'user' => (clone $statsQuery)->where('role', 'user')->count(),
        ];

        $perPage = (int) $request->input('per_page', 5);
        $perPage = in_array($perPage, [5, 10, 25, 50], true) ? $perPage : 5;

        $users = $query->paginate($perPage)->withQueryString();

        $users->getCollection()->transform(function (User $user) {
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
                'avatar' => $user->avatar,
                'avatar_url' => $user->avatarUrl(),
                'created_at' => optional($user->created_at)->toISOString(),
            ];
        });

        return response()->json(array_merge(
            $users->toArray(),
            ['stats' => $filteredStats]
        ));
    }

    public function store(Request $request, RegionResolver $resolver)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => [
                    'required',
                    'string',
                    'min:12',
                    'regex:/[A-Za-z]/',
                    'regex:/\d/',
                    'not_regex:/\s/',
                    'confirmed',
                ],
                'role' => ['required', Rule::in(['admin', 'user'])],
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
                'email.required' => 'Alamat email wajib diisi dengan format yang benar.',
                'email.email' => 'Alamat email wajib diisi dengan format yang benar.',
                'email.unique' => 'Email ini sudah terdaftar, silakan gunakan email lain.',
                'password.required' => 'Kata sandi wajib diisi.',
                'password.min' => 'Kata sandi minimal harus 12 karakter.',
                'password.regex' => 'Kata sandi harus mengandung kombinasi huruf dan angka.',
                'password.not_regex' => 'Kata sandi tidak boleh mengandung spasi.',
                'password.confirmed' => 'Konfirmasi kata sandi tidak cocok. Silakan periksa kembali.',
                'role.required' => 'Role wajib dipilih.',
                'role.in' => 'Role harus admin atau user.',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        }

        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $region = $resolver->resolve(
            $validated['negara_kode'] ?? null,
            $validated['provinsi_id'] ?? null,
            $validated['kota_id'] ?? null,
            $validated['kecamatan_id'] ?? null,
            $validated['negara'] ?? null,
            $validated['provinsi'] ?? null,
            $validated['kota'] ?? null,
            $validated['kecamatan'] ?? null,
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

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'negara' => $region['negara'],
            'negara_kode' => $region['negara_kode'],
            'provinsi' => $region['provinsi'],
            'provinsi_id' => $region['provinsi_id'],
            'kota' => $region['kota'],
            'kota_id' => $region['kota_id'],
            'kecamatan' => $region['kecamatan'],
            'kecamatan_id' => $region['kecamatan_id'],
            'avatar' => $validated['avatar'] ?? null,
        ]);

        return response()->json([
            'message' => 'Pengguna berhasil ditambahkan.',
            'user' => $this->formatUser($user),
        ], 201);
    }

    public function show(User $user)
    {
        return response()->json([
            'user' => $this->formatUser($user),
        ]);
    }

    public function update(Request $request, User $user, RegionResolver $resolver)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($user->id),
                ],
                'password' => [
                    'nullable',
                    'string',
                    'min:12',
                    'regex:/[A-Za-z]/',
                    'regex:/\d/',
                    'not_regex:/\s/',
                    'confirmed',
                ],
                'role' => ['required', Rule::in(['admin', 'user'])],
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
                'email.required' => 'Alamat email wajib diisi dengan format yang benar.',
                'email.email' => 'Alamat email wajib diisi dengan format yang benar.',
                'email.unique' => 'Email ini sudah terdaftar, silakan gunakan email lain.',
                'password.min' => 'Kata sandi minimal harus 12 karakter.',
                'password.regex' => 'Kata sandi harus mengandung kombinasi huruf dan angka.',
                'password.not_regex' => 'Kata sandi tidak boleh mengandung spasi.',
                'password.confirmed' => 'Konfirmasi kata sandi tidak cocok. Silakan periksa kembali.',
                'role.required' => 'Role wajib dipilih.',
                'role.in' => 'Role harus admin atau user.',
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

        // Fallback ke wilayah tersimpan — mode-aware: bila form memindahkan
        // user ke mode lain (Indonesia ↔ luar negeri), sisi lama dibuang agar
        // tidak tersimpan data campuran.
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

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'negara' => $region['negara'],
            'negara_kode' => $region['negara_kode'],
            'provinsi' => $region['provinsi'],
            'provinsi_id' => $region['provinsi_id'],
            'kota' => $region['kota'],
            'kota_id' => $region['kota_id'],
            'kecamatan' => $region['kecamatan'],
            'kecamatan_id' => $region['kecamatan_id'],
        ];

        if (! empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        if (array_key_exists('avatar', $validated)) {
            $data['avatar'] = $validated['avatar'];
        }

        $user->update($data);

        return response()->json([
            'message' => 'Data pengguna berhasil diperbarui.',
            'user' => $this->formatUser($user->fresh()),
        ]);
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return response()->json([
                'message' => 'Anda tidak dapat menghapus akun yang sedang digunakan.',
            ], 422);
        }

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'message' => 'Pengguna berhasil dihapus.',
        ]);
    }

    private function formatUser(User $user): array
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
            'avatar' => $user->avatar,
            'avatar_url' => $user->avatarUrl(),
            'created_at' => optional($user->created_at)->toDateTimeString(),
        ];
    }
}
