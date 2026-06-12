<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBooksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'author_id' => ['nullable', 'uuid', 'exists:authors,id'],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'image_id' => ['nullable', 'uuid', 'exists:images,id'],
            'public_date' => ['nullable', 'date'],
            'image_file' => ['nullable', 'image', 'max:5120'],
            'pdf_file' => ['nullable', 'mimes:pdf', 'max:10240'],
            'is_subscribed' => ['sometimes', 'boolean'],
        ];
    }
}
