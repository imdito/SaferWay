<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SaferWay - Yogyakarta</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .leaflet-routing-container {
            display: none !important;
        }

        .custom-scroll::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
    </style>
</head>

<body class="bg-gray-100 h-screen flex flex-col overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <div class="flex flex-1 relative overflow-hidden">

        <div class="absolute top-4 left-4 z-[1000] w-[380px] bg-white rounded-xl shadow-2xl border border-gray-200 flex flex-col max-h-[calc(100vh-100px)]"
            id="sidebar-panel">
            <div class="p-5">
                <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i data-lucide="navigation" class="w-5 h-5 text-blue-600"></i> Cari Rute Aman
                </h2>

                <div class="space-y-3 relative">
                    <div class="absolute left-[18px] top-8 bottom-8 w-0.5 bg-gray-300 z-0"></div>

                    <div class="relative z-10">
                        <div class="flex items-center gap-2">
                            <i data-lucide="circle-dot" class="w-5 h-5 text-green-600 bg-white"></i>
                            <div class="flex-1 flex items-center relative">
                                <input type="text" id="start-input" placeholder="Titik Awal (Klik Peta)" readonly
                                    class="w-full pl-3 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                <button onclick="useCurrentLocation()"
                                    class="absolute right-2 p-1 text-gray-400 hover:text-blue-600 transition"
                                    title="Gunakan Lokasi Saya"><i data-lucide="crosshair" class="w-4 h-4"></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="relative z-10">
                        <div class="flex items-center gap-2">
                            <i data-lucide="map-pin" class="w-5 h-5 text-red-600 bg-white"></i>
                            <input type="text" id="end-input" placeholder="Tujuan (Klik Kanan Peta)" readonly
                                class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                    </div>
                </div>

                <div class="mt-5">
                    <label class="text-xs font-bold text-gray-500 uppercase mb-2 block">Preferensi Rute</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button onclick="setMode('fastest')" id="btn-fastest"
                            class="flex items-center justify-center gap-2 py-2 px-3 rounded-lg border border-gray-200 text-sm font-bold text-blue-600 bg-blue-50 border-blue-200 transition"><i
                                data-lucide="zap" class="w-4 h-4"></i> Tercepat</button>
                        <button onclick="setMode('safer')" id="btn-safer"
                            class="flex items-center justify-center gap-2 py-2 px-3 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 transition"><i
                                data-lucide="shield-check" class="w-4 h-4"></i> Teraman</button>
                    </div>
                </div>

                <div id="safer-filters"
                    class="hidden mt-3 bg-orange-50 p-3 rounded-lg border border-orange-100 animate-fade-in">
                    <p class="text-xs font-bold text-orange-800 mb-2">Hindari Area:</p>
                    <div class="space-y-2">
                        <label
                            class="flex items-center gap-2 cursor-pointer p-1 rounded transition hover:bg-white"><input
                                type="checkbox" class="risk-filter accent-red-600 w-4 h-4" value="Bahaya" checked><span
                                class="w-2 h-2 rounded-full bg-red-600"></span><span
                                class="text-xs font-medium text-gray-700">Bahaya (Merah)</span></label>
                        <label
                            class="flex items-center gap-2 cursor-pointer p-1 rounded transition hover:bg-white"><input
                                type="checkbox" class="risk-filter accent-orange-500 w-4 h-4" value="Rawan"
                                checked><span class="w-2 h-2 rounded-full bg-orange-500"></span><span
                                class="text-xs font-medium text-gray-700">Rawan (Oranye)</span></label>
                        <label
                            class="flex items-center gap-2 cursor-pointer p-1 rounded transition hover:bg-white"><input
                                type="checkbox" class="risk-filter accent-yellow-500 w-4 h-4" value="Siaga"><span
                                class="w-2 h-2 rounded-full bg-yellow-500"></span><span
                                class="text-xs font-medium text-gray-700">Siaga (Kuning)</span></label>
                    </div>
                </div>

                <button onclick="calculateRoute()"
                    class="mt-5 w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-3 rounded-xl shadow-lg transition transform active:scale-95 flex items-center justify-center gap-2"><i
                        data-lucide="search" class="w-4 h-4"></i> Cari Rute Sekarang</button>

                <div id="route-info" class="hidden mt-4 pt-4 border-t border-gray-100">
                    <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg border border-gray-200">
                        <div class="text-center">
                            <div class="text-xs text-gray-500">Jarak</div>
                            <div class="font-bold text-gray-800 text-lg" id="info-dist">-</div>
                        </div>
                        <div class="h-8 w-px bg-gray-300"></div>
                        <div class="text-center">
                            <div class="text-xs text-gray-500">Waktu</div>
                            <div class="font-bold text-gray-800 text-lg" id="info-time">-</div>
                        </div>
                    </div>
                    <div id="safety-alert"
                        class="hidden mt-3 bg-red-50 border border-red-100 p-3 rounded-lg flex items-start gap-3">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5"></i>
                        <div>
                            <h4 class="text-xs font-bold text-red-700">Peringatan Keamanan</h4>
                            <p class="text-[11px] text-red-600 leading-tight mt-1">Rute ini melewati <span
                                    id="conflict-count" class="font-bold underline">0</span> titik rawan.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="map" class="flex-1 h-full z-0 bg-slate-200"></div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    <script>lucide.createIcons();</script>

    <script src="../assets/mapScript.js"></script>
</body>

</html>