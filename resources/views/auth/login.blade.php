<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Autentikasi Aman - Sistem OTP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
    @include('partials.select2-dark')
</head>
<body class="min-h-screen flex items-center justify-center bg-slate-950 p-4 text-slate-100 selection:bg-blue-600 selection:text-white">

    <div class="w-full max-w-xl border border-slate-800 bg-slate-900/80 backdrop-blur-md p-6 sm:p-8 rounded-2xl shadow-2xl">

        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-12 h-12 bg-blue-600/10 border border-blue-500/20 rounded-xl text-blue-400 mb-3 shadow-inner">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <h2 class="text-xl font-bold tracking-tight text-white">Autentikasi Akun</h2>
            <p class="text-xs text-slate-400 mt-1">Masuk dengan kata sandi atau kode OTP</p>
        </div>

        {{-- ========== STEP LOGIN (terisolasi total dari register) ========== --}}
        <div id="step-login">

            {{-- Tab switcher Password vs OTP --}}
            <div id="login-tabs" class="flex gap-2 mb-5 p-1 bg-slate-950/80 border border-slate-800 rounded-xl">
                <button type="button" id="tab-password" onclick="switchLoginTab('password')" class="flex-1 py-2 text-sm font-medium rounded-lg transition bg-blue-600 text-white">
                    Login Password
                </button>
                <button type="button" id="tab-otp" onclick="switchLoginTab('otp')" class="flex-1 py-2 text-sm font-medium rounded-lg transition text-slate-400 hover:text-slate-200">
                    Login OTP
                </button>
            </div>

            <div id="login-alert" class="hidden mb-4 p-3 rounded-xl text-xs text-center font-medium"></div>

            {{-- Tab 1: Password --}}
            <div id="panel-password" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Alamat Email</label>
                    <input type="email" id="login-email" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 text-sm placeholder-slate-600 transition text-slate-200" placeholder="nama@gmail.com" autocomplete="username">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Kata Sandi</label>
                    <div class="relative">
                        <input type="password" id="login-password" class="w-full pl-4 pr-10 py-2.5 bg-slate-950 border border-slate-800 rounded-xl focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 text-sm placeholder-slate-600 transition text-slate-200" placeholder="Kata sandi Anda" autocomplete="current-password">
                        <button type="button" onclick="togglePassword('login-password', 'icon-login-pass')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-500 hover:text-slate-300 transition">
                            <svg id="icon-login-pass" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <label class="inline-flex items-center gap-2 text-sm text-slate-300">
                    <input type="checkbox" id="remember_password" class="rounded border-slate-700 bg-slate-950 text-blue-500 focus:ring-blue-500">
                    Ingat saya
                </label>
                <button type="button" onclick="loginWithPassword()" id="btn-login-password" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-medium py-2.5 rounded-xl transition duration-200 shadow-md shadow-blue-900/20 text-sm">
                    Masuk
                </button>
                <div class="text-center">
                    <button type="button" onclick="showForgot()" class="text-blue-400 text-xs font-semibold hover:underline">Lupa kata sandi?</button>
                </div>
            </div>

            {{-- Tab 2: OTP --}}
            <div id="panel-otp" class="hidden space-y-4">
                <div id="otp-step-1" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Alamat Email</label>
                        <input type="email" id="otp-email" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 text-sm placeholder-slate-600 transition text-slate-200" placeholder="nama@gmail.com" autocomplete="username">
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-300">
                        <input type="checkbox" id="remember_otp" class="rounded border-slate-700 bg-slate-950 text-blue-500 focus:ring-blue-500">
                        Ingat saya
                    </label>
                    <button type="button" onclick="sendLoginOtp()" id="btn-send-otp" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-medium py-2.5 rounded-xl transition duration-200 shadow-md shadow-blue-900/20 text-sm">
                        Kirim Kode OTP
                    </button>
                </div>

                <div id="otp-step-2" class="hidden space-y-4">
                    <div class="bg-slate-950 border border-slate-800 p-3 rounded-xl text-center">
                        <p class="text-xs text-slate-400">Kode OTP dikirim ke:</p>
                        <span id="lbl-otp-email" class="font-semibold text-xs text-blue-400"></span>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Kode OTP (6 digit)</label>
                        <input type="text" id="otp-code" maxlength="6" inputmode="numeric" pattern="[0-9]*" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 text-sm text-center tracking-[0.4em] font-bold text-slate-200 placeholder-slate-600 transition" placeholder="••••••">
                    </div>
                    <button type="button" onclick="verifyLoginOtp()" id="btn-verify-otp" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-medium py-2.5 rounded-xl transition duration-200 shadow-md shadow-emerald-900/20 text-sm">
                        Verifikasi & Masuk
                    </button>
                    <button type="button" onclick="backToOtpStep1()" class="w-full text-sm text-slate-400 hover:text-slate-200 transition font-medium">
                        Kembali / ganti email
                    </button>
                </div>
            </div>

            {{-- Forgot password (sub-step di dalam step-login) --}}
            <div id="panel-forgot" class="hidden space-y-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Email untuk atur ulang</label>
                    <input type="email" id="forgot-email" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 text-sm placeholder-slate-600 transition text-slate-200" placeholder="nama@gmail.com">
                </div>
                <button type="button" onclick="requestPasswordResetOtp()" id="btn-forgot" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-medium py-2.5 rounded-xl transition duration-200 text-sm">
                    Kirim Kode Reset Kata Sandi
                </button>
                <div class="text-center">
                    <button type="button" onclick="showLoginMain()" class="text-slate-400 text-xs font-semibold hover:underline">Kembali ke Login</button>
                </div>
            </div>

            {{-- Reset password --}}
            <div id="panel-reset" class="hidden space-y-4">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Email</label>
                    <input type="email" id="reset-email" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 text-sm text-slate-200" placeholder="nama@gmail.com">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Kode OTP</label>
                    <input type="text" id="reset-otp" maxlength="6" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 text-sm text-slate-200" placeholder="123456">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Kata Sandi Baru</label>
                    <div class="relative">
                        <input type="password" id="reset-password" class="w-full pl-4 pr-10 py-2.5 bg-slate-950 border border-slate-800 rounded-xl focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 text-sm text-slate-200" placeholder="Minimal 12 karakter">
                        <button type="button" onclick="togglePassword('reset-password', 'icon-reset-pass1')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-500 hover:text-slate-300 transition">
                            <svg id="icon-reset-pass1" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Konfirmasi Kata Sandi Baru</label>
                    <div class="relative">
                        <input type="password" id="reset-password-confirmation" class="w-full pl-4 pr-10 py-2.5 bg-slate-950 border border-slate-800 rounded-xl focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 text-sm text-slate-200" placeholder="Ulangi kata sandi baru">
                        <button type="button" onclick="togglePassword('reset-password-confirmation', 'icon-reset-pass2')" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-500 hover:text-slate-300 transition">
                            <svg id="icon-reset-pass2" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <button type="button" onclick="resetPassword()" id="btn-reset" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-medium py-2.5 rounded-xl transition duration-200 text-sm">
                    Atur Ulang Kata Sandi
                </button>
                <div class="text-center">
                    <button type="button" onclick="showLoginMain()" class="text-slate-400 text-xs font-semibold hover:underline">Kembali ke Login</button>
                </div>
            </div>

            <div id="login-footer" class="text-center mt-5">
                <p class="text-xs text-slate-400">Belum punya akun? <button type="button" onclick="showRegister()" class="text-blue-400 font-semibold hover:underline">Daftar sekarang</button></p>
            </div>
        </div>

        {{-- ========== STEP REGISTER (terisolasi, tidak bercampur dengan login) ========== --}}
        @include('auth.register')
    </div>

    @include('partials.region-cascading')

    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            }
        });

        const apiBaseUrl = "{{ url('/api') }}";
        const authRoutes = {
            register: "{{ route('auth.register') }}",
            login: "{{ route('auth.login') }}",
            otpSend: "{{ route('auth.otp.send') }}",
            otpVerify: "{{ route('auth.otp.verify') }}",
            forgot: "{{ route('auth.forgot') }}",
            reset: "{{ route('auth.reset') }}",
        };
        let otpUserEmail = '';
        let regionApi = null;

        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (!input || !icon) return;

            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>';
            }
        }

        function setAlert(elId, message, type) {
            const box = document.getElementById(elId);
            if (!box) return;
            box.classList.remove('hidden', 'bg-red-950/40', 'border', 'border-red-900/50', 'text-red-400', 'bg-emerald-950/40', 'border-emerald-900/50', 'text-emerald-300');
            if (!message) {
                box.classList.add('hidden');
                box.textContent = '';
                return;
            }
            box.textContent = message;
            if (type === 'success') {
                box.classList.add('bg-emerald-950/40', 'border', 'border-emerald-900/50', 'text-emerald-300');
            } else {
                box.classList.add('bg-red-950/40', 'border', 'border-red-900/50', 'text-red-400');
            }
        }

        function showLoginAlert(message, type) {
            setAlert('login-alert', message, type || 'error');
        }

        function showRegisterAlert(message, type) {
            setAlert('register-alert', message, type || 'error');
        }

        function getApiErrorMessage(xhr) {
            const data = xhr.responseJSON || {};
            const errors = data.errors;
            if (errors && typeof errors === 'object') {
                const messages = Object.values(errors).flat().filter(Boolean);
                if (messages.length) return messages.join(' ');
            }
            return data.message || 'Terjadi kesalahan. Silakan coba lagi.';
        }

        function hideAllLoginPanels() {
            ['panel-password', 'panel-otp', 'panel-forgot', 'panel-reset'].forEach(function (id) {
                document.getElementById(id).classList.add('hidden');
            });
            document.getElementById('login-tabs').classList.add('hidden');
            document.getElementById('login-footer').classList.add('hidden');
        }

        function showLoginMain() {
            hideAllLoginPanels();
            document.getElementById('login-tabs').classList.remove('hidden');
            document.getElementById('login-footer').classList.remove('hidden');
            switchLoginTab(document.getElementById('tab-otp').classList.contains('bg-blue-600') ? 'otp' : 'password');
            showLoginAlert('');
        }

        function switchLoginTab(tab) {
            showLoginAlert('');
            document.getElementById('panel-forgot').classList.add('hidden');
            document.getElementById('panel-reset').classList.add('hidden');
            document.getElementById('login-tabs').classList.remove('hidden');
            document.getElementById('login-footer').classList.remove('hidden');

            const tabPassword = document.getElementById('tab-password');
            const tabOtp = document.getElementById('tab-otp');

            if (tab === 'otp') {
                document.getElementById('panel-password').classList.add('hidden');
                document.getElementById('panel-otp').classList.remove('hidden');
                tabOtp.classList.add('bg-blue-600', 'text-white');
                tabOtp.classList.remove('text-slate-400');
                tabPassword.classList.remove('bg-blue-600', 'text-white');
                tabPassword.classList.add('text-slate-400');
            } else {
                document.getElementById('panel-otp').classList.add('hidden');
                document.getElementById('panel-password').classList.remove('hidden');
                tabPassword.classList.add('bg-blue-600', 'text-white');
                tabPassword.classList.remove('text-slate-400');
                tabOtp.classList.remove('bg-blue-600', 'text-white');
                tabOtp.classList.add('text-slate-400');
            }
        }

        function showForgot() {
            hideAllLoginPanels();
            document.getElementById('panel-forgot').classList.remove('hidden');
            showLoginAlert('');
        }

        function showRegister() {
            document.getElementById('step-login').classList.add('hidden');
            document.getElementById('step-register').classList.remove('hidden');
            showLoginAlert('');
            showRegisterAlert('');
            if (!regionApi) {
                regionApi = window.initRegionCascading({ prefix: '', hiddenPrefix: '', apiBase: apiBaseUrl });
            }
        }

        function showLogin() {
            document.getElementById('step-register').classList.add('hidden');
            document.getElementById('step-login').classList.remove('hidden');
            showRegisterAlert('');
            showLoginMain();
        }

        function passwordClientValid(password) {
            if (password.length < 12) return 'Kata sandi minimal harus 12 karakter.';
            if (!/[A-Za-z]/.test(password) || !/\d/.test(password)) return 'Kata sandi harus mengandung kombinasi huruf dan angka.';
            if (/\s/.test(password)) return 'Kata sandi tidak boleh mengandung spasi.';
            return null;
        }

        function loginWithPassword() {
            const email = $('#login-email').val().trim();
            const password = $('#login-password').val();
            const btn = $('#btn-login-password');
            showLoginAlert('');

            if (!email || !password) {
                showLoginAlert('Alamat email dan kata sandi wajib diisi.');
                return;
            }

            btn.prop('disabled', true).text('Memproses...');

            $.ajax({
                url: authRoutes.login,
                method: 'POST',
                data: {
                    email: email,
                    password: password,
                    remember: $('#remember_password').is(':checked') ? 1 : 0
                },
                success: function (res) {
                    if (res.token) localStorage.setItem('auth_token', res.token);
                    showLoginAlert(res.message || 'Login berhasil.', 'success');
                    window.location.assign(res.redirect || "{{ route('dashboard') }}");
                },
                error: function (xhr) {
                    showLoginAlert(getApiErrorMessage(xhr));
                    btn.prop('disabled', false).text('Masuk');
                }
            });
        }

        function sendLoginOtp() {
            const email = $('#otp-email').val().trim();
            const btn = $('#btn-send-otp');
            showLoginAlert('');

            if (!email) {
                showLoginAlert('Alamat email wajib diisi dengan format yang benar.');
                return;
            }

            btn.prop('disabled', true).text('Mengirim...');

            $.ajax({
                url: authRoutes.otpSend,
                method: 'POST',
                data: { email: email },
                success: function (res) {
                    otpUserEmail = email;
                    $('#lbl-otp-email').text(email);
                    $('#otp-step-1').addClass('hidden');
                    $('#otp-step-2').removeClass('hidden');
                    let msg = res.message || 'Kode OTP telah dikirim.';
                    if (res.otp_debug) {
                        msg += ' (Debug: ' + res.otp_debug + ')';
                    }
                    showLoginAlert(msg, 'success');
                    btn.prop('disabled', false).text('Kirim Kode OTP');
                },
                error: function (xhr) {
                    showLoginAlert(getApiErrorMessage(xhr));
                    btn.prop('disabled', false).text('Kirim Kode OTP');
                }
            });
        }

        function backToOtpStep1() {
            $('#otp-step-2').addClass('hidden');
            $('#otp-step-1').removeClass('hidden');
            $('#otp-code').val('');
            showLoginAlert('');
        }

        function verifyLoginOtp() {
            const otp = $('#otp-code').val().trim();
            const btn = $('#btn-verify-otp');
            showLoginAlert('');

            if (!/^\d{6}$/.test(otp)) {
                showLoginAlert('Kode OTP harus berupa 6 digit angka.');
                return;
            }

            btn.prop('disabled', true).text('Memverifikasi...');

            $.ajax({
                url: authRoutes.otpVerify,
                method: 'POST',
                data: {
                    email: otpUserEmail,
                    otp: otp,
                    remember: $('#remember_otp').is(':checked') ? 1 : 0
                },
                success: function (res) {
                    if (res.token) localStorage.setItem('auth_token', res.token);
                    showLoginAlert(res.message || 'Login berhasil.', 'success');
                    window.location.assign(res.redirect || "{{ route('dashboard') }}");
                },
                error: function (xhr) {
                    showLoginAlert(getApiErrorMessage(xhr));
                    btn.prop('disabled', false).text('Verifikasi & Masuk');
                }
            });
        }

        function requestPasswordResetOtp() {
            const email = $('#forgot-email').val().trim();
            const btn = $('#btn-forgot');
            showLoginAlert('');

            if (!email) {
                showLoginAlert('Alamat email wajib diisi dengan format yang benar.');
                return;
            }

            btn.prop('disabled', true).text('Memproses...');

            $.ajax({
                url: authRoutes.forgot,
                method: 'POST',
                data: { email: email },
                success: function (res) {
                    $('#reset-email').val(email);
                    hideAllLoginPanels();
                    $('#panel-reset').removeClass('hidden');
                    let msg = res.message || 'Kode reset telah dikirim.';
                    if (res.otp_debug) msg += ' (Debug: ' + res.otp_debug + ')';
                    showLoginAlert(msg, 'success');
                },
                error: function (xhr) {
                    showLoginAlert(getApiErrorMessage(xhr));
                },
                complete: function () {
                    btn.prop('disabled', false).text('Kirim Kode Reset Kata Sandi');
                }
            });
        }

        function resetPassword() {
            const email = $('#reset-email').val().trim();
            const otp = $('#reset-otp').val().trim();
            const password = $('#reset-password').val();
            const password_confirmation = $('#reset-password-confirmation').val();
            const btn = $('#btn-reset');
            showLoginAlert('');

            if (!email || !otp || !password || !password_confirmation) {
                showLoginAlert('Semua kolom wajib diisi untuk mengatur ulang kata sandi.');
                return;
            }
            if (password !== password_confirmation) {
                showLoginAlert('Konfirmasi kata sandi tidak cocok. Silakan periksa kembali.');
                return;
            }
            const pwdErr = passwordClientValid(password);
            if (pwdErr) {
                showLoginAlert(pwdErr);
                return;
            }

            btn.prop('disabled', true).text('Memproses...');

            $.ajax({
                url: authRoutes.reset,
                method: 'POST',
                data: {
                    email: email,
                    otp: otp,
                    password: password,
                    password_confirmation: password_confirmation
                },
                success: function (res) {
                    showLoginAlert(res.message || 'Kata sandi berhasil diubah.', 'success');
                    showLoginMain();
                },
                error: function (xhr) {
                    showLoginAlert(getApiErrorMessage(xhr));
                },
                complete: function () {
                    btn.prop('disabled', false).text('Atur Ulang Kata Sandi');
                }
            });
        }

        function registerUser() {
            if (regionApi) regionApi.syncHidden();

            const name = $('#reg-name').val().trim();
            const email = $('#reg-email').val().trim();
            const password = $('#reg-password').val();
            const password_confirmation = $('#reg-password-confirmation').val();
            const negara = $('#reg-negara').val().trim();
            const provinsi = $('#reg-provinsi').val().trim();
            const kota = $('#reg-kota').val().trim();
            const kecamatan = $('#reg-kecamatan').val().trim();
            const btn = $('#btn-register');

            showRegisterAlert('');

            if (!name || !negara) {
                showRegisterAlert('Nama lengkap dan negara wajib diisi.');
                return;
            }
            if (!email) {
                showRegisterAlert('Alamat email wajib diisi dengan format yang benar.');
                return;
            }
            if (password !== password_confirmation) {
                showRegisterAlert('Konfirmasi kata sandi tidak cocok. Silakan periksa kembali.');
                return;
            }
            const pwdErr = passwordClientValid(password);
            if (pwdErr) {
                showRegisterAlert(pwdErr);
                return;
            }

            btn.prop('disabled', true).text('Mendaftarkan...');

            $.ajax({
                url: authRoutes.register,
                method: 'POST',
                data: {
                    name: name,
                    email: email,
                    password: password,
                    password_confirmation: password_confirmation,
                    negara: negara,
                    provinsi: provinsi || null,
                    kota: kota || null,
                    kecamatan: kecamatan || null
                },
                success: function (res) {
                    showLogin();
                    showLoginAlert(res.message || 'Akun berhasil dibuat. Silakan masuk.', 'success');
                    btn.prop('disabled', false).text('Buat Akun');
                },
                error: function (xhr) {
                    showRegisterAlert(getApiErrorMessage(xhr));
                    btn.prop('disabled', false).text('Buat Akun');
                }
            });
        }

        $(function () {
            switchLoginTab('password');
        });
    </script>
</body>
</html>
