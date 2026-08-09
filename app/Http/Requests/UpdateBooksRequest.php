<?php

namespace App\Http\Requests;

use App\Support\PdfUploadValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateBooksRequest extends FormRequest
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
            'pdf_file' => ['nullable', 'file', 'mimetypes:application/pdf,application/x-pdf', 'max:' . $pdfMaxKb],
            'price' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'pdf_file.uploaded' => 'The pdf file failed to upload. Check PHP upload_max_filesize / post_max_size (php-fpm) and nginx client_max_body_size.',
            'pdf_file.mimetypes' => 'The file must be a PDF.',
            'pdf_file.max' => 'The PDF may not be greater than :max kilobytes.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $v) => PdfUploadValidation::inspect($this, $v));
    }
}
