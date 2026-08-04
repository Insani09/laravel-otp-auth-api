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
        
        <!-- Header Ikon SVG (Gempok Keamanan) -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-12 h-12 bg-blue-600/10 border border-blue-500/20 rounded-xl text-blue-400 mb-3 shadow-inner">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <h2 class="text-xl font-bold tracking-tight text-white">Autentikasi Akun</h2>
            <p class="text-xs text-slate-400 mt-1">Sistem Masuk Aman Berbasis Verifikasi OTP</p>
        </div>

        <!-- STEP 1: Form Input Email -->
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
            <button onclick="requestOtp()" id="btn-request" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-medium py-2.5 rounded-xl transition duration-200 shadow-md shadow-blue-900/20 text-sm">
                Kirim Kode OTP
            </button>
        </div>

        <!-- STEP 2: Form Input OTP (Hidden by default) -->
        <div id="step-otp" class="hidden space-y-4">
            <div class="bg-slate-950 border border-slate-800 p-3 rounded-xl text-center">
                <p class="text-xs text-slate-400">Kode OTP telah dikirimkan ke:</p>
                <span id="lbl-email" class="font-semibold text-xs text-blue-400"></span>
            </div>
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1.5">Masukkan 6 Digit OTP</label>
                <input type="text" id="otp" maxlength="6" class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-center tracking-[0.4em] text-lg font-bold focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition placeholder:tracking-normal placeholder:text-sm placeholder:font-normal placeholder:text-slate-600 text-slate-200" placeholder="------">
            </div>
            <button onclick="verifyOtp()" id="btn-verify" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-medium py-2.5 rounded-xl transition duration-200 shadow-md shadow-emerald-900/20 text-sm">
                Verifikasi & Masuk
            </button>
            <!-- Tombol Batal / Kembali (Sudah diperbaiki) -->
            <div class="text-center mt-5">
                <button type="button" onclick="cancelOtp()" class="text-sm text-slate-400 hover:text-slate-200 transition font-medium flex items-center justify-center w-full space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span>Salah ketik email? Kembali</span>
                </button>
            </div>
</div>
        </div>

        <!-- Error Message Box -->
        <div id="error-box" class="hidden mt-4 p-3 bg-red-950/40 border border-red-900/50 rounded-xl text-red-400 text-xs text-center font-medium">
            <span id="error-msg"></span>
        </div>
    </div>

    <script>
        let userEmail = "";

        function showError(message) {
            const box = document.getElementById('error-box');
            const msg = document.getElementById('error-msg');
            msg.innerText = message;
            box.classList.remove('hidden');
        }

        function hideError() {
            document.getElementById('error-box').classList.add('hidden');
        }

        async function requestOtp() {
            userEmail = document.getElementById('email').value;
            const btn = document.getElementById('btn-request');
            hideError();

            if (!userEmail) {
                showError("Alamat email wajib diisi.");
                return;
            }

            btn.innerText = "Memproses...";
            btn.disabled = true;

            try {
                await axios.post('/api/request-otp', { email: userEmail });
                document.getElementById('step-email').classList.add('hidden');
                document.getElementById('step-otp').classList.remove('hidden');
                document.getElementById('lbl-email').innerText = userEmail;
            } catch (error) {
                showError(error.response?.data?.message || "Gagal memproses permintaan.");
                btn.innerText = "Kirim Kode OTP";
                btn.disabled = false;
            }
        }

        async function verifyOtp() {
            const otpCode = document.getElementById('otp').value;
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
            document.getElementById('otp').value = '';
    
            const btn = document.getElementById('btn-request');
            btn.innerText = "Kirim Kode OTP";
            btn.disabled = false;
    
            hideError();
        }
    </script>
</body>
</html>