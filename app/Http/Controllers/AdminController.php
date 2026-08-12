<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'total' => User::count(),
            'admin' => User::where('role', 'admin')->count(),
            'user' => User::where('role', 'user')->count(),
        ];

        return view('dashboard.admin', compact('stats'));
    }

    public function index(Request $request)
    {
        $query = User::query()->latest();

        if ($search = $request->string('search')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('negara', 'like', "%{$search}%")
                    ->orWhere('kota', 'like', "%{$search}%");
            });
        }

        if ($role = $request->string('role')->trim()->toString()) {
            if (in_array($role, ['admin', 'user'], true)) {
                $query->where('role', $role);
            }
        }

        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [5, 10, 25, 50], true) ? $perPage : 10;

        $users = $query->paginate($perPage)->withQueryString();

        $users->getCollection()->transform(function (User $user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'negara' => $user->negara,
                'provinsi' => $user->provinsi,
                'kota' => $user->kota,
                'kecamatan' => $user->kecamatan,
                'avatar' => $user->avatar,
                'avatar_url' => $user->avatarUrl(),
                'created_at' => optional($user->created_at)->toDateTimeString(),
            ];
        });

        return response()->json($users);
    }

    public function store(Request $request)
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
                'provinsi' => 'nullable|string|max:100',
                'kota' => 'nullable|string|max:100',
                'kecamatan' => 'nullable|string|max:100',
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

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'negara' => $validated['negara'] ?? null,
            'provinsi' => $validated['provinsi'] ?? null,
            'kota' => $validated['kota'] ?? null,
            'kecamatan' => $validated['kecamatan'] ?? null,
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

    public function update(Request $request, User $user)
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
                'provinsi' => 'nullable|string|max:100',
                'kota' => 'nullable|string|max:100',
                'kecamatan' => 'nullable|string|max:100',
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

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'negara' => $validated['negara'] ?? null,
            'provinsi' => $validated['provinsi'] ?? null,
            'kota' => $validated['kota'] ?? null,
            'kecamatan' => $validated['kecamatan'] ?? null,
        ];

        if (!empty($validated['password'])) {
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
            'provinsi' => $user->provinsi,
            'kota' => $user->kota,
            'kecamatan' => $user->kecamatan,
            'avatar' => $user->avatar,
            'avatar_url' => $user->avatarUrl(),
            'created_at' => optional($user->created_at)->toDateTimeString(),
        ];
    }
}
