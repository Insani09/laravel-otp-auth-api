<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Profil Saya</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
    @include('partials.select2-dark')
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">
    <div class="max-w-5xl mx-auto px-4 py-10">
        <div class="flex flex-col md:flex-row gap-6">
            <aside class="w-full md:w-80 bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-2xl">
                <div class="flex items-center gap-4">
                    <img id="sidebar-avatar" src="{{ $user->avatarUrl() }}" alt="Avatar" class="w-16 h-16 rounded-full object-cover border border-slate-700 bg-slate-950">
                    <div>
                        <h1 id="sidebar-name" class="text-lg font-semibold text-white">{{ $user->name }}</h1>
                        <p class="text-sm text-slate-400">{{ ucfirst($user->role ?? 'user') }}</p>
                    </div>
                </div>

                <div class="mt-6 space-y-3">
                    <div class="bg-slate-950 border border-slate-800 rounded-xl p-4">
                        <p class="text-[10px] uppercase tracking-wider text-slate-500">Email</p>
                        <p class="mt-1 text-sm text-slate-300 break-all">{{ $user->email }}</p>
                    </div>
                    <div class="bg-slate-950 border border-slate-800 rounded-xl p-4">
                        <p class="text-[10px] uppercase tracking-wider text-slate-500">Alamat</p>
                        <p id="sidebar-address" class="mt-1 text-sm text-slate-300">
                            {{ collect([$user->kecamatan, $user->kota, $user->provinsi, $user->negara])->filter()->implode(', ') ?: 'Belum diisi' }}
                        </p>
                    </div>
                </div>

                <div class="mt-6 flex flex-col gap-3">
                    @if($user->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="w-full text-center bg-emerald-600 hover:bg-emerald-500 text-white py-2.5 rounded-xl font-medium transition">Admin Dashboard</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-center bg-slate-800 hover:bg-red-600/20 border border-slate-700 hover:border-red-500/30 text-slate-300 hover:text-red-400 py-2.5 rounded-xl font-medium transition">
                            Logout
                        </button>
                    </form>
                </div>
            </aside>

            <main class="flex-1 bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-2xl">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <div>
                        <h2 class="text-xl font-semibold text-white">Profil Pengguna</h2>
                        <p class="text-sm text-slate-400 mt-1">Lihat dan perbarui data registrasi Anda.</p>
                    </div>
                    <button type="button" id="btn-toggle-edit" class="bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded-xl text-sm font-medium transition">
                        Edit Profil
                    </button>
                </div>

                <div id="profile-view" class="mt-6 grid gap-4 md:grid-cols-2">
                    <div class="bg-slate-950 border border-slate-800 rounded-xl p-4">
                        <p class="text-[10px] uppercase tracking-wider text-slate-500">Nama Lengkap</p>
                        <p id="view-name" class="mt-2 text-sm text-slate-300">{{ $user->name }}</p>
                    </div>
                    <div class="bg-slate-950 border border-slate-800 rounded-xl p-4">
                        <p class="text-[10px] uppercase tracking-wider text-slate-500">Email</p>
                        <p class="mt-2 text-sm text-slate-300">{{ $user->email }}</p>
                    </div>
                    <div class="bg-slate-950 border border-slate-800 rounded-xl p-4">
                        <p class="text-[10px] uppercase tracking-wider text-slate-500">Role</p>
                        <p class="mt-2 text-sm text-slate-300">{{ ucfirst($user->role ?? 'user') }}</p>
                    </div>
                    <div class="bg-slate-950 border border-slate-800 rounded-xl p-4">
                        <p class="text-[10px] uppercase tracking-wider text-slate-500">Negara</p>
                        <p id="view-negara" class="mt-2 text-sm text-slate-300">{{ $user->negara ?: '-' }}</p>
                    </div>
                    <div class="bg-slate-950 border border-slate-800 rounded-xl p-4">
                        <p class="text-[10px] uppercase tracking-wider text-slate-500">Provinsi</p>
                        <p id="view-provinsi" class="mt-2 text-sm text-slate-300">{{ $user->provinsi ?: '-' }}</p>
                    </div>
                    <div class="bg-slate-950 border border-slate-800 rounded-xl p-4">
                        <p class="text-[10px] uppercase tracking-wider text-slate-500">Kota</p>
                        <p id="view-kota" class="mt-2 text-sm text-slate-300">{{ $user->kota ?: '-' }}</p>
                    </div>
                    <div class="bg-slate-950 border border-slate-800 rounded-xl p-4 md:col-span-2">
                        <p class="text-[10px] uppercase tracking-wider text-slate-500">Kecamatan</p>
                        <p id="view-kecamatan" class="mt-2 text-sm text-slate-300">{{ $user->kecamatan ?: '-' }}</p>
                    </div>
                </div>

                <form id="profile-edit" class="hidden mt-6 space-y-4 select2-parent" enctype="multipart/form-data">
                    <div class="flex items-center gap-4">
                        <img id="avatar-preview" src="{{ $user->avatarUrl() }}" alt="Preview" class="w-20 h-20 rounded-full object-cover border border-slate-700">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Foto Profil</label>
                            <input type="file" id="prof-avatar" name="avatar" accept="image/*" class="block w-full text-sm text-slate-400 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-blue-600 file:text-white file:text-xs">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Nama Lengkap</label>
                        <input type="text" id="prof-name" value="{{ $user->name }}" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl focus:outline-none focus:border-blue-600 text-sm text-slate-200">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Negara</label>
                        <select id="prof-country" class="w-full"><option value="">-- Pilih Negara --</option></select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Provinsi</label>
                        <select id="prof-province" disabled class="w-full"><option value="">-- Pilih Provinsi --</option></select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Kota / Kabupaten</label>
                        <select id="prof-regency" disabled class="w-full"><option value="">-- Pilih Kota / Kabupaten --</option></select>
                    </div>
                    <div id="prof-district-wrapper" class="hidden">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Kecamatan</label>
                        <select id="prof-district" disabled class="w-full"><option value="">-- Pilih Kecamatan --</option></select>
                    </div>

                    <input type="hidden" id="prof-reg-negara" value="{{ $user->negara }}">
                    <input type="hidden" id="prof-reg-provinsi" value="{{ $user->provinsi }}">
                    <input type="hidden" id="prof-reg-kota" value="{{ $user->kota }}">
                    <input type="hidden" id="prof-reg-kecamatan" value="{{ $user->kecamatan }}">

                    <div id="profile-alert" class="hidden p-3 rounded-xl text-xs text-center font-medium"></div>

                    <div class="flex gap-3">
                        <button type="submit" id="btn-save-profile" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white py-2.5 rounded-xl text-sm font-medium transition">Simpan Perubahan</button>
                        <button type="button" id="btn-cancel-edit" class="px-4 bg-slate-800 hover:bg-slate-700 text-slate-200 py-2.5 rounded-xl text-sm font-medium transition">Batal</button>
                    </div>
                </form>
            </main>
        </div>
    </div>

    @include('partials.region-cascading')

    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            }
        });

        const currentNegara = @json($user->negara);
        let regionApi = null;
        let editReady = false;

        function showAlert(msg, type) {
            const box = $('#profile-alert');
            box.removeClass('hidden bg-red-950/40 border border-red-900/50 text-red-400 bg-emerald-950/40 border-emerald-900/50 text-emerald-300');
            if (!msg) { box.addClass('hidden').text(''); return; }
            box.text(msg);
            if (type === 'success') box.addClass('bg-emerald-950/40 border border-emerald-900/50 text-emerald-300');
            else box.addClass('bg-red-950/40 border border-red-900/50 text-red-400');
        }

        function ensureRegion() {
            if (!editReady) {
                regionApi = window.initRegionCascading({
                    prefix: 'prof-',
                    hiddenPrefix: 'prof-',
                    apiBase: "{{ url('/api') }}"
                });
                if (currentNegara && regionApi) {
                    setTimeout(function () {
                        regionApi.setCountry(currentNegara);
                        $('#prof-reg-provinsi').val(@json($user->provinsi));
                        $('#prof-reg-kota').val(@json($user->kota));
                        $('#prof-reg-kecamatan').val(@json($user->kecamatan));
                    }, 600);
                }
                editReady = true;
            }
        }

        $('#btn-toggle-edit').on('click', function () {
            $('#profile-view').addClass('hidden');
            $('#profile-edit').removeClass('hidden');
            $(this).addClass('hidden');
            ensureRegion();
            showAlert('');
        });

        $('#btn-cancel-edit').on('click', function () {
            $('#profile-edit').addClass('hidden');
            $('#profile-view').removeClass('hidden');
            $('#btn-toggle-edit').removeClass('hidden');
            showAlert('');
        });

        $('#prof-avatar').on('change', function (e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function (ev) {
                $('#avatar-preview').attr('src', ev.target.result);
            };
            reader.readAsDataURL(file);
        });

        $('#profile-edit').on('submit', function (e) {
            e.preventDefault();
            if (regionApi) regionApi.syncHidden();

            const name = $('#prof-name').val().trim();
            const negara = $('#prof-reg-negara').val().trim();

            if (!name || !negara) {
                showAlert('Nama lengkap dan negara wajib diisi.');
                return;
            }

            const fd = new FormData();
            fd.append('name', name);
            fd.append('negara', negara);
            fd.append('provinsi', $('#prof-reg-provinsi').val() || '');
            fd.append('kota', $('#prof-reg-kota').val() || '');
            fd.append('kecamatan', $('#prof-reg-kecamatan').val() || '');
            fd.append('_token', $('meta[name="csrf-token"]').attr('content'));

            const file = $('#prof-avatar')[0].files[0];
            if (file) fd.append('avatar', file);

            const btn = $('#btn-save-profile');
            btn.prop('disabled', true).text('Menyimpan...');

            $.ajax({
                url: "{{ route('profile.update') }}",
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                success: function (res) {
                    const u = res.user;
                    $('#view-name, #sidebar-name').text(u.name);
                    $('#view-negara').text(u.negara || '-');
                    $('#view-provinsi').text(u.provinsi || '-');
                    $('#view-kota').text(u.kota || '-');
                    $('#view-kecamatan').text(u.kecamatan || '-');
                    $('#sidebar-avatar, #avatar-preview').attr('src', u.avatar_url);
                    const addr = [u.kecamatan, u.kota, u.provinsi, u.negara].filter(Boolean).join(', ') || 'Belum diisi';
                    $('#sidebar-address').text(addr);
                    showAlert(res.message || 'Profil berhasil diperbarui.', 'success');
                    setTimeout(function () {
                        $('#profile-edit').addClass('hidden');
                        $('#profile-view').removeClass('hidden');
                        $('#btn-toggle-edit').removeClass('hidden');
                    }, 800);
                },
                error: function (xhr) {
                    const data = xhr.responseJSON || {};
                    let msg = data.message || 'Gagal menyimpan profil.';
                    if (data.errors) {
                        msg = Object.values(data.errors).flat().join(' ');
                    }
                    showAlert(msg);
                },
                complete: function () {
                    btn.prop('disabled', false).text('Simpan Perubahan');
                }
            });
        });
    </script>
</body>
</html>
