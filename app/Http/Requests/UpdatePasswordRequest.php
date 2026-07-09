<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePasswordRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password'      => ['required', 'string', 'current_password'],
            'password'              => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'current_password.required'         => 'Password saat ini harus diisi.',
            'current_password.current_password' => 'Password saat ini tidak sesuai.',
            'password.required'                 => 'Password baru harus diisi.',
            'password.min'                      => 'Password baru minimal 8 karakter.',
            'password.confirmed'                => 'Konfirmasi password tidak cocok.',
            'password_confirmation.required'    => 'Konfirmasi password baru harus diisi.',
        ];
    }
}
