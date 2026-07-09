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

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $facilityId = $this->input('facility_id');
            $checkIn = $this->input('check_in');
            $checkOut = $this->input('check_out');

            if ($facilityId && $checkIn && $checkOut) {
                // Check if any booking overlaps with the requested dates
                // Overlap condition:
                // Existing booking check_in < Requested check_out AND Existing check_out > Requested check_in
                $conflict = \App\Models\Booking::where('facility_id', $facilityId)
                    ->where(function ($q) {
                        $q->whereIn('status', ['lunas', 'check_in'])
                          ->orWhere(function ($q2) {
                              $q2->where('status', 'pending')
                                 ->where('created_at', '>=', now()->subMinutes(60));
                          });
                    })
                    ->where('check_in', '<', $checkOut)
                    ->where('check_out', '>', $checkIn)
                    ->exists();

                if ($conflict) {
                    $validator->errors()->add('check_in', 'Fasilitas sudah dipesan pada rentang tanggal tersebut. Silakan pilih tanggal lain.');
                }
            }
        });
    }
}
