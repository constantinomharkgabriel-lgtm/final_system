<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farm Owners - Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-900 text-gray-200">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-800 border-r border-gray-700">
            <div class="p-6 border-b border-gray-700">
                <h1 class="text-2xl font-bold text-orange-500">Poultry Admin</h1>
            </div>
            
            <nav class="p-4 space-y-2">
                <a href="{{ route('superadmin.dashboard') }}" class="block px-4 py-3 hover:bg-gray-700 rounded-lg">Dashboard</a>
                <a href="{{ route('superadmin.farm_owners') }}" class="block px-4 py-3 bg-orange-600 text-white rounded-lg">Farm Owners</a>
                <a href="{{ route('superadmin.orders') }}" class="block px-4 py-3 hover:bg-gray-700 rounded-lg">Orders</a>
                <a href="{{ route('superadmin.monitoring') }}" class="block px-4 py-3 hover:bg-gray-700 rounded-lg">Monitoring</a>
                <a href="{{ route('superadmin.subscriptions') }}" class="block px-4 py-3 hover:bg-gray-700 rounded-lg">Subscriptions</a>
                <a href="{{ route('superadmin.users') }}" class="block px-4 py-3 hover:bg-gray-700 rounded-lg">Users</a>
                <a href="{{ route('superadmin.support.index') }}" class="block px-4 py-3 hover:bg-gray-700 rounded-lg">Support</a>
                <hr class="my-4 border-gray-600">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full px-4 py-3 text-left hover:bg-red-600 rounded-lg">Logout</button>
                </form>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-auto">
            <header class="bg-gray-800 border-b border-gray-700 px-8 py-4">
                <h2 class="text-2xl font-bold">Farm Owners</h2>
                <p class="text-gray-400 text-sm">Manage and verify farm owners</p>
            </header>

            <div class="p-8">
                <!-- Success Message -->
                @if(session('success'))
                <div class="mb-6 px-4 py-3 bg-green-500/20 border border-green-500/50 text-green-400 rounded-lg flex items-center justify-between">
                    <span>{{ session('success') }}</span>
                    <button onclick="this.parentElement.style.display='none'" class="text-green-400 hover:text-green-300">&times;</button>
                </div>
                @endif

                <!-- Error Message -->
                @if(session('error'))
                <div class="mb-6 px-4 py-3 bg-red-500/20 border border-red-500/50 text-red-400 rounded-lg flex items-center justify-between">
                    <span>{{ session('error') }}</span>
                    <button onclick="this.parentElement.style.display='none'" class="text-red-400 hover:text-red-300">&times;</button>
                </div>
                @endif
                @if($farm_owners->count() > 0)
                <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-700 border-b border-gray-600">
                            <tr>
                                <th class="text-left px-6 py-3">Farm Name</th>
                                <th class="text-left px-6 py-3">Owner</th>
                                <th class="text-left px-6 py-3">Valid ID</th>
                                <th class="text-left px-6 py-3">Status</th>
                                <th class="text-left px-6 py-3">Products</th>
                                <th class="text-left px-6 py-3">Orders</th>
                                <th class="text-center px-6 py-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            @foreach($farm_owners as $owner)
                            <tr class="hover:bg-gray-700 transition">
                                <td class="px-6 py-4 font-semibold">{{ $owner->farm_name ?? 'N/A' }}</td>
                                <td class="px-6 py-4">{{ $owner->user?->name ?? 'Unknown' }}</td>
                                <td class="px-6 py-4">
                                    @if($owner->valid_id_url)
                                    <a href="{{ $owner->valid_id_url }}" target="_blank" class="text-blue-400 hover:text-blue-300 underline text-xs">View ID</a>
                                    @else
                                    <span class="text-gray-500 text-xs">No ID</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                                        @if($owner->permit_status === 'approved') bg-green-500/20 text-green-400
                                        @elseif($owner->permit_status === 'pending') bg-yellow-500/20 text-yellow-400
                                        @else bg-red-500/20 text-red-400
                                        @endif">
                                        {{ ucfirst($owner->permit_status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">{{ $owner->products_count ?? 0 }}</td>
                                <td class="px-6 py-4">{{ $owner->orders_count ?? 0 }}</td>
                                <td class="px-6 py-4 text-center space-x-2">
                                    @if($owner->permit_status === 'pending')
                                    <form method="POST" action="{{ route('superadmin.approve_farm_owner', $owner->id) }}" class="inline-block">
                                        @csrf
                                        <button type="submit" class="px-3 py-1 bg-green-600 hover:bg-green-700 rounded text-xs font-medium">Approve</button>
                                    </form>
                                    <button type="button" onclick="openRejectModal({{ $owner->id }}, '{{ $owner->farm_name }}')" class="px-3 py-1 bg-red-600 hover:bg-red-700 rounded text-xs font-medium">Reject</button>
                                    @endif
                                    <a href="{{ route('superadmin.show_farm_owner', $owner->id) }}" class="px-3 py-1 bg-blue-600 hover:bg-blue-700 rounded text-xs font-medium inline-block">View</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="mt-6 flex justify-center">
                    {{ $farm_owners->links('pagination::tailwind') }}
                </div>
                @else
                <div class="text-center py-12">
                    <p class="text-gray-400">No farm owners found</p>
                </div>
                @endif
            </div>
        </main>
    </div>

    <!-- Rejection Modal -->
    <div id="rejectModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-gray-800 border border-gray-700 rounded-lg p-8 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold mb-4 text-white">Reject Farm Owner Registration</h3>
            <p class="text-gray-300 mb-4">Farm: <strong id="farmNameDisplay"></strong></p>
            
            <form id="rejectForm" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Rejection Reason *</label>
                    <textarea 
                        name="reason" 
                        rows="4" 
                        required
                        maxlength="500"
                        class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:ring-2 focus:ring-red-500 focus:border-transparent"
                        placeholder="Explain why this farm owner registration is being rejected..."></textarea>
                    <p class="text-xs text-gray-400 mt-1">Max 500 characters. This reason will be sent to the farm owner via email.</p>
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="closeRejectModal()" class="flex-1 px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg font-medium">
                        Cancel
                    </button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium">
                        Confirm Rejection
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let currentFarmOwnerId = null;

        function openRejectModal(farmOwnerId, farmName) {
            currentFarmOwnerId = farmOwnerId;
            document.getElementById('farmNameDisplay').innerText = farmName;
            document.getElementById('rejectForm').action = `/super-admin/farm-owners/${farmOwnerId}/reject`;
            document.getElementById('rejectForm').reset();
            document.getElementById('rejectModal').classList.remove('hidden');
        }

        function closeRejectModal() {
            currentFarmOwnerId = null;
            document.getElementById('rejectModal').classList.add('hidden');
        }

        // Close modal when clicking outside of it
        document.getElementById('rejectModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeRejectModal();
            }
        });

        // Close modal on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeRejectModal();
            }
        });

        // Handle form submission with loading state
        document.getElementById('rejectForm')?.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerText = 'Processing...';
        });
    </script>
</body>
</html>
