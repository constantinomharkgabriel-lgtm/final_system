<x-guest-layout>
    <div class="min-h-screen flex flex-col justify-center items-center bg-[#1a202c] p-4 sm:p-6">
        <div class="w-full max-w-2xl p-6 sm:p-8 bg-[#111827] border border-gray-700 rounded-xl sm:rounded-3xl shadow-2xl">
            
            <h2 class="text-2xl sm:text-3xl font-black text-[#4fd1c5] mb-2 uppercase tracking-widest text-center">
                Shopper Registration
            </h2>
            <p class="text-gray-400 text-xs sm:text-sm text-center mb-6 sm:mb-8 uppercase font-bold tracking-tighter">Create your consumer account</p>

            @if ($errors->any())
            <div class="mb-6 p-4 bg-red-900/40 border-l-4 border-red-500 rounded-lg">
                <p class="text-red-300 font-bold mb-3 text-sm">⚠ Registration Error</p>
                <ul class="list-disc list-inside space-y-2">
                    @foreach ($errors->all() as $error)
                    <li class="text-red-200 text-sm">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if (session('success'))
            <div class="mb-6 p-4 bg-green-900/40 border-l-4 border-green-500 rounded-lg">
                <p class="text-green-200 font-bold mb-2 text-sm">✓ Success</p>
                <p class="text-green-200 text-sm">{{ session('success') }}</p>
            </div>
            @endif

            <form method="POST" action="{{ route('consumer.store') }}">
                @csrf

                <!-- Row 1: Full Name & Email (2 cols on desktop, 1 on mobile) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-500 text-xs font-black uppercase mb-2 ml-1">Full Name</label>
                        <input type="text" name="full_name" value="{{ old('full_name') }}" placeholder="John Doe" 
                               class="w-full px-4 py-2 bg-[#1a202c] border border-gray-700 text-white rounded-xl focus:ring-[#4fd1c5] focus:border-[#4fd1c5] transition" required>
                        @error('full_name')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-gray-500 text-xs font-black uppercase mb-2 ml-1">Email Address</label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="john@example.com" 
                               class="w-full px-4 py-2 bg-[#1a202c] border border-gray-700 text-white rounded-xl focus:ring-[#4fd1c5] focus:border-[#4fd1c5] transition" required>
                        @error('email')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <!-- Row 2: Contact Number & Delivery Address -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-gray-500 text-xs font-black uppercase mb-2 ml-1">Contact Number</label>
                        <input type="text" name="phone_number" value="{{ old('phone_number') }}" placeholder="09123456789" 
                               class="w-full px-4 py-2 bg-[#1a202c] border border-gray-700 text-white rounded-xl focus:ring-[#4fd1c5] focus:border-[#4fd1c5] transition" required>
                        @error('phone_number')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-gray-500 text-xs font-black uppercase mb-2 ml-1">Delivery Address</label>
                        <textarea name="address" rows="1" value="{{ old('address') }}" placeholder="Street, Barangay, City" 
                                  class="w-full px-4 py-2 bg-[#1a202c] border border-gray-700 text-white rounded-xl focus:ring-[#4fd1c5] focus:border-[#4fd1c5] transition" required></textarea>
                        @error('address')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <!-- Row 3: Password & Confirm Password (2 cols on desktop, 1 on mobile) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-gray-500 text-xs font-black uppercase mb-2 ml-1">Password</label>
                        <input type="password" name="password" placeholder="Enter password" 
                               class="w-full px-4 py-2 bg-[#1a202c] border border-gray-700 text-white rounded-xl focus:ring-[#4fd1c5] focus:border-[#4fd1c5] transition" required>
                        <p class="text-xs text-gray-500 mt-1">Minimum 8 characters</p>
                        @error('password')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-gray-500 text-xs font-black uppercase mb-2 ml-1">Confirm Password</label>
                        <input type="password" name="password_confirmation" placeholder="Confirm password" 
                               class="w-full px-4 py-2 bg-[#1a202c] border border-gray-700 text-white rounded-xl focus:ring-[#4fd1c5] focus:border-[#4fd1c5] transition" required>
                        @error('password_confirmation')<p class="text-red-400 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="w-full bg-[#4fd1c5] hover:bg-[#38b2ac] text-[#111827] font-black py-3 sm:py-4 rounded-2xl transition duration-300 uppercase tracking-widest shadow-lg transform hover:scale-[1.02] text-sm sm:text-base">
                    Complete Registration
                </button>

                <p class="mt-6 text-center text-gray-500 text-xs">
                    By registering, you agree to our terms of service.
                </p>
            </form>
        </div>
    </div>
</x-guest-layout>