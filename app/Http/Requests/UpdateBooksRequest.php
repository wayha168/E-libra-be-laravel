<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
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

            $validator->errors()->add('pdf_file', self::uploadFailureMessage($file));
        });
    }

    public static function uploadFailureMessage(UploadedFile $file): string
    {
        $code = $file->getError();
        $uploadMax = ini_get('upload_max_filesize') ?: '?';
        $postMax = ini_get('post_max_size') ?: '?';

        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "PDF too large for server limits (upload_max_filesize={$uploadMax}, post_max_size={$postMax}).",
            UPLOAD_ERR_PARTIAL => 'PDF was only partially uploaded. Try again.',
            UPLOAD_ERR_NO_FILE => 'No PDF file was received.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server missing temp folder for uploads.',
            UPLOAD_ERR_CANT_WRITE => 'Server could not write the PDF (check storage permissions).',
            UPLOAD_ERR_EXTENSION => 'A PHP extension blocked the PDF upload.',
            default => "The pdf file failed to upload (PHP error {$code}; upload_max_filesize={$uploadMax}, post_max_size={$postMax}).",
        };
    }
}
