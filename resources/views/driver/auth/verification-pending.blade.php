<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification Pending - Driver Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-md space-y-8">
            <!-- Logo & Header -->
            <div class="text-center">
                <img class="h-12 w-auto mx-auto" src="/images/logo.png" alt="Logo">
                <h2 class="mt-6 text-3xl font-bold tracking-tight text-gray-900">
                    Email Verification Required
                </h2>
            </div>

            <!-- Info Box -->
            <div class="rounded-lg bg-amber-50 border-l-4 border-amber-500 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-amber-700">
                            Your email address has not been verified yet. You need to verify it before accessing the driver portal.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="space-y-4">
                <div class="bg-white rounded-lg shadow p-6 space-y-4">
                    <h3 class="text-lg font-semibold text-gray-900">What to do next:</h3>
                    <ol class="list-decimal list-inside space-y-2 text-gray-600 text-sm">
                        <li>Check your email inbox for a message from "Poultry System"</li>
                        <li>Click the verification link in the email (valid for 60 minutes)</li>
                        <li>Your account will be activated and visible to logistics staff</li>
                        <li>Log in to start accepting deliveries</li>
                    </ol>

                    <div class="pt-4 border-t border-gray-200">
                        <p class="text-xs text-gray-500 mb-3">
                            Didn't receive the email? Check your spam/junk folder.
                        </p>
                    </div>
                </div>

                <!-- Driver Name Display (if logged in) -->
                @auth
                    <div class="bg-white rounded-lg shadow p-6">
                        <p class="text-sm text-gray-700">
                            <strong>Logged in as:</strong><br>
                            {{ Auth::user()->name }}<br>
                            <em class="text-gray-500">{{ Auth::user()->email }}</em>
                        </p>
                        <form action="{{ route('driver.logout') }}" method="POST" class="mt-4">
                            @csrf
                            <button type="submit" class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                                Log out
                            </button>
                        </form>
                    </div>
                @endauth
            </div>

            <!-- Info Message -->
            <div class="rounded-lg bg-blue-50 p-4">
                <p class="text-sm text-blue-800">
                    <strong>Need help?</strong><br>
                    Contact your HR manager or the support team if you:
                </p>
                <ul class="mt-2 list-disc list-inside text-sm text-blue-700 space-y-1">
                    <li>Haven't received the verification email</li>
                    <li>The verification link has expired</li>
                    <li>Need to resend the verification email</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
