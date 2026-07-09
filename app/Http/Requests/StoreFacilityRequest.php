<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFacilityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'type'        => ['required', 'in:Buah,Bunga,Rapat'],
            'gedung'      => ['required', 'string', 'max:255'],
            'lantai'      => ['required', 'string', 'max:255'],
            'capacity'    => ['required', 'integer', 'min:1'],
            'price'       => ['required', 'numeric', 'min:0'],
            'unit'        => ['required', 'string', 'in:night,day,4_jam'],
            'luas'        => ['nullable', 'string', 'max:50'],
            'bed'         => ['nullable', 'string', 'max:255'],
            'status'      => ['nullable', 'in:READY,OCCUPIED,CLEANING,MAINTENANCE'],
            'photo'       => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'description' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama fasilitas wajib diisi.',
            'type.required' => 'Tipe fasilitas wajib dipilih.',
            'gedung.required' => 'Gedung wajib diisi.',
            'lantai.required' => 'Lantai wajib diisi.',
            'capacity.required' => 'Kapasitas wajib diisi.',
            'capacity.min' => 'Kapasitas minimal 1 orang.',
            'price.required' => 'Harga wajib diisi.',
            'price.min' => 'Harga tidak boleh negatif.',
            'type.in'        => 'Tipe fasilitas harus Buah, Bunga, atau Rapat.',
            'unit.in'        => 'Satuan tarif harus night, day, atau 4_jam.',
            'photo.image' => 'File harus berupa gambar.',
            'photo.mimes' => 'Format foto harus jpeg, png, jpg, atau webp.',
            'photo.max' => 'Ukuran foto maksimal 2MB.',
        ];
    }
}
