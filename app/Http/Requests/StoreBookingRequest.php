<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
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
            'facility_id' => ['required', 'exists:facilities,id'],
            'check_in'    => ['required', 'date', 'after_or_equal:today'],
            'check_out'   => ['required', 'date', 'after:check_in'],
            'guest_name'  => ['required', 'string', 'max:255'],
            'guest_nip'   => ['nullable', 'string', 'max:50'],
            'guest_phone' => ['nullable', 'string', 'max:20'],
            'guest_email' => ['nullable', 'email', 'max:255'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'facility_id.required'   => 'Fasilitas wajib dipilih.',
            'facility_id.exists'     => 'Fasilitas tidak ditemukan.',
            'check_in.required'      => 'Tanggal check-in wajib diisi.',
            'check_in.after_or_equal'=> 'Tanggal check-in tidak boleh sebelum hari ini.',
            'check_out.required'     => 'Tanggal check-out wajib diisi.',
            'check_out.after'        => 'Tanggal check-out harus setelah check-in.',
            'guest_name.required'    => 'Nama tamu wajib diisi.',
        ];
    }
}
