<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login OTP - Sistem Keamanan</title>
    <!-- Tailwind CSS untuk styling cepat -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Axios untuk request API -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">

    <div class="bg-white p-8 rounded-xl shadow-lg w-96">
        <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">🔐 Login OTP</h2>

        <!-- STEP 1: Form Input Email -->
        <div id="step-email">
            <label class="block text-sm font-medium text-gray-700 mb-2">Email Kamu</label>
            <input type="email" id="email" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 mb-4" placeholder="nama@gmail.com">
            <button onclick="requestOtp()" id="btn-request" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">Kirim Kode OTP</button>
        </div>

        <!-- STEP 2: Form Input OTP (Disembunyikan dulu) -->
        <div id="step-otp" class="hidden">
            <p class="text-sm text-gray-600 mb-3 text-center">Kode OTP telah dikirim ke <span id="lbl-email" class="font-semibold text-gray-800"></span></p>
            <label class="block text-sm font-medium text-gray-700 mb-2">Masukkan 6 Digit OTP</label>
            <input type="text" id="otp" maxlength="6" class="w-full px-4 py-2 border rounded-lg text-center tracking-widest text-xl font-bold focus:outline-none focus:ring-2 focus:ring-green-500 mb-4" placeholder="------">
            <button onclick="verifyOtp()" id="btn-verify" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 transition">Verifikasi & Masuk</button>
        </div>

        <p id="error-msg" class="text-red-500 text-sm mt-4 text-center font-medium"></p>
    </div>

    <script>
        let userEmail = "";

        async function requestOtp() {
            userEmail = document.getElementById('email').value;
            const btn = document.getElementById('btn-request');
            const errorMsg = document.getElementById('error-msg');
            errorMsg.innerText = "";

            if (!userEmail) {
                errorMsg.innerText = "Email tidak boleh kosong!";
                return;
            }

            btn.innerText = "Mengirim...";
            btn.disabled = true;

            try {
                // Panggil API Laravel kita
                const response = await axios.post('/api/request-otp', { email: userEmail });
                
                // Jika sukses, pindah ke tampilan input OTP
                document.getElementById('step-email').classList.add('hidden');
                document.getElementById('step-otp').classList.remove('hidden');
                document.getElementById('lbl-email').innerText = userEmail;
            } catch (error) {
                errorMsg.innerText = error.response?.data?.message || "Terjadi kesalahan sistem.";
                btn.innerText = "Kirim Kode OTP";
                btn.disabled = false;
            }
        }

        async function verifyOtp() {
            const otpCode = document.getElementById('otp').value;
            const errorMsg = document.getElementById('error-msg');
            errorMsg.innerText = "";

            if (otpCode.length !== 6) {
                errorMsg.innerText = "Kode OTP harus 6 digit angka!";
                return;
            }

            try {
                // Panggil API Verifikasi
                const response = await axios.post('/api/verify-otp', {
                    email: userEmail,
                    otp: otpCode
                });

                // --- PENGAMANAN TOKEN (ENKRIPSI SEDERHANA / LOCALSTORAGE) ---
                const token = response.data.token;
                
                // Simpan token ke localStorage yang diamankan
                localStorage.setItem('auth_token', token);

                // Redirect ke halaman dashboard
                window.location.href = "/dashboard";

            } catch (error) {
                errorMsg.innerText = error.response?.data?.message || "OTP salah atau kedaluwarsa.";
            }
        }
    </script>
</body>
</html>