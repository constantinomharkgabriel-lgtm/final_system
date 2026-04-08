<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farm Owner Registration - Poultry System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-900 min-h-screen py-8 px-4">
    <div class="max-w-4xl mx-auto">
        <div class="bg-gray-800 border border-gray-700 rounded-lg shadow-2xl p-6 md:p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl md:text-4xl font-bold text-orange-500">Register Your Farm</h1>
                <p class="text-gray-400 mt-2">Join our agricultural marketplace</p>
            </div>

            @if ($errors->any())
            <div class="mb-6 p-4 bg-red-900/30 border border-red-700 rounded-lg">
                <p class="text-red-400 font-semibold mb-2">Registration Errors</p>
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                    <li class="text-red-300 text-sm">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('farmowner.register.store') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <!-- Owner Information Section -->
                <div class="bg-gray-700/50 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-white mb-4">Owner Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Full Name *</label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                   class="w-full px-4 py-2 bg-gray-600 text-white border border-gray-500 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                            @error('name')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Email Address *</label>
                            <input type="email" name="email" value="{{ old('email') }}" required
                                   class="w-full px-4 py-2 bg-gray-600 text-white border border-gray-500 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                            @error('email')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Phone Number (PH)</label>
                            <input type="text" name="phone" value="{{ old('phone') }}" placeholder="+639123456789 or 09123456789"
                                   class="w-full px-4 py-2 bg-gray-600 text-white border border-gray-500 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                            @error('phone')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Password *</label>
                            <input type="password" name="password" required
                                   class="w-full px-4 py-2 bg-gray-600 text-white border border-gray-500 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                            <p class="text-xs text-gray-400 mt-1">Minimum 8 characters</p>
                            @error('password')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Confirm Password *</label>
                            <input type="password" name="password_confirmation" required
                                   class="w-full px-4 py-2 bg-gray-600 text-white border border-gray-500 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                            @error('password_confirmation')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <!-- Farm Information Section -->
                <div class="bg-gray-700/50 p-6 rounded-lg">
                    <h3 class="text-lg font-semibold text-white mb-4">Farm Information</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Farm Name *</label>
                            <input type="text" name="farm_name" value="{{ old('farm_name') }}" required
                                   class="w-full px-4 py-2 bg-gray-600 text-white border border-gray-500 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                            @error('farm_name')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-300 mb-2">Business Registration Number *</label>
                                <input type="text" name="business_registration_number" value="{{ old('business_registration_number') }}" required
                                       class="w-full px-4 py-2 bg-gray-600 text-white border border-gray-500 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                                <p class="text-xs text-gray-400 mt-1">Official registration number</p>
                                @error('business_registration_number')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-300 mb-2">Valid ID Picture *</label>
                                <input type="file" name="valid_id" accept="image/*" required
                                       class="w-full px-4 py-2 bg-gray-600 text-white border border-gray-500 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 file:mr-3 file:py-1 file:px-3 file:rounded file:border-0 file:bg-orange-600 file:text-white file:cursor-pointer file:text-xs">
                                <p class="text-xs text-gray-400 mt-1">JPG, PNG, max 5MB</p>
                                @error('valid_id')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-300 mb-2">Farm Address *</label>
                            <input type="text" name="farm_address" value="{{ old('farm_address') }}" required
                                   class="w-full px-4 py-2 bg-gray-600 text-white border border-gray-500 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                            @error('farm_address')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-300 mb-2">City *</label>
                                <input type="text" name="city" value="{{ old('city') }}" required
                                       class="w-full px-4 py-2 bg-gray-600 text-white border border-gray-500 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                                @error('city')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-300 mb-2">Province *</label>
                                <input type="text" name="province" value="{{ old('province') }}" required
                                       class="w-full px-4 py-2 bg-gray-600 text-white border border-gray-500 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                                @error('province')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-300 mb-2">Postal Code</label>
                                <input type="text" name="postal_code" value="{{ old('postal_code') }}"
                                       class="w-full px-4 py-2 bg-gray-600 text-white border border-gray-500 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                                @error('postal_code')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-300 mb-2">Latitude</label>
                                <input type="number" step="0.000001" name="latitude" value="{{ old('latitude') }}"
                                       class="w-full px-4 py-2 bg-gray-600 text-white border border-gray-500 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                                @error('latitude')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-300 mb-2">Longitude</label>
                                <input type="number" step="0.000001" name="longitude" value="{{ old('longitude') }}"
                                       class="w-full px-4 py-2 bg-gray-600 text-white border border-gray-500 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                                @error('longitude')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white font-semibold py-3 rounded-lg transition">
                        Register Farm
                    </button>
                    <a href="{{ route('farmowner.login') }}" class="bg-gray-600 hover:bg-gray-500 text-white font-semibold py-3 rounded-lg transition text-center">
                        Back to Login
                    </a>
                </div>
            </form>
        </div>

        <div class="mt-6 text-center text-gray-400">
            <p class="text-sm">Poultry System © 2026 | For Farm Owners Only</p>
        </div>
    </div>
</body>
</html>
