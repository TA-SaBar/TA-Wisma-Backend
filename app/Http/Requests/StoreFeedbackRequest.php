<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating_cleanliness' => ['required', 'integer', 'min:1', 'max:5'],
            'rating_facilities'  => ['required', 'integer', 'min:1', 'max:5'],
            'rating_service'     => ['required', 'integer', 'min:1', 'max:5'],
            'comment'            => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating_cleanliness.required' => 'Rating kebersihan wajib diisi.',
            'rating_cleanliness.min'      => 'Rating minimal 1 bintang.',
            'rating_cleanliness.max'      => 'Rating maksimal 5 bintang.',
            'rating_facilities.required'  => 'Rating fasilitas wajib diisi.',
            'rating_service.required'     => 'Rating pelayanan wajib diisi.',
        ];
    }
}
