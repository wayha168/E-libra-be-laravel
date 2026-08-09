<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class StoreBooksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $pdfMaxKb = (int) config('elibra.book_pdf_max_kb', 524288);

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'author_id' => ['nullable', 'uuid', 'exists:authors,id'],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'image_id' => ['nullable', 'uuid', 'exists:images,id'],
            'public_date' => ['nullable', 'date'],
            'image_file' => ['nullable', 'image', 'max:5120'],
            'image_files' => ['nullable', 'array'],
            'image_files.*' => ['image', 'max:5120'],
            'pdf_file' => ['nullable', 'file', 'mimes:pdf', 'max:' . $pdfMaxKb],
            'price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $file = $this->file('pdf_file');
            if (! $file instanceof UploadedFile) {
                return;
            }

            if ($file->isValid()) {
                return;
            }

            $validator->errors()->add('pdf_file', UpdateBooksRequest::uploadFailureMessage($file));
        });
    }
}
