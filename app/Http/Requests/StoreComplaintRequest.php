<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'category'    => ['required', 'in:facility,laundry,internet,food'],
            'location'    => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'    => 'Judul keluhan wajib diisi.',
            'category.required' => 'Kategori keluhan wajib dipilih.',
            'category.in'       => 'Kategori tidak valid. Pilih salah satu: fasilitas, laundry, internet, makanan.',
            'location.required' => 'Lokasi kejadian wajib diisi.',
        ];
    }
}
