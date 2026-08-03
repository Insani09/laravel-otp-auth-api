<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Rahasia</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</head>
<body class="bg-gray-50 flex items-center justify-center h-screen">

    <div class="bg-white p-8 rounded-xl shadow-lg w-[450px] text-center">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">🎉 Selamat Datang!</h1>
        <p class="text-gray-500 mb-6">Anda berhasil masuk ke halaman terlindungi.</p>

        <div id="user-info" class="bg-blue-50 p-4 rounded-lg text-left mb-6 border border-blue-100">
            <p class="text-sm text-gray-600"><strong>Nama:</strong> <span id="name">-</span></p>
            <p class="text-sm text-gray-600"><strong>Email:</strong> <span id="email">-</span></p>
        </div>

        <button onclick="logout()" class="w-full bg-red-500 text-white py-2 rounded-lg hover:bg-red-600 transition font-medium">Keluar (Logout)</button>
    </div>

    <script>
        // --- CEK PENGAMANAN TOKEN SAAT HALAMAN DIBUKA ---
        const token = localStorage.getItem('auth_token');

        if (!token) {
            // Kalau tidak ada token, tendang balik ke halaman login! (Anti-Bypass)
            alert("Akses ditolak! Silakan login terlebih dahulu.");
            window.location.href = "/";
        }

        // Ambil data user dari API Dashboard yang dikunci Sanctum
        async function loadDashboard() {
            try {
                const response = await axios.get('/api/dashboard', {
                    headers: {
                        'Authorization': `Bearer ${token}` // Kirim token pengaman
                    }
                });

                // Tampilkan data user ke layar
                document.getElementById('name').innerText = response.data.user.name;
                document.getElementById('email').innerText = response.data.user.email;
            } catch (error) {
                // Jika token palsu / expired
                alert("Sesi telah habis atau token tidak valid.");
                localStorage.removeItem('auth_token');
                window.location.href = "/";
            }
        }

        function logout() {
            // Hapus token pengaman dari browser
            localStorage.removeItem('auth_token');
            window.location.href = "/";
        }

        // Jalankan fungsi load saat halaman siap
        loadDashboard();
    </script>
</body>
</html>