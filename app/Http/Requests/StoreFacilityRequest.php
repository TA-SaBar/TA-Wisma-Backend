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
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:kamar,ruang_rapat'],
            'gedung' => ['required', 'string', 'max:255'],
            'lantai' => ['required', 'string', 'max:255'],
            'capacity' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', 'string', 'in:night,4_jam,day'],
            'luas' => ['nullable', 'string', 'max:50'],
            'bed' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:READY,OCCUPIED,CLEANING,MAINTENANCE'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
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
            'type.in' => 'Tipe fasilitas harus kamar atau ruang_rapat.',
            'gedung.required' => 'Gedung wajib diisi.',
            'lantai.required' => 'Lantai wajib diisi.',
            'capacity.required' => 'Kapasitas wajib diisi.',
            'capacity.min' => 'Kapasitas minimal 1 orang.',
            'price.required' => 'Harga wajib diisi.',
            'price.min' => 'Harga tidak boleh negatif.',
            'unit.in' => 'Satuan tarif harus night, 4_jam, atau day.',
            'photo.image' => 'File harus berupa gambar.',
            'photo.mimes' => 'Format foto harus jpeg, png, jpg, atau webp.',
            'photo.max' => 'Ukuran foto maksimal 2MB.',
        ];
    }
}
