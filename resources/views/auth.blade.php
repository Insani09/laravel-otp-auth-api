<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autentikasi Aman - Sistem OTP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 flex items-center justify-center h-screen selection:bg-blue-600 selection:text-white">

    <div class="bg-slate-900 border border-slate-800 p-8 rounded-2xl shadow-2xl w-[420px]">
        
        <!-- Header dengan ikon gembok -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-12 h-12 bg-blue-600/10 border border-blue-500/20 rounded-xl text-blue-400 mb-3 shadow-inner">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <h2 class="text-xl font-bold tracking-tight text-white">Autentikasi Akun</h2>
        </div>

        <!-- Bagian 1: Login / Minta OTP -->
        <div id="step-email" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Alamat Email</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    </span>
                    <input type="email" id="email" class="w-full pl-10 pr-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 text-sm placeholder-slate-600 transition text-slate-200" placeholder="nama@gmail.com">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Password</label>
                <div class="relative">
                    <input type="password" id="password" class="w-full pl-4 pr-10 py-2.5 bg-slate-950 border border-slate-800 rounded-xl focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 text-sm placeholder-slate-600 transition text-slate-200" placeholder="Password Anda">
                    <button type="button" onclick="togglePassword('password', 'icon-login-pass')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-500 hover:text-slate-300 transition">
                        <svg id="icon-login-pass" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <label class="inline-flex items-center gap-2 text-sm text-slate-300">
                <input type="checkbox" id="remember_me" class="rounded border-slate-700 bg-slate-950 text-blue-500 focus:ring-blue-500">
                Ingat saya selama 30 hari
            </label>
            <button onclick="requestOtp()" id="btn-request" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-medium py-2.5 rounded-xl transition duration-200 shadow-md shadow-blue-900/20 text-sm">
                Kirim Kode OTP
            </button>
            <!-- <p class="text-center text-xs text-slate-400 mt-2">Pilih ini jika kamu sudah ingat password. Sistem akan verifikasi password dulu dan kemudian kirim OTP.</p> -->
            <button onclick="passwordlessLogin()" id="btn-passwordless" class="w-full bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-200 font-medium py-2.5 rounded-xl transition duration-200 text-sm">
                Masuk tanpa Password
            </button>
            <!-- <p class="text-center text-xs text-slate-400 mt-2">Gunakan ini bila kamu lupa password. Cukup masukkan email saja, OTP akan dikirim ke email.</p> -->
            <div class="text-center mt-2">
                <button type="button" onclick="showForgot()" class="text-blue-400 text-xs font-semibold hover:underline">Lupa password?</button>
            </div>
            <div class="text-center mt-4">
                <p class="text-xs text-slate-400">Belum punya akun? <button type="button" onclick="showRegister()" class="text-blue-400 font-semibold hover:underline">Daftar sekarang</button></p>
            </div>
        </div>

        <!-- Form pendaftaran akun -->
        <div id="step-register" class="hidden space-y-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Nama Lengkap</label>
                <input type="text" id="reg-name" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 text-sm placeholder-slate-600 transition text-slate-200" placeholder="Nama Kamu">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Alamat Email</label>
                <input type="email" id="reg-email" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 text-sm placeholder-slate-600 transition text-slate-200" placeholder="nama@gmail.com">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Password</label>
                <div class="relative">
                    <input type="password" id="reg-password" class="w-full pl-4 pr-10 py-2.5 bg-slate-950 border border-slate-800 rounded-xl focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 text-sm placeholder-slate-600 transition text-slate-200" placeholder="Minimal 12 karakter">
                    <button type="button" onclick="togglePassword('reg-password', 'icon-pass1')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-500 hover:text-slate-300 transition">
                        <svg id="icon-pass1" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Konfirmasi Password</label>
                <div class="relative">
                    <input type="password" id="reg-password-confirmation" class="w-full pl-4 pr-10 py-2.5 bg-slate-950 border border-slate-800 rounded-xl focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 text-sm placeholder-slate-600 transition text-slate-200" placeholder="Ulangi password">
                    <button type="button" onclick="togglePassword('reg-password-confirmation', 'icon-pass2')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-500 hover:text-slate-300 transition">
                        <svg id="icon-pass2" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <button onclick="registerUser()" id="btn-register" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-medium py-2.5 rounded-xl transition duration-200 shadow-md shadow-blue-900/20 text-sm flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
                Daftar Akun
            </button>

            <!-- Tombol kembali ke login -->
            <div class="text-center mt-4">
                <p class="text-xs text-slate-400">Sudah punya akun? <button type="button" onclick="showLogin()" class="text-blue-400 font-semibold hover:underline">Masuk</button></p>
            </div>
        </div>

        <!-- Bagian lupa password -->
        <div id="step-forgot" class="hidden space-y-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Email untuk reset</label>
                <input type="email" id="forgot-email" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 text-sm placeholder-slate-600 transition text-slate-200" placeholder="nama@gmail.com">
            </div>
            <button onclick="requestPasswordResetOtp()" id="btn-forgot" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-medium py-2.5 rounded-xl transition duration-200 shadow-md shadow-blue-900/20 text-sm">
                Kirim Kode Reset Password
            </button>
            <div class="text-center mt-4">
                <button type="button" onclick="showLogin()" class="text-slate-400 text-xs font-semibold hover:underline">Kembali ke Login</button>
            </div>
        </div>

        <!-- Bagian reset password -->
        <div id="step-reset" class="hidden space-y-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Email</label>
                <input type="email" id="reset-email" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 text-sm placeholder-slate-600 transition text-slate-200" placeholder="nama@gmail.com">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Kode OTP Reset</label>
                <input type="text" id="reset-otp" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 text-sm placeholder-slate-600 transition text-slate-200" placeholder="123456">
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Password Baru</label>
                <div class="relative">
                    <input type="password" id="reset-password" class="w-full pl-4 pr-10 py-2.5 bg-slate-950 border border-slate-800 rounded-xl focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 text-sm placeholder-slate-600 transition text-slate-200" placeholder="Minimal 12 karakter">
                    <button type="button" onclick="togglePassword('reset-password', 'icon-reset-pass1')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-500 hover:text-slate-300 transition">
                        <svg id="icon-reset-pass1" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Konfirmasi Password Baru</label>
                <div class="relative">
                    <input type="password" id="reset-password-confirmation" class="w-full pl-4 pr-10 py-2.5 bg-slate-950 border border-slate-800 rounded-xl focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 text-sm placeholder-slate-600 transition text-slate-200" placeholder="Ulangi password baru">
                    <button type="button" onclick="togglePassword('reset-password-confirmation', 'icon-reset-pass2')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-500 hover:text-slate-300 transition">
                        <svg id="icon-reset-pass2" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <button onclick="resetPassword()" id="btn-reset" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-medium py-2.5 rounded-xl transition duration-200 shadow-md shadow-emerald-900/20 text-sm">
                Ubah Password
            </button>
            <div class="text-center mt-4 space-y-2">
                <p class="text-xs text-slate-400">
                    Tidak menerima kode atau kode sudah tidak berlaku?
                    <button type="button" onclick="resendResetOtp()" id="btn-resend-reset" class="text-blue-400 font-semibold hover:underline disabled:text-slate-500 disabled:no-underline disabled:cursor-not-allowed">Kirim Ulang Kode</button>
                </p>
                <button type="button" onclick="showLogin()" class="text-slate-400 text-xs font-semibold hover:underline">Kembali ke Login</button>
            </div>
        </div>

        <!-- Bagian OTP 6 digit -->
        <div id="step-otp" class="hidden space-y-4">
            <div class="bg-slate-950 border border-slate-800 p-3 rounded-xl text-center">
                <p class="text-xs text-slate-400">Kode OTP telah dikirimkan ke:</p>
                <span id="lbl-email" class="font-semibold text-xs text-blue-400"></span>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5 text-center">Masukkan 6 Digit OTP</label>
                
                <input type="hidden" id="otp-hidden">

                <div class="flex justify-between gap-2" id="otp-container">
                    <input type="text" maxlength="1" class="w-12 h-14 bg-slate-950 border border-slate-800 rounded-xl text-center text-xl font-bold text-slate-200 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition otp-input" />
                    <input type="text" maxlength="1" class="w-12 h-14 bg-slate-950 border border-slate-800 rounded-xl text-center text-xl font-bold text-slate-200 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition otp-input" />
                    <input type="text" maxlength="1" class="w-12 h-14 bg-slate-950 border border-slate-800 rounded-xl text-center text-xl font-bold text-slate-200 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition otp-input" />
                    <input type="text" maxlength="1" class="w-12 h-14 bg-slate-950 border border-slate-800 rounded-xl text-center text-xl font-bold text-slate-200 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition otp-input" />
                    <input type="text" maxlength="1" class="w-12 h-14 bg-slate-950 border border-slate-800 rounded-xl text-center text-xl font-bold text-slate-200 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition otp-input" />
                    <input type="text" maxlength="1" class="w-12 h-14 bg-slate-950 border border-slate-800 rounded-xl text-center text-xl font-bold text-slate-200 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition otp-input" />
                </div>
            </div>

            <button onclick="verifyOtp()" id="btn-verify" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-medium py-2.5 rounded-xl transition duration-200 shadow-md shadow-emerald-900/20 text-sm">
                Verifikasi & Masuk
            </button>

            <!-- Tombol batal / kembali -->
            <div class="text-center mt-5">
                <button type="button" onclick="cancelOtp()" class="text-sm text-slate-400 hover:text-slate-200 transition font-medium flex items-center justify-center w-full space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span>Salah ketik email? Kembali</span>
                </button>
            </div>
        </div>

        <!-- Kotak pesan error / sukses -->
        <div id="error-box" class="hidden mt-4 p-3 bg-red-950/40 border border-red-900/50 rounded-xl text-red-400 text-xs text-center font-medium">
            <span id="error-msg"></span>
        </div>
    </div>

    <script>
        let userEmail = "";

        // Menampilkan notifikasi error atau sukses
        function showMessage(message, type = 'error') {
            const box = document.getElementById('error-box');
            const msg = document.getElementById('error-msg');
            msg.innerText = message;
            box.classList.remove('hidden');

            box.classList.remove('bg-red-950/40', 'border-red-900/50', 'text-red-400', 'bg-emerald-950/40', 'border-emerald-900/50', 'text-emerald-300');
            if (type === 'success') {
                box.classList.add('bg-emerald-950/40', 'border-emerald-900/50', 'text-emerald-300');
            } else {
                box.classList.add('bg-red-950/40', 'border-red-900/50', 'text-red-400');
            }
        }

        function showError(message) {
            showMessage(message, 'error');
        }

        function showSuccess(message) {
            showMessage(message, 'success');
        }

        function hideError() {
            const box = document.getElementById('error-box');
            box.classList.add('hidden');
        }

        // Menyalakan dan mematikan form login / register / reset
        function showRegister() {
            hideError();
            document.getElementById('step-email').classList.add('hidden');
            document.getElementById('step-otp').classList.add('hidden');
            document.getElementById('step-forgot').classList.add('hidden');
            document.getElementById('step-reset').classList.add('hidden');
            document.getElementById('step-register').classList.remove('hidden');
        }

        function showLogin() {
            hideError();
            document.getElementById('step-register').classList.add('hidden');
            document.getElementById('step-otp').classList.add('hidden');
            document.getElementById('step-forgot').classList.add('hidden');
            document.getElementById('step-reset').classList.add('hidden');
            document.getElementById('step-email').classList.remove('hidden');
        }

        function showForgot() {
            hideError();
            document.getElementById('step-email').classList.add('hidden');
            document.getElementById('step-otp').classList.add('hidden');
            document.getElementById('step-register').classList.add('hidden');
            document.getElementById('step-reset').classList.add('hidden');
            document.getElementById('step-forgot').classList.remove('hidden');
        }

        // Toggle tombol lihat / sembunyikan password
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (input.type === 'password') {
                input.type = 'text';
                // Ganti icon jadi mata terpotong
                icon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>`;
            } else {
                input.type = 'password';
                // Kembali ke icon mata terbuka
                icon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>`;
            }
        }

        // Proses request OTP untuk login dengan password
        async function requestOtp() {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const rememberMe = document.getElementById('remember_me').checked;
            const btn = document.getElementById('btn-request');
            hideError();

            if (!email || !password) {
                showError("Alamat email dan password wajib diisi.");
                return;
            }

            btn.innerText = "Memproses...";
            btn.disabled = true;

            try {
                await axios.post('/api/request-otp', {
                    email,
                    password,
                    remember_me: rememberMe,
                });

                userEmail = email;
                document.getElementById('step-email').classList.add('hidden');
                document.getElementById('step-otp').classList.remove('hidden');
                document.getElementById('lbl-email').innerText = email;

                document.querySelectorAll('.otp-input')[0].focus();
            } catch (error) {
                if (error.response?.status === 401) {
                    showError(error.response?.data?.message || "Email atau password salah.");
                } else if (error.response?.status === 404 || error.response?.status === 422) {
                    showError(error.response?.data?.message || "Email belum terdaftar. Silakan daftar terlebih dahulu.");
                } else {
                    showError(error.response?.data?.message || "Gagal memproses permintaan.");
                }

                btn.innerText = "Kirim Kode OTP";
                btn.disabled = false;
            }
        }

        async function passwordlessLogin() {
            const email = document.getElementById('email').value;
            const rememberMe = document.getElementById('remember_me').checked;
            const btn = document.getElementById('btn-passwordless');
            hideError();

            if (!email) {
                showError("Alamat email wajib diisi untuk login tanpa password.");
                return;
            }

            btn.innerText = "Memproses...";
            btn.disabled = true;

            try {
                await axios.post('/api/request-passwordless-otp', {
                    email,
                    remember_me: rememberMe,
                });

                userEmail = email;
                document.getElementById('step-email').classList.add('hidden');
                document.getElementById('step-otp').classList.remove('hidden');
                document.getElementById('lbl-email').innerText = email;
                document.querySelectorAll('.otp-input')[0].focus();
            } catch (error) {
                if (error.response?.status === 404 || error.response?.status === 422) {
                    showError(error.response?.data?.message || "Email belum terdaftar. Silakan daftar terlebih dahulu.");
                } else {
                    showError(error.response?.data?.message || "Gagal memproses permintaan.");
                }
            } finally {
                btn.innerText = "Masuk tanpa Password";
                btn.disabled = false;
            }
        }

        function passwordValid(password) {
            return password.length >= 12 &&
                /[A-Za-z]/.test(password) &&
                /\d/.test(password) &&
                !/\s/.test(password);
        }

        async function requestPasswordResetOtp() {
            const email = document.getElementById('forgot-email').value;
            const btn = document.getElementById('btn-forgot');
            hideError();

            if (!email) {
                showError("Email wajib diisi untuk reset password.");
                return;
            }

            btn.innerText = "Memproses...";
            btn.disabled = true;

            try {
                await axios.post('/api/forgot-password', { email });
                showSuccess('Kode reset password telah dikirim ke email Anda.');
                document.getElementById('reset-email').value = email;
                document.getElementById('step-forgot').classList.add('hidden');
                document.getElementById('step-reset').classList.remove('hidden');
            } catch (error) {
                showError(error.response?.data?.message || "Gagal mengirim kode reset.");
            } finally {
                btn.innerText = "Kirim Kode Reset";
                btn.disabled = false;
            }
        }

        let resendCooldownTimer = null;

        function startResendCooldown(seconds = 60) {
            const btn = document.getElementById('btn-resend-reset');
            let remaining = seconds;
            btn.disabled = true;
            btn.innerText = `Kirim Ulang Kode (${remaining}s)`;

            clearInterval(resendCooldownTimer);
            resendCooldownTimer = setInterval(() => {
                remaining--;
                if (remaining <= 0) {
                    clearInterval(resendCooldownTimer);
                    btn.disabled = false;
                    btn.innerText = 'Kirim Ulang Kode';
                } else {
                    btn.innerText = `Kirim Ulang Kode (${remaining}s)`;
                }
            }, 1000);
        }

        async function resendResetOtp() {
            const email = document.getElementById('reset-email').value;
            const btn = document.getElementById('btn-resend-reset');
            hideError();

            if (!email) {
                showError("Email tidak ditemukan. Silakan ulangi dari awal.");
                return;
            }

            btn.disabled = true;

            try {
                await axios.post('/api/forgot-password', { email });

                // Kode lama otomatis invalid begitu kode baru dikirim —
                // kosongkan input supaya user tidak salah pakai kode lama.
                document.getElementById('reset-otp').value = '';
                document.getElementById('reset-otp').focus();

                showSuccess('Kode baru telah dikirim ke email Anda. Kode sebelumnya sudah tidak berlaku.');
                startResendCooldown(60);
            } catch (error) {
                showError(error.response?.data?.message || "Gagal mengirim ulang kode. Coba lagi nanti.");
                btn.disabled = false;
            }
        }

        async function resetPassword() {
            const email = document.getElementById('reset-email').value;
            const otp = document.getElementById('reset-otp').value;
            const password = document.getElementById('reset-password').value;
            const password_confirmation = document.getElementById('reset-password-confirmation').value;
            const btn = document.getElementById('btn-reset');
            hideError();

            if (!email || !otp || !password || !password_confirmation) {
                showError("Semua kolom harus diisi untuk reset password.");
                return;
            }

            if (password !== password_confirmation) {
                showError("Konfirmasi password tidak cocok.");
                return;
            }

            if (!passwordValid(password)) {
                showError("Password minimal 12 karakter, harus mengandung huruf dan angka, serta tidak boleh memakai spasi.");
                return;
            }

            if (!passwordValid(password)) {
                showError("Password minimal 12 karakter, harus mengandung huruf dan angka, serta tidak boleh memakai spasi.");
                return;
            }

            btn.innerText = "Memproses...";
            btn.disabled = true;

            try {
                await axios.post('/api/reset-password', {
                    email,
                    otp,
                    password,
                    password_confirmation,
                });
                showSuccess('Password berhasil diubah. Silakan login kembali.');
                document.getElementById('step-reset').classList.add('hidden');
                showLogin();
            } catch (error) {
                showError(error.response?.data?.message || "Gagal mereset password.");
            } finally {
                btn.innerText = "Ubah Password";
                btn.disabled = false;
            }
        }

        function cancelForgot() {
            document.getElementById('step-reset').classList.add('hidden');
            document.getElementById('step-forgot').classList.add('hidden');
            document.getElementById('step-email').classList.remove('hidden');
            hideError();
        }

        // Proses pendaftaran user baru
        async function registerUser() {
            const name = document.getElementById('reg-name').value;
            const email = document.getElementById('reg-email').value;
            const password = document.getElementById('reg-password').value;
            const password_confirmation = document.getElementById('reg-password-confirmation').value;
            const btn = document.getElementById('btn-register');
            hideError();

            if (!name || !email || !password || !password_confirmation) {
                showError("Semua kolom wajib diisi.");
                return;
            }

            if (password !== password_confirmation) {
                showError("Konfirmasi password tidak cocok.");
                return;
            }

            btn.innerText = "Mendaftarkan...";
            btn.disabled = true;

            try {
                await axios.post('/api/register', {
                    name,
                    email,
                    password,
                    password_confirmation
                });

                showLogin();
                showSuccess('Registrasi berhasil. Silakan login untuk melanjutkan.');
            } catch (error) {
                showError(error.response?.data?.message || "Gagal melakukan registrasi.");
                btn.innerText = "Daftar Akun";
                btn.disabled = false;
            }
        }

        // Logika auto-focus, backspace, dan paste untuk input OTP 6 digit
        const inputs = document.querySelectorAll('.otp-input');
        const hiddenInput = document.getElementById('otp-hidden');

        const updateHiddenInput = () => {
            hiddenInput.value = Array.from(inputs).map(input => input.value).join('');
        };

        inputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                if (isNaN(e.target.value)) {
                    e.target.value = '';
                    return;
                }
                if (e.target.value !== '' && index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
                updateHiddenInput();
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && e.target.value === '' && index > 0) {
                    inputs[index - 1].focus();
                }
            });

            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text').slice(0, 6).split('');
                if (pastedData.some(isNaN)) return; 
                pastedData.forEach((char, i) => {
                    if (inputs[i]) inputs[i].value = char;
                });
                const focusIndex = pastedData.length < 6 ? pastedData.length : 5;
                if(inputs[focusIndex]) inputs[focusIndex].focus();
                updateHiddenInput();
            });
        });
        // Akhir logika input OTP

        // Verifikasi OTP
        async function verifyOtp() {
            updateHiddenInput();
            const otpCode = hiddenInput.value;
            hideError();

            if (otpCode.length !== 6) {
                showError("Format OTP harus 6 digit angka.");
                return;
            }

            try {
                const response = await axios.post('/api/verify-otp', {
                    email: userEmail,
                    otp: otpCode
                });
                localStorage.setItem('auth_token', response.data.token);
                window.location.href = "/dashboard";
            } catch (error) {
                showError(error.response?.data?.message || "Kode OTP salah atau kedaluwarsa.");
            }
        }

        function cancelOtp() {
            document.getElementById('step-otp').classList.add('hidden');
            document.getElementById('step-email').classList.remove('hidden');
            
            // Bersihkan semua input OTP
            inputs.forEach(input => input.value = '');
            hiddenInput.value = '';
    
            const btn = document.getElementById('btn-request');
            btn.innerText = "Kirim Kode OTP";
            btn.disabled = false;
    
            hideError();
        }
    </script>
</body>
</html>