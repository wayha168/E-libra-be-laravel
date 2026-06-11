<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => ['nullable', 'string', 'max:2048', 'required_without:image_file'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'image_type' => ['nullable', 'string', 'max:100'],
            'image_file' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
