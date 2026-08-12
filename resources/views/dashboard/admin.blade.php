<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard Admin · Manajemen User</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
    @include('partials.select2-dark')
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">
    <div class="max-w-6xl mx-auto px-4 py-10">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-2xl">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <p class="text-sm text-emerald-400">Panel Administrator</p>
                    <h1 class="text-2xl font-semibold text-white mt-1">Manajemen Pengguna</h1>
                    <p class="text-sm text-slate-400 mt-2">Kelola seluruh akun terdaftar: tambah, ubah, hapus, dan filter.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('profile') }}" class="bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 px-4 py-2 rounded-xl text-sm font-medium transition">Profil Saya</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="bg-slate-800 hover:bg-red-600/20 border border-slate-700 hover:border-red-500/30 text-slate-300 hover:text-red-400 px-4 py-2 rounded-xl text-sm font-medium transition">Logout</button>
                    </form>
                </div>
            </div>

            <div class="mt-8 grid gap-4 md:grid-cols-3">
                <div class="bg-slate-950 border border-slate-800 rounded-xl p-5">
                    <h2 class="text-sm text-slate-400">Total User</h2>
                    <p class="text-3xl font-bold text-emerald-400 mt-2" id="stat-total">{{ $stats['total'] }}</p>
                </div>
                <div class="bg-slate-950 border border-slate-800 rounded-xl p-5">
                    <h2 class="text-sm text-slate-400">Admin</h2>
                    <p class="text-3xl font-bold text-blue-400 mt-2" id="stat-admin">{{ $stats['admin'] }}</p>
                </div>
                <div class="bg-slate-950 border border-slate-800 rounded-xl p-5">
                    <h2 class="text-sm text-slate-400">User Biasa</h2>
                    <p class="text-3xl font-bold text-slate-200 mt-2" id="stat-user">{{ $stats['user'] }}</p>
                </div>
            </div>

            <div class="mt-8 flex flex-col lg:flex-row gap-3 lg:items-center lg:justify-between">
                <div class="flex flex-col sm:flex-row gap-3 flex-1">
                    <input type="search" id="filter-search" placeholder="Cari nama, email, kota..." class="flex-1 px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200 placeholder-slate-600 focus:outline-none focus:border-blue-600">
                    <select id="filter-role" class="px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200">
                        <option value="">Semua Role</option>
                        <option value="admin">Admin</option>
                        <option value="user">User</option>
                    </select>
                    <select id="filter-per-page" class="px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200">
                        <option value="5">5 / halaman</option>
                        <option value="10" selected>10 / halaman</option>
                        <option value="25">25 / halaman</option>
                        <option value="50">50 / halaman</option>
                    </select>
                </div>
                <button type="button" id="btn-open-create" class="bg-blue-600 hover:bg-blue-500 text-white px-4 py-2.5 rounded-xl text-sm font-medium transition whitespace-nowrap">
                    + Tambah User
                </button>
            </div>

            <div id="admin-alert" class="hidden mt-4 p-3 rounded-xl text-xs text-center font-medium"></div>

            <div class="mt-4 overflow-x-auto border border-slate-800 rounded-xl">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-950 text-slate-400 text-left text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3">Pengguna</th>
                            <th class="px-4 py-3">Role</th>
                            <th class="px-4 py-3">Wilayah</th>
                            <th class="px-4 py-3">Dibuat</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="users-tbody" class="divide-y divide-slate-800">
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Memuat data...</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <p id="pagination-info" class="text-xs text-slate-500"></p>
                <div id="pagination-links" class="flex flex-wrap gap-2"></div>
            </div>
        </div>
    </div>

    {{-- Modal Create / Edit --}}
    <div id="user-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70">
        <div class="w-full max-w-xl max-h-[90vh] overflow-y-auto bg-slate-900 border border-slate-800 rounded-2xl p-6 select2-parent">
            <div class="flex items-center justify-between mb-4">
                <h3 id="modal-title" class="text-lg font-semibold text-white">Tambah User</h3>
                <button type="button" id="btn-close-modal" class="text-slate-400 hover:text-white text-xl leading-none">&times;</button>
            </div>

            <form id="user-form" class="space-y-4" enctype="multipart/form-data">
                <input type="hidden" id="edit-user-id" value="">

                <div class="flex items-center gap-4">
                    <img id="admin-avatar-preview" src="https://ui-avatars.com/api/?name=User&background=1e293b&color=60a5fa" class="w-16 h-16 rounded-full object-cover border border-slate-700" alt="Avatar">
                    <div class="flex-1">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Foto Profil</label>
                        <input type="file" id="admin-avatar" accept="image/*" class="block w-full text-sm text-slate-400 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-blue-600 file:text-white file:text-xs">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Nama Lengkap</label>
                    <input type="text" id="admin-name" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200 focus:outline-none focus:border-blue-600" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Email</label>
                    <input type="email" id="admin-email" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200 focus:outline-none focus:border-blue-600" required>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Role</label>
                    <select id="admin-role" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Kata Sandi <span id="password-hint" class="normal-case text-slate-500 font-normal">(wajib)</span></label>
                    <input type="password" id="admin-password" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200 focus:outline-none focus:border-blue-600" autocomplete="new-password">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Konfirmasi Kata Sandi</label>
                    <input type="password" id="admin-password-confirmation" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-slate-200 focus:outline-none focus:border-blue-600" autocomplete="new-password">
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Negara</label>
                    <select id="admin-country" class="w-full"><option value="">-- Pilih Negara --</option></select>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Provinsi</label>
                    <select id="admin-province" disabled class="w-full"><option value="">-- Pilih Provinsi --</option></select>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Kota / Kabupaten</label>
                    <select id="admin-regency" disabled class="w-full"><option value="">-- Pilih Kota / Kabupaten --</option></select>
                </div>
                <div id="admin-district-wrapper" class="hidden">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Kecamatan</label>
                    <select id="admin-district" disabled class="w-full"><option value="">-- Pilih Kecamatan --</option></select>
                </div>

                <input type="hidden" id="admin-reg-negara" value="">
                <input type="hidden" id="admin-reg-provinsi" value="">
                <input type="hidden" id="admin-reg-kota" value="">
                <input type="hidden" id="admin-reg-kecamatan" value="">

                <div id="modal-alert" class="hidden p-3 rounded-xl text-xs text-center font-medium"></div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" id="btn-save-user" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white py-2.5 rounded-xl text-sm font-medium transition">Simpan</button>
                    <button type="button" id="btn-cancel-modal" class="px-4 bg-slate-800 hover:bg-slate-700 text-slate-200 py-2.5 rounded-xl text-sm font-medium transition">Batal</button>
                </div>
            </form>
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

        const routes = {
            index: "{{ route('admin.users.index') }}",
            store: "{{ route('admin.users.store') }}",
            show: (id) => "{{ url('/admin/users') }}/" + id,
            update: (id) => "{{ url('/admin/users') }}/" + id,
            destroy: (id) => "{{ url('/admin/users') }}/" + id,
        };

        let currentPage = 1;
        let regionApi = null;
        let regionReady = false;

        function showAlert(target, msg, type) {
            const box = $(target);
            box.removeClass('hidden bg-red-950/40 border border-red-900/50 text-red-400 bg-emerald-950/40 border-emerald-900/50 text-emerald-300');
            if (!msg) { box.addClass('hidden').text(''); return; }
            box.text(msg);
            if (type === 'success') box.addClass('bg-emerald-950/40 border border-emerald-900/50 text-emerald-300');
            else box.addClass('bg-red-950/40 border border-red-900/50 text-red-400');
        }

        function ensureRegion() {
            if (!regionReady) {
                regionApi = window.initRegionCascading({
                    prefix: 'admin-',
                    hiddenPrefix: 'admin-',
                    apiBase: "{{ url('/api') }}"
                });
                regionReady = true;
            }
        }

        function loadUsers(page) {
            currentPage = page || 1;
            const params = {
                page: currentPage,
                search: $('#filter-search').val(),
                role: $('#filter-role').val(),
                per_page: $('#filter-per-page').val()
            };

            $('#users-tbody').html('<tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Memuat data...</td></tr>');

            $.get(routes.index, params)
                .done(function (res) {
                    renderTable(res);
                    updateStatsFromRows(res);
                })
                .fail(function (xhr) {
                    const msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal memuat daftar pengguna.';
                    $('#users-tbody').html('<tr><td colspan="5" class="px-4 py-8 text-center text-red-400">' + msg + '</td></tr>');
                });
        }

        function updateStatsFromRows(res) {
            // Stats utama dari server saat load halaman; refresh ringan via recount opsional
        }

        function renderTable(res) {
            const rows = res.data || [];
            if (!rows.length) {
                $('#users-tbody').html('<tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Tidak ada data pengguna.</td></tr>');
            } else {
                const html = rows.map(function (u) {
                    const wilayah = [u.kecamatan, u.kota, u.provinsi, u.negara].filter(Boolean).join(', ') || '-';
                    const roleBadge = u.role === 'admin'
                        ? '<span class="inline-flex px-2 py-0.5 rounded-md text-xs bg-emerald-600/20 text-emerald-300 border border-emerald-500/30">Admin</span>'
                        : '<span class="inline-flex px-2 py-0.5 rounded-md text-xs bg-slate-700/50 text-slate-300 border border-slate-600">User</span>';
                    return `
                        <tr class="hover:bg-slate-950/60">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <img src="${u.avatar_url}" class="w-9 h-9 rounded-full object-cover border border-slate-700" alt="">
                                    <div>
                                        <p class="font-medium text-slate-100">${escapeHtml(u.name)}</p>
                                        <p class="text-xs text-slate-500">${escapeHtml(u.email)}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">${roleBadge}</td>
                            <td class="px-4 py-3 text-slate-400 max-w-[220px] truncate" title="${escapeHtml(wilayah)}">${escapeHtml(wilayah)}</td>
                            <td class="px-4 py-3 text-slate-500 whitespace-nowrap">${u.created_at || '-'}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button type="button" class="text-blue-400 hover:text-blue-300 text-xs font-semibold mr-3" onclick="openEdit(${u.id})">Edit</button>
                                <button type="button" class="text-red-400 hover:text-red-300 text-xs font-semibold" onclick="deleteUser(${u.id}, '${escapeHtml(u.name)}')">Hapus</button>
                            </td>
                        </tr>`;
                }).join('');
                $('#users-tbody').html(html);
            }

            $('#pagination-info').text(`Menampilkan ${res.from || 0}–${res.to || 0} dari ${res.total || 0} data`);

            let links = '';
            (res.links || []).forEach(function (link) {
                if (!link.url) {
                    links += `<span class="px-3 py-1.5 rounded-lg text-xs bg-slate-950 border border-slate-800 text-slate-600">${stripTags(link.label)}</span>`;
                    return;
                }
                const active = link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-slate-950 text-slate-300 border-slate-800 hover:border-slate-600';
                const pageMatch = link.url.match(/[?&]page=(\d+)/);
                const pageNum = pageMatch ? pageMatch[1] : 1;
                links += `<button type="button" class="px-3 py-1.5 rounded-lg text-xs border ${active}" onclick="loadUsers(${pageNum})">${stripTags(link.label)}</button>`;
            });
            $('#pagination-links').html(links);
        }

        function escapeHtml(str) {
            return String(str || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function stripTags(html) {
            const tmp = document.createElement('div');
            tmp.innerHTML = html;
            return tmp.textContent || tmp.innerText || '';
        }

        function openModal(mode) {
            ensureRegion();
            showAlert('#modal-alert', '');
            $('#user-modal').removeClass('hidden');
            if (mode === 'create') {
                $('#modal-title').text('Tambah User');
                $('#password-hint').text('(wajib)');
                $('#edit-user-id').val('');
                $('#user-form')[0].reset();
                $('#admin-avatar-preview').attr('src', 'https://ui-avatars.com/api/?name=User&background=1e293b&color=60a5fa');
                $('#admin-reg-negara, #admin-reg-provinsi, #admin-reg-kota, #admin-reg-kecamatan').val('');
                if (regionApi) {
                    $('#admin-country').val(null).trigger('change');
                }
            }
        }

        function closeModal() {
            $('#user-modal').addClass('hidden');
        }

        window.openEdit = function (id) {
            openModal('edit');
            $('#modal-title').text('Edit User');
            $('#password-hint').text('(opsional, isi untuk mengubah)');
            $('#edit-user-id').val(id);

            $.get(routes.show(id))
                .done(function (res) {
                    const u = res.user;
                    $('#admin-name').val(u.name);
                    $('#admin-email').val(u.email);
                    $('#admin-role').val(u.role);
                    $('#admin-avatar-preview').attr('src', u.avatar_url);
                    $('#admin-reg-negara').val(u.negara || '');
                    $('#admin-reg-provinsi').val(u.provinsi || '');
                    $('#admin-reg-kota').val(u.kota || '');
                    $('#admin-reg-kecamatan').val(u.kecamatan || '');
                    if (u.negara && regionApi) {
                        setTimeout(function () {
                            regionApi.setCountry(u.negara);
                        }, 400);
                    }
                })
                .fail(function (xhr) {
                    showAlert('#modal-alert', (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal memuat data pengguna.');
                });
        };

        window.deleteUser = function (id, name) {
            if (!confirm('Hapus akun "' + name + '" beserta foto profilnya? Tindakan ini tidak dapat dibatalkan.')) {
                return;
            }
            $.ajax({
                url: routes.destroy(id),
                method: 'DELETE',
                success: function (res) {
                    showAlert('#admin-alert', res.message || 'Pengguna dihapus.', 'success');
                    loadUsers(currentPage);
                    refreshStats();
                },
                error: function (xhr) {
                    showAlert('#admin-alert', (xhr.responseJSON && xhr.responseJSON.message) || 'Gagal menghapus pengguna.');
                }
            });
        };

        function refreshStats() {
            $.get(routes.index, { per_page: 1 })
                .done(function () {
                    // Reload page stats via dedicated recount
                    location.reload();
                });
        }

        $('#btn-open-create').on('click', function () { openModal('create'); });
        $('#btn-close-modal, #btn-cancel-modal').on('click', closeModal);

        $('#admin-avatar').on('change', function (e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function (ev) {
                $('#admin-avatar-preview').attr('src', ev.target.result);
            };
            reader.readAsDataURL(file);
        });

        let searchTimer = null;
        $('#filter-search').on('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () { loadUsers(1); }, 350);
        });
        $('#filter-role, #filter-per-page').on('change', function () { loadUsers(1); });

        $('#user-form').on('submit', function (e) {
            e.preventDefault();
            if (regionApi) regionApi.syncHidden();

            const id = $('#edit-user-id').val();
            const isEdit = !!id;
            const fd = new FormData();
            fd.append('name', $('#admin-name').val().trim());
            fd.append('email', $('#admin-email').val().trim());
            fd.append('role', $('#admin-role').val());
            fd.append('negara', $('#admin-reg-negara').val() || '');
            fd.append('provinsi', $('#admin-reg-provinsi').val() || '');
            fd.append('kota', $('#admin-reg-kota').val() || '');
            fd.append('kecamatan', $('#admin-reg-kecamatan').val() || '');
            fd.append('_token', $('meta[name="csrf-token"]').attr('content'));

            const password = $('#admin-password').val();
            const passwordConfirmation = $('#admin-password-confirmation').val();
            if (password || !isEdit) {
                fd.append('password', password);
                fd.append('password_confirmation', passwordConfirmation);
            }

            const file = $('#admin-avatar')[0].files[0];
            if (file) fd.append('avatar', file);

            const btn = $('#btn-save-user');
            btn.prop('disabled', true).text('Menyimpan...');

            $.ajax({
                url: isEdit ? routes.update(id) : routes.store,
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                success: function (res) {
                    showAlert('#admin-alert', res.message || 'Berhasil disimpan.', 'success');
                    closeModal();
                    loadUsers(isEdit ? currentPage : 1);
                    setTimeout(function () { location.reload(); }, 600);
                },
                error: function (xhr) {
                    const data = xhr.responseJSON || {};
                    let msg = data.message || 'Gagal menyimpan data.';
                    if (data.errors) msg = Object.values(data.errors).flat().join(' ');
                    showAlert('#modal-alert', msg);
                },
                complete: function () {
                    btn.prop('disabled', false).text('Simpan');
                }
            });
        });

        $(function () {
            loadUsers(1);
        });
    </script>
</body>
</html>
