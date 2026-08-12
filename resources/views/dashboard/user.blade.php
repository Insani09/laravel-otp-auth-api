<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pengguna</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">
    <div class="max-w-5xl mx-auto px-4 py-10">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 shadow-2xl">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <p class="text-sm text-blue-400">Selamat datang, {{ $user->name }}</p>
                    <h1 class="text-2xl font-semibold text-white mt-1">Dashboard Pengguna</h1>
                    <p class="text-sm text-slate-400 mt-2">Kelola profil dan data akun Anda.</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('profile') }}" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-xl text-sm font-medium transition">Lihat Profil</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="bg-slate-800 hover:bg-red-600/20 border border-slate-700 hover:border-red-500/30 text-slate-300 hover:text-red-400 px-4 py-2 rounded-xl text-sm font-medium transition">Logout</button>
                    </form>
                </div>
            </div>

            <div class="mt-8 grid gap-4 md:grid-cols-2">
                <div class="bg-slate-950 border border-slate-800 rounded-xl p-5">
                    <h2 class="text-lg font-semibold text-white">Informasi Akun</h2>
                    <p class="text-sm text-slate-400 mt-2">Email: {{ $user->email }}</p>
                    <p class="text-sm text-slate-400 mt-1">Role: {{ ucfirst($user->role ?? 'user') }}</p>
                    <p class="text-sm text-slate-400 mt-1">
                        Alamat: {{ collect([$user->kecamatan, $user->kota, $user->provinsi, $user->negara])->filter()->implode(', ') ?: 'Belum diisi' }}
                    </p>
                </div>
                <div class="bg-slate-950 border border-slate-800 rounded-xl p-5">
                    <h2 class="text-lg font-semibold text-white">Aksi Cepat</h2>
                    <p class="text-sm text-slate-400 mt-2">Perbarui foto profil, nama, dan data wilayah melalui halaman profil.</p>
                    <a href="{{ route('profile') }}" class="inline-block mt-4 text-sm text-blue-400 hover:underline">Buka halaman profil →</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
