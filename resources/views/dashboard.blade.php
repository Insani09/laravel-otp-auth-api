<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Utama</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 flex items-center justify-center h-screen">

    <div class="bg-slate-900 border border-slate-800 p-8 rounded-2xl shadow-2xl w-[440px]">
        
        <!-- Header Profil -->
        <div class="flex items-center space-x-4 mb-6 pb-6 border-b border-slate-800">
            <div class="w-12 h-12 bg-blue-600/10 border border-blue-500/20 text-blue-400 rounded-xl flex items-center justify-center text-lg font-bold">
                <span id="avatar-initial">U</span>
            </div>
            <div>
                <h1 class="text-base font-bold text-white" id="name">Memuat Data...</h1>
                <p class="text-xs text-slate-400">Sesi Terautentikasi (Sanctum)</p>
            </div>
        </div>

        <!-- Detail Informasi -->
        <div class="space-y-3 mb-6">
            <div class="bg-slate-950 border border-slate-800 p-4 rounded-xl">
                <span class="block text-[10px] uppercase font-semibold text-slate-500 tracking-wider mb-1">Email Terdaftar</span>
                <span class="text-sm font-medium text-slate-300" id="email">-</span>
            </div>
            <div class="bg-emerald-950/30 border border-emerald-900/30 p-3 rounded-xl flex items-center space-x-3 text-emerald-400 text-xs font-medium">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor"  stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                <span>Token Keamanan Bearer Aktif dan Terenkripsi.</span>
            </div>
        </div>

        <!-- Tombol Keluar dengan Ikon -->
        <button onclick="logout()" class="w-full bg-slate-800 hover:bg-red-600/20 border border-slate-700 hover:border-red-500/30 text-slate-300 hover:text-red-400 py-2.5 rounded-xl transition duration-200 font-medium text-sm flex items-center justify-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            <span>Keluar Sesi (Logout)</span>
        </button>
    </div>

    <script>
        const token = localStorage.getItem('auth_token');

        if (!token) {
            alert("Akses ditolak! Silakan login terlebih dahulu.");
            window.location.href = "/";
        }

        async function loadDashboard() {
            try {
                const response = await axios.get('/api/dashboard', {
                    headers: { 'Authorization': `Bearer ${token}` }
                });

                const userName = response.data.user.name;
                const userEmail = response.data.user.email;

                document.getElementById('name').innerText = userName;
                document.getElementById('email').innerText = userEmail;
                document.getElementById('avatar-initial').innerText = userName.charAt(0).toUpperCase();
            } catch (error) {
                alert("Sesi telah habis atau token tidak valid.");
                localStorage.removeItem('auth_token');
                window.location.href = "/";
            }
        }

        async function logout() {
            try {
                await axios.post('/api/logout', {}, {
                    headers: { 'Authorization': `Bearer ${token}` }
                });
            } catch (error) {
                console.warn('Logout API error:', error.response?.data?.message || error.message);
            } finally {
                localStorage.removeItem('auth_token');
                window.location.href = "/";
            }
        }

        loadDashboard();
    </script>
</body>
</html>