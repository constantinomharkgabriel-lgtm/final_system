<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\ConsumerRegistrationRequest;
use App\Rules\PhilippinePhoneNumber;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\ConsumerVerificationService;

class ConsumerRegistrationController extends Controller
{
    public function store(ConsumerRegistrationRequest $request, ConsumerVerificationService $verificationService)
    {
        Log::info('Consumer registration request received', [
            'email' => $request->input('email'),
            'phone' => $request->input('phone_number'),
        ]);

        try {
            $validated = $request->validated();

            Log::info('Validation passed', ['validated_data' => array_keys($validated)]);

            $user = DB::transaction(function () use ($validated, $verificationService) {
                // Normalize phone number to +63 format
                $phone = PhilippinePhoneNumber::normalize($validated['phone_number']);
                
                Log::info('Creating user', [
                    'email' => $validated['email'],
                    'phone' => $phone,
                    'name' => $validated['full_name'],
                ]);

                $user = User::create([
                    'name'         => $validated['full_name'],
                    'email'        => $validated['email'],
                    'phone'        => $phone,
                    'password'     => $validated['password'],
                    'role'         => 'consumer',
                    'status'       => 'active',
                    'email_verified_at' => null,
                ]);

                Log::info('User created successfully', ['user_id' => $user->id]);

                $verificationService->issueCode($user);

                return $user;
            });

            session(['consumer_verification_user_id' => $user->id]);

            Log::info('Consumer registered successfully', [
                'user_id' => $user->id,
                'email' => $validated['email'],
            ]);

            return redirect()
                ->route('consumer.verify.form')
                ->with('success', 'Registration complete. Enter the verification code sent to your email.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Consumer registration validation failed', [
                'errors' => $e->errors(),
            ]);
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors($e->errors());
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            Log::error('Consumer registration failed - duplicate data', [
                'error' => $e->getMessage(),
                'email' => $request->input('email'),
            ]);
            $errorMsg = 'Registration failed: ';
            if (strpos($e->getMessage(), 'email') !== false) {
                $errorMsg .= 'This email is already registered.';
            } elseif (strpos($e->getMessage(), 'phone') !== false) {
                $errorMsg .= 'This phone number is already registered.';
            } else {
                $errorMsg .= 'Please use different email or phone number.';
            }
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['registration' => $errorMsg]);
        } catch (\Exception $e) {
            Log::error('Consumer registration failed', [
                'error' => $e->getMessage(),
                'exception' => get_class($e),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'email' => $request->input('email'),
            ]);
            return back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['registration' => 'Registration failed: ' . $e->getMessage()]);
        }
    }
}