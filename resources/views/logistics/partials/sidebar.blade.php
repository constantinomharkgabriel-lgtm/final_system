<aside class="w-64 bg-gray-800 border-r border-gray-700 flex-shrink-0">
    <div class="p-6 border-b border-gray-700">
        <h1 class="text-2xl font-bold text-purple-500">Logistics Portal</h1>
        <p class="text-gray-400 text-sm mt-1">Fleet & Delivery Management</p>
    </div>
    
    <nav class="p-4 space-y-1 overflow-y-auto max-h-[calc(100vh-120px)]">
        <!-- Dashboard -->
        <a href="{{ route('department.logistics.dashboard') }}" 
           class="block px-4 py-2.5 rounded-lg {{ request()->routeIs('department.logistics.dashboard') ? 'bg-purple-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">📊</span> Dashboard
        </a>

        <!-- Logistics Section -->
        <div class="pt-4">
            <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Logistics Management</p>
        </div>
        <a href="{{ route('logistics.drivers.index') }}" 
           class="block px-4 py-2.5 rounded-lg {{ request()->routeIs('logistics.drivers.*') ? 'bg-purple-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">🚗</span> Drivers
        </a>
        <a href="{{ route('logistics.deliveries.index') }}" 
           class="block px-4 py-2.5 rounded-lg {{ request()->routeIs('logistics.deliveries.*') ? 'bg-purple-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">📦</span> Deliveries
        </a>
        <a href="{{ route('logistics.deliveries.schedule') }}" 
           class="block px-4 py-2.5 rounded-lg {{ request()->routeIs('logistics.deliveries.schedule') ? 'bg-purple-600 text-white' : 'text-gray-300 hover:bg-gray-700' }}">
            <span class="mr-2">📅</span> Delivery Schedule
        </a>

        <hr class="my-4 border-gray-600">

        <!-- Account -->
        <a href="{{ route('farmowner.profile') }}" 
           class="block px-4 py-2.5 rounded-lg text-gray-300 hover:bg-gray-700">
            <span class="mr-2">⚙️</span> Profile
        </a>

        <form method="POST" action="{{ route('farmowner.logout') }}">
            @csrf
            <button type="submit" class="w-full px-4 py-2.5 text-left text-gray-300 hover:bg-red-600 hover:text-white rounded-lg">
                <span class="mr-2">🚪</span> Logout
            </button>
        </form>
    </nav>
</aside>
