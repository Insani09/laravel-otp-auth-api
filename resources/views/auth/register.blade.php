{{-- Isolasi total: hanya konten register, tanpa elemen login --}}
<div id="step-register" class="hidden space-y-5">
    <div class="text-center mb-2">
        <h3 class="text-lg font-semibold text-white">Buat Akun Baru</h3>
        <p class="text-xs text-slate-400 mt-1">Lengkapi data wilayah lalu informasi akun Anda.</p>
    </div>

    {{-- BAGIAN 1: DATA WILAYAH --}}
    <div class="space-y-3 select2-parent">
        <p class="text-[10px] font-semibold uppercase tracking-wider text-blue-400">Bagian 1 · Data Wilayah / Alamat</p>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Negara</label>
            <select id="country" class="w-full">
                <option value="">-- Pilih Negara --</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Provinsi</label>
            <select id="province" disabled class="w-full">
                <option value="">-- Pilih Provinsi --</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Kota / Kabupaten</label>
            <select id="regency" disabled class="w-full">
                <option value="">-- Pilih Kota / Kabupaten --</option>
            </select>
        </div>

        <div id="district-wrapper" class="hidden">
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Kecamatan</label>
            <select id="district" disabled class="w-full">
                <option value="">-- Pilih Kecamatan --</option>
            </select>
        </div>

        <input type="hidden" id="reg-negara" name="negara" value="">
        <input type="hidden" id="reg-provinsi" name="provinsi" value="">
        <input type="hidden" id="reg-kota" name="kota" value="">
        <input type="hidden" id="reg-kecamatan" name="kecamatan" value="">
    </div>

    {{-- BAGIAN 2: INFORMASI AKUN --}}
    <div class="space-y-3 pt-2 border-t border-slate-800">
        <p class="text-[10px] font-semibold uppercase tracking-wider text-blue-400">Bagian 2 · Informasi Akun</p>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Nama Lengkap</label>
            <input type="text" id="reg-name" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 text-sm placeholder-slate-600 transition text-slate-200" placeholder="Nama lengkap Anda" autocomplete="name">
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Alamat Email</label>
            <input type="email" id="reg-email" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 text-sm placeholder-slate-600 transition text-slate-200" placeholder="nama@gmail.com" autocomplete="email">
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Kata Sandi</label>
            <div class="relative">
                <input type="password" id="reg-password" class="w-full pl-4 pr-10 py-2.5 bg-slate-950 border border-slate-800 rounded-xl focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 text-sm placeholder-slate-600 transition text-slate-200" placeholder="Minimal 12 karakter, huruf & angka" autocomplete="new-password">
                <button type="button" onclick="togglePassword('reg-password', 'icon-pass1')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-500 hover:text-slate-300 transition" aria-label="Tampilkan kata sandi">
                    <svg id="icon-pass1" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                </button>
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Konfirmasi Kata Sandi</label>
            <div class="relative">
                <input type="password" id="reg-password-confirmation" class="w-full pl-4 pr-10 py-2.5 bg-slate-950 border border-slate-800 rounded-xl focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 text-sm placeholder-slate-600 transition text-slate-200" placeholder="Ulangi kata sandi" autocomplete="new-password">
                <button type="button" onclick="togglePassword('reg-password-confirmation', 'icon-pass2')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-500 hover:text-slate-300 transition" aria-label="Tampilkan konfirmasi kata sandi">
                    <svg id="icon-pass2" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div id="register-alert" class="hidden p-3 rounded-xl text-xs text-center font-medium"></div>

    <button type="button" onclick="registerUser()" id="btn-register" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-medium py-2.5 rounded-xl transition duration-200 shadow-md shadow-blue-900/20 text-sm flex items-center justify-center gap-2">
        Buat Akun
    </button>

    <div class="text-center">
        <p class="text-xs text-slate-400">Sudah punya akun? <button type="button" onclick="showLogin()" class="text-blue-400 font-semibold hover:underline">Masuk</button></p>
    </div>
</div>
