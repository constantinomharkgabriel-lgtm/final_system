<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farm Owner Approval Status</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-900 min-h-screen flex items-center justify-center p-6 text-gray-200">
    <div class="w-full max-w-xl bg-gray-800 border border-gray-700 rounded-2xl p-8 shadow-2xl">
        
        @if($farmOwner?->permit_status === 'rejected')
            <!-- REJECTED STATUS -->
            <h1 class="text-3xl font-bold text-red-500">Registration Rejected</h1>

            <div class="mt-6 p-4 bg-red-500/10 border border-red-500/50 rounded-lg">
                <p class="text-red-400 font-semibold mb-2">Rejection Reason:</p>
                <p class="text-gray-300">
                    Your registration was rejected. Please review the reason provided in your email
                    and update your farm details if needed.
                </p>
            </div>

            <p class="mt-4 text-gray-300">
                You have the opportunity to edit your farm information and resubmit for approval.
                Make sure to address the issues mentioned in the rejection notice.
            </p>

            <div class="mt-8 flex gap-3">
                <a href="{{ route('farmowner.register') }}"
                   class="flex-1 px-5 py-3 bg-orange-600 hover:bg-orange-700 rounded-lg font-semibold text-center">
                    Edit & Resubmit
                </a>
                <a href="{{ route('farmowner.logout') }}"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                   class="px-5 py-3 bg-gray-700 hover:bg-gray-600 rounded-lg font-semibold">
                    Logout
                </a>
            </div>

        @elseif($farmOwner?->permit_status === 'pending')
            <!-- PENDING STATUS -->
            <h1 class="text-3xl font-bold text-orange-500">Registration Under Review</h1>

            <p class="mt-4 text-gray-300">
                Your farm owner account is currently <span class="font-semibold text-orange-400">pending</span> approval.
            </p>

            <p class="mt-3 text-gray-400">
                You cannot access the farm owner dashboard and modules until the Super Admin approves your registration.
                We will notify you through email once your status is approved or denied.
            </p>

            <div class="mt-8 flex gap-3">
                <a href="{{ route('farmowner.logout') }}"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                   class="px-5 py-3 bg-gray-700 hover:bg-gray-600 rounded-lg font-semibold">
                    Logout
                </a>
            </div>

        @elseif($farmOwner?->permit_status === 'approved')
            <!-- SHOULD NOT HAPPEN - Redirect to dashboard -->
            <h1 class="text-3xl font-bold text-green-500">Approved!</h1>
            <p class="mt-4 text-gray-300">Your registration has been approved. Redirecting to dashboard...</p>
            <script>
                window.location.href = "{{ route('farmowner.dashboard') }}";
            </script>

        @else
            <!-- UNKNOWN STATUS -->
            <h1 class="text-3xl font-bold text-gray-400">Status Unknown</h1>
            <p class="mt-4 text-gray-300">
                Please contact the Super Admin to verify your registration status.
            </p>
            <div class="mt-8">
                <a href="{{ route('farmowner.logout') }}"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                   class="px-5 py-3 bg-gray-700 hover:bg-gray-600 rounded-lg font-semibold inline-block">
                    Logout
                </a>
            </div>

        @endif

        <form id="logout-form" action="{{ route('farmowner.logout') }}" method="POST" class="hidden">
            @csrf
        </form>
    </div>
</body>
</html>
