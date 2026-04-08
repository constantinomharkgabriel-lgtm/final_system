<x-guest-layout>
    <div class="w-full flex flex-col bg-[#1a202c]">
        <div class="w-full bg-[#111827] border border-gray-700 rounded-xl shadow-2xl flex flex-col">
            
            <!-- Header -->
            <div class="bg-gradient-to-b from-[#111827] to-[#0f1419] px-4 sm:px-8 lg:px-12 py-4 sm:py-6 border-b border-gray-700">
                <h2 class="text-xl sm:text-3xl lg:text-4xl font-black text-[#4fd1c5] mb-1 uppercase tracking-widest text-center">
                    Farm Owner Registration
                </h2>
                <p class="text-gray-400 text-xs sm:text-sm lg:text-base text-center uppercase font-bold tracking-tighter">Register your farm with our agricultural marketplace</p>
            </div>

            <!-- Content -->
            <div class="overflow-auto w-full">
                <div class="px-4 sm:px-8 lg:px-12 py-6 w-full">
                    @if ($errors->any())
                    <div class="mb-4 p-3 sm:p-4 bg-red-900/40 border-l-4 border-red-500 rounded-lg">
                        <p class="text-red-300 font-bold mb-2 text-xs sm:text-sm">⚠ Registration Error</p>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                            <li class="text-red-200 text-xs sm:text-sm">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    @if (session('message'))
                    <div class="mb-4 p-3 sm:p-4 bg-green-900/40 border-l-4 border-green-500 rounded-lg">
                        <p class="text-green-200 font-bold mb-1 text-xs sm:text-sm">✓ Success</p>
                        <p class="text-green-200 text-xs sm:text-sm">{{ session('message') }}</p>
                    </div>
                    @endif

                    <form action="{{ route('client.request.store') }}" method="POST" enctype="multipart/form-data" class="space-y-3 sm:space-y-4">
                        @csrf
                        
                        <!-- Basic Information Section - Full Width -->
                        <div class="bg-gray-800/50 border border-gray-700 rounded-lg sm:rounded-xl p-3 sm:p-4 lg:p-6">
                            <h3 class="text-xs sm:text-sm lg:text-base font-bold text-[#4fd1c5] mb-2 sm:mb-3 lg:mb-4 uppercase tracking-wide">Basic Information</h3>
                            <div class="space-y-2 sm:space-y-3">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3 lg:gap-4">
                                    <div>
                                        <label class="block text-gray-400 text-xs font-black uppercase mb-0.5 sm:mb-1 ml-1">Owner Name</label>
                                        <input type="text" name="owner_name" value="{{ old('owner_name') }}" 
                                               class="w-full px-2 py-1 sm:px-3 sm:py-1.5 lg:px-4 lg:py-2 bg-[#1a202c] border border-gray-700 text-white rounded text-xs sm:text-sm lg:text-base focus:ring-[#4fd1c5] focus:border-[#4fd1c5] transition" 
                                               placeholder="Full name" required>
                                        @error('owner_name')<p class="text-red-400 text-xs mt-0.5">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label class="block text-gray-400 text-xs font-black uppercase mb-0.5 sm:mb-1 ml-1">Email</label>
                                        <input type="email" name="email" value="{{ old('email') }}" 
                                               class="w-full px-2 py-1 sm:px-3 sm:py-1.5 lg:px-4 lg:py-2 bg-[#1a202c] border border-gray-700 text-white rounded text-xs sm:text-sm lg:text-base focus:ring-[#4fd1c5] focus:border-[#4fd1c5] transition" 
                                               placeholder="email@domain.com" required>
                                        @error('email')<p class="text-red-400 text-xs mt-0.5">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label class="block text-gray-400 text-xs font-black uppercase mb-0.5 sm:mb-1 ml-1">Farm Name</label>
                                        <input type="text" name="farm_name" value="{{ old('farm_name') }}" 
                                               class="w-full px-2 py-1 sm:px-3 sm:py-1.5 lg:px-4 lg:py-2 bg-[#1a202c] border border-gray-700 text-white rounded text-xs sm:text-sm lg:text-base focus:ring-[#4fd1c5] focus:border-[#4fd1c5] transition" 
                                               placeholder="Farm name" required>
                                        @error('farm_name')<p class="text-red-400 text-xs mt-0.5">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label class="block text-gray-400 text-xs font-black uppercase mb-0.5 sm:mb-1 ml-1">Password</label>
                                        <input type="password" name="password" 
                                               class="w-full px-2 py-1 sm:px-3 sm:py-1.5 lg:px-4 lg:py-2 bg-[#1a202c] border border-gray-700 text-white rounded text-xs sm:text-sm lg:text-base focus:ring-[#4fd1c5] focus:border-[#4fd1c5] transition" 
                                               placeholder="Min 8 chars" required>
                                        <p class="text-xs text-gray-500 mt-0.5">8+ chars</p>
                                        @error('password')<p class="text-red-400 text-xs mt-0.5">{{ $message }}</p>@enderror
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-gray-400 text-xs font-black uppercase mb-0.5 sm:mb-1 ml-1">Confirm Password</label>
                                        <input type="password" name="password_confirmation" 
                                               class="w-full px-2 py-1 sm:px-3 sm:py-1.5 lg:px-4 lg:py-2 bg-[#1a202c] border border-gray-700 text-white rounded text-xs sm:text-sm lg:text-base focus:ring-[#4fd1c5] focus:border-[#4fd1c5] transition" 
                                               placeholder="Confirm" required>
                                        @error('password_confirmation')<p class="text-red-400 text-xs mt-0.5">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Location and Documents - Side by Side -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 sm:gap-4">
                            <!-- Farm Location Section -->
                            <div class="bg-gray-800/50 border border-gray-700 rounded-lg sm:rounded-xl p-3 sm:p-4 lg:p-6">
                                <h3 class="text-xs sm:text-sm lg:text-base font-bold text-[#4fd1c5] mb-2 sm:mb-3 lg:mb-4 uppercase tracking-wide">Location</h3>
                                <div class="space-y-2">
                                    <div>
                                        <label class="block text-gray-400 text-xs font-black uppercase mb-0.5 ml-1">Location Description</label>
                                        <textarea name="farm_location" rows="2" 
                                                  class="w-full px-2 py-1 sm:px-3 sm:py-1.5 lg:px-4 lg:py-2 bg-[#1a202c] border border-gray-700 text-white rounded text-xs sm:text-sm focus:ring-[#4fd1c5] focus:border-[#4fd1c5] transition resize-none" 
                                                  placeholder="Barangay, City" required>{{ old('farm_location') }}</textarea>
                                        @error('farm_location')<p class="text-red-400 text-xs mt-0.5">{{ $message }}</p>@enderror
                                    </div>

                                    <div id="map" class="w-full h-32 sm:h-40 lg:h-48 rounded border-2 border-gray-700 bg-gray-900 overflow-hidden shadow"></div>

                                    <div class="bg-cyan-900/30 border border-cyan-600/50 text-cyan-300 p-1.5 sm:p-2 rounded text-xs">
                                        <p><span class="font-bold">💡</span> Click to pin in Cavite</p>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-gray-400 text-xs font-black uppercase mb-0.5 ml-1">Lat</label>
                                            <input type="number" name="latitude" step="0.00000001" value="{{ old('latitude') }}" 
                                                   id="latitude" class="w-full px-2 py-1 sm:px-3 sm:py-1.5 bg-[#1a202c] border border-gray-700 text-white rounded text-xs focus:ring-[#4fd1c5] focus:border-[#4fd1c5] transition" 
                                                   placeholder="10-15" required readonly>
                                            <p class="text-xs text-gray-500 mt-0.5">10-15</p>
                                        </div>
                                        <div>
                                            <label class="block text-gray-400 text-xs font-black uppercase mb-0.5 ml-1">Lng</label>
                                            <input type="number" name="longitude" step="0.00000001" value="{{ old('longitude') }}" 
                                                   id="longitude" class="w-full px-2 py-1 sm:px-3 sm:py-1.5 bg-[#1a202c] border border-gray-700 text-white rounded text-xs focus:ring-[#4fd1c5] focus:border-[#4fd1c5] transition" 
                                                   placeholder="119-124" required readonly>
                                            <p class="text-xs text-gray-500 mt-0.5">119-124</p>
                                        </div>
                                    </div>
                                </div>
                        </div>
                            </div>

                            <!-- Required Documents Section -->
                            <div class="bg-gray-800/50 border border-gray-700 rounded-lg sm:rounded-xl p-3 sm:p-4 lg:p-6">
                                <h3 class="text-xs sm:text-sm lg:text-base font-bold text-[#4fd1c5] mb-2 sm:mb-3 uppercase tracking-wide">Documents</h3>
                                <div class="space-y-2 sm:space-y-3">
                                <!-- Personal & Business -->
                                <div>
                                    <h5 class="text-xs font-black text-gray-300 mb-1 uppercase tracking-tight">Personal & Business</h5>
                                    <div class="space-y-1">
                                        <input type="file" name="valid_id" accept="image/jpeg,image/png" 
                                               class="block w-full text-xs text-gray-400 cursor-pointer file:mr-2 file:py-0.5 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-[#4fd1c5] file:text-[#1a202c] file:cursor-pointer hover:file:bg-cyan-400 transition" required>
                                        @error('valid_id')<p class="text-red-400 text-xs">{{ $message }}</p>@enderror
                                        <input type="file" name="business_permit" accept="application/pdf,image/jpeg,image/png" 
                                               class="block w-full text-xs text-gray-400 cursor-pointer file:mr-2 file:py-0.5 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-[#4fd1c5] file:text-[#1a202c] file:cursor-pointer hover:file:bg-cyan-400 transition" required>
                                        @error('business_permit')<p class="text-red-400 text-xs">{{ $message }}</p>@enderror
                                    </div>
                                </div>

                                <!-- Local Government -->
                                <div>
                                    <h5 class="text-xs font-black text-gray-300 mb-1 uppercase tracking-tight">Local Government</h5>
                                    <div class="space-y-1">
                                        <input type="file" name="barangay_clearance" 
                                               class="block w-full text-xs text-gray-400 cursor-pointer file:mr-2 file:py-0.5 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-[#4fd1c5] file:text-[#1a202c] file:cursor-pointer hover:file:bg-cyan-400 transition" required>
                                        @error('barangay_clearance')<p class="text-red-400 text-xs">{{ $message }}</p>@enderror
                                        <input type="file" name="mayor_bir_registration" 
                                               class="block w-full text-xs text-gray-400 cursor-pointer file:mr-2 file:py-0.5 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-[#4fd1c5] file:text-[#1a202c] file:cursor-pointer hover:file:bg-cyan-400 transition" required>
                                        @error('mayor_bir_registration')<p class="text-red-400 text-xs">{{ $message }}</p>@enderror
                                    </div>
                                </div>

                                <!-- Environmental -->
                                <div>
                                    <h5 class="text-xs font-black text-gray-300 mb-1 uppercase tracking-tight">Environmental & Zoning</h5>
                                    <div class="space-y-1">
                                        <input type="file" name="ecc_certificate" 
                                               class="block w-full text-xs text-gray-400 cursor-pointer file:mr-2 file:py-0.5 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-[#4fd1c5] file:text-[#1a202c] file:cursor-pointer hover:file:bg-cyan-400 transition" required>
                                        @error('ecc_certificate')<p class="text-red-400 text-xs">{{ $message }}</p>@enderror
                                        <input type="file" name="locational_clearance" 
                                               class="block w-full text-xs text-gray-400 cursor-pointer file:mr-2 file:py-0.5 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-[#4fd1c5] file:text-[#1a202c] file:cursor-pointer hover:file:bg-cyan-400 transition" required>
                                        @error('locational_clearance')<p class="text-red-400 text-xs">{{ $message }}</p>@enderror
                                        <input type="file" name="bai_registration" 
                                               class="block w-full text-xs text-gray-400 cursor-pointer file:mr-2 file:py-0.5 file:px-2 file:rounded file:border-0 file:text-xs file:font-semibold file:bg-[#4fd1c5] file:text-[#1a202c] file:cursor-pointer hover:file:bg-cyan-400 transition" required>
                                        @error('bai_registration')<p class="text-red-400 text-xs">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                            </div>
                            </div>
                        </div>

                        <!-- Submit Button - Full Width -->
                        <button type="submit" class="w-full bg-[#4fd1c5] hover:bg-[#38b2ac] text-[#111827] font-black py-2 sm:py-2.5 rounded transition duration-300 uppercase tracking-widest shadow text-xs sm:text-sm lg:text-base">
                            Submit for Verification
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaflet CSS & JS for Map -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

    <script>
        // Initialize map centered on Cavite - delay to ensure DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            if (!document.getElementById('map')) return;
            
            const map = L.map('map').setView([14.3604, 120.8863], 13);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(map);

            const caviteBounds = L.latLngBounds(
                [13.8, 119.5],
                [14.8, 121.2]
            );

            map.setMaxBounds(caviteBounds.pad(0.1));

            let marker = null;
            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');

            if (latInput.value && lngInput.value) {
                const lat = parseFloat(latInput.value);
                const lng = parseFloat(lngInput.value);
                marker = L.marker([lat, lng], {
                    icon: L.icon({
                        iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
                        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize: [41, 41]
                    })
                }).addTo(map);
                map.setView([lat, lng], 14);
            }

            map.on('click', function(e) {
                const lat = e.latlng.lat;
                const lng = e.latlng.lng;

                if (lat < 10 || lat > 15 || lng < 119 || lng > 124) {
                    showMapAlert('⚠ Location outside Cavite area (10-15°N, 119-124°E)');
                    return;
                }

                latInput.value = lat.toFixed(8);
                lngInput.value = lng.toFixed(8);

                if (marker) {
                    marker.setLatLng(e.latlng);
                } else {
                    marker = L.marker(e.latlng, {
                        icon: L.icon({
                            iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
                            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
                            iconSize: [25, 41],
                            iconAnchor: [12, 41],
                            popupAnchor: [1, -34],
                            shadowSize: [41, 41]
                        })
                    }).addTo(map);
                }

                map.setView(e.latlng, 14);
                showMapAlert('✓ Location pinned!', 'success');
            });

            function showMapAlert(message, type = 'error') {
                const alertDiv = document.createElement('div');
                alertDiv.className = type === 'success' 
                    ? 'fixed top-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg z-50 font-semibold text-xs sm:text-sm' 
                    : 'fixed top-4 right-4 bg-red-600 text-white px-4 py-2 rounded-lg shadow-lg z-50 font-semibold text-xs sm:text-sm';
                alertDiv.textContent = message;
                document.body.appendChild(alertDiv);
                setTimeout(() => alertDiv.remove(), 3000);
            }
        });
    </script>
</x-guest-layout>