<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // semua user yang authenticated boleh update profil sendiri
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'     => ['sometimes', 'string', 'max:255'],
            'email'    => ['sometimes', 'email', 'max:255', 'unique:users,email,' . $this->user()->id],
            'phone'    => ['sometimes', 'nullable', 'string', 'max:20'],
            'instansi' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'email.unique'        => 'Email sudah digunakan oleh akun lain.',
        ];
    }
}
