<header class="bg-white shadow-md z-50 h-16 flex-none px-6 flex justify-between items-center border-b border-gray-200">
    <div class="flex items-center gap-3">

        <button id="hamburger-menu" class="md:hidden text-gray-500 hover:text-slate-900 transition">
            <i data-lucide="menu" class="w-6 h-6"></i>
        </button>

        <div class="bg-blue-600 text-white p-2 rounded-lg">
            <i data-lucide="shield" class="w-5 h-5"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-gray-800 leading-none tracking-tight">SaferWay</h1>
            <span class="text-[10px] text-gray-500 font-bold tracking-widest uppercase">YOGYAKARTA</span>
        </div>
    </div>

    <div class="hidden md:flex gap-8 text-sm font-medium text-gray-500">
        <a href="index.php"
            class="hover:text-blue-600 transition pb-1 <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'text-blue-600 border-b-2 border-blue-600' : '' ?>">
            Peta Rute
        </a>
        <a href="form.php"
            class="hover:text-blue-600 transition pb-1 <?= basename($_SERVER['PHP_SELF']) == 'form.php' ? 'text-blue-600 border-b-2 border-blue-600' : '' ?>">
            Lapor Kejadian
        </a>
        <div
            class="flex items-center gap-2 px-3 py-1 bg-green-50 text-green-700 rounded-full text-xs border border-green-200">
            <span class="relative flex h-2 w-2">
                <span
                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
            </span>
            System Online
        </div>
    </div>
</header>

<div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-[1001] hidden transition-opacity backdrop-blur-sm"></div>

<div id="sidebar"
    class="fixed top-0 left-0 h-full w-72 bg-white shadow-2xl z-[1002] transform -translate-x-full transition-transform duration-300 ease-in-out flex flex-col">
    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-slate-50">
        <div class="flex items-center gap-2">
            <div class="bg-blue-600 text-white p-1.5 rounded">
                <i data-lucide="shield" class="w-4 h-4"></i>
            </div>
            <h2 class="font-bold text-slate-800 text-lg">Menu Utama</h2>
        </div>
        <button id="close-sidebar" class="text-gray-400 hover:text-red-500 transition p-1 hover:bg-red-50 rounded-full">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>

    <div class="p-4 space-y-1 flex-1 overflow-y-auto">
        <p class="px-4 text-xs font-bold text-gray-400 uppercase mb-2 mt-2">Navigasi</p>

        <a href="index.php"
            class="flex items-center gap-3 px-4 py-3 rounded-xl transition group <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-slate-900' ?>">
            <i data-lucide="map"
                class="w-5 h-5 <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'text-blue-600' : 'text-gray-400 group-hover:text-blue-500' ?>"></i>
            Peta Rute
        </a>

        <a href="form.php"
            class="flex items-center gap-3 px-4 py-3 rounded-xl transition group <?= basename($_SERVER['PHP_SELF']) == 'form.php' ? 'bg-blue-50 text-blue-700 font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-slate-900' ?>">
            <i data-lucide="file-text"
                class="w-5 h-5 <?= basename($_SERVER['PHP_SELF']) == 'form.php' ? 'text-blue-600' : 'text-gray-400 group-hover:text-blue-500' ?>"></i>
            Lapor Kejahatan
        </a>

        <div class="my-4 border-t border-gray-100"></div>
        <p class="px-4 text-xs font-bold text-gray-400 uppercase mb-2">Lainnya</p>

        <button
            class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-gray-600 hover:bg-gray-50 hover:text-slate-900 transition text-sm group text-left">
            <i data-lucide="info" class="w-4 h-4 text-gray-400 group-hover:text-slate-600"></i> Tentang
        </button>
        <button
            class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-gray-600 hover:bg-gray-50 hover:text-slate-900 transition text-sm group text-left">
            <i data-lucide="help-circle" class="w-4 h-4 text-gray-400 group-hover:text-slate-600"></i> Bantuan
        </button>
    </div>

    <div class="p-4 border-t border-gray-100 bg-gray-50">
        <p class="text-[10px] text-center text-gray-400">&copy; 2025 SaferWay Team</p>
    </div>
</div>

<script>
    // Logic Toggle Sidebar (Vanilla JS)
    const hamBtn = document.getElementById('hamburger-menu');
    const closeBtn = document.getElementById('close-sidebar');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    function toggleSidebar() {
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    }

    if (hamBtn) hamBtn.addEventListener('click', toggleSidebar);
    if (closeBtn) closeBtn.addEventListener('click', toggleSidebar);
    if (overlay) overlay.addEventListener('click', toggleSidebar);

    // Re-init icons kalau dipanggil via AJAX/Include
    if (typeof lucide !== 'undefined') lucide.createIcons();
</script>