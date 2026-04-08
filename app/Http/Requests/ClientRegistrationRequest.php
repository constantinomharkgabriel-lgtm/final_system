<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClientRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'owner_name'                 => ['required', 'string', 'max:255'],
            'farm_name'                  => ['required', 'string', 'max:255'],
            'email'                      => ['required', 'email:rfc,dns', 'max:255', 'unique:client_requests,email', 'unique:users,email'],
            'farm_location'              => ['required', 'string'],
            'valid_id'                   => ['required', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'business_permit'            => ['required', 'mimes:pdf,jpeg,png,jpg', 'max:2048'],
            'barangay_clearance'         => ['required', 'mimes:pdf,jpeg,png,jpg', 'max:2048'],
            'mayor_bir_registration'     => ['required', 'mimes:pdf,jpeg,png,jpg', 'max:2048'],
            'ecc_certificate'            => ['required', 'mimes:pdf,jpeg,png,jpg', 'max:2048'],
            'bai_registration'           => ['required', 'mimes:pdf,jpeg,png,jpg', 'max:2048'],
            'locational_clearance'       => ['required', 'mimes:pdf,jpeg,png,jpg', 'max:2048'],
            'latitude'                   => ['required', 'numeric', 'between:10.0,15.0', 'regex:/^(?:[0-9]|1[0-4])\.?\d*$/'],
            'longitude'                  => ['required', 'numeric', 'between:119.0,124.0', 'regex:/^(?:1[1-2][0-9])\.?\d*$/'],
            'password'                   => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'owner_name.required'              => 'Owner name is required.',
            'farm_name.required'               => 'Farm name is required.',
            'email.email'                      => 'Please enter a valid email address.',
            'email.dns'                        => 'Please use a valid email domain that can receive mail.',
            'email.unique'                     => 'This email is already registered.',
            'valid_id.required'                => 'Please upload a valid ID.',
            'valid_id.image'                   => 'Valid ID must be an image.',
            'business_permit.required'         => 'Business permit is required.',
            'barangay_clearance.required'      => 'Barangay clearance is required.',
            'mayor_bir_registration.required'  => "Mayor's BIR registration is required.",
            'ecc_certificate.required'         => 'Environmental Compliance Certificate (ECC) is required.',
            'bai_registration.required'        => 'Bureau of Animal Industry (BAI) registration is required.',
            'locational_clearance.required'    => 'Locational clearance (zoning) is required.',
            'latitude.required'                => 'Farm location (geolocation) is required.',
            'latitude.between'                 => 'Farm must be located in Cavite area (coordinates: 10.0-15.0 latitude).',
            'longitude.required'               => 'Farm location (geolocation) is required.',
            'longitude.between'                => 'Farm must be located in Cavite area (coordinates: 119.0-124.0 longitude).',
            'password.confirmed'               => 'Passwords do not match.',
            'password.min'                     => 'Password must be at least 8 characters.',
        ];
    }
}
