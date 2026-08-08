<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAuthorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isSuperAdmin() || $user->isAdmin());
    }

    public function rules(): array
    {
        $social = [
            'website' => ['nullable', 'url', 'max:255'],
            'facebook' => ['nullable', 'url', 'max:255'],
            'instagram' => ['nullable', 'url', 'max:255'],
            'twitter' => ['nullable', 'url', 'max:255'],
            'tiktok' => ['nullable', 'url', 'max:255'],
            'youtube' => ['nullable', 'url', 'max:255'],
            'telegram' => ['nullable', 'url', 'max:255'],
        ];

        $mode = $this->input('mode', 'new_account');

        if ($mode === 'existing_user') {
            return array_merge([
                'mode' => ['required', Rule::in(['new_account', 'existing_user'])],
                'user_id' => [
                    'required',
                    'uuid',
                    'exists:users,id',
                    Rule::unique('authors', 'user_id'),
                ],
                'image_id' => ['nullable', 'uuid', 'exists:images,id'],
                'image_file' => ['nullable', 'image', 'max:5120'],
                'bio' => ['nullable', 'string', 'max:5000'],
            ], $social);
        }

        return array_merge([
            'mode' => ['required', Rule::in(['new_account', 'existing_user'])],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'image_id' => ['nullable', 'uuid', 'exists:images,id'],
            'image_file' => ['nullable', 'image', 'max:5120'],
            'bio' => ['nullable', 'string', 'max:5000'],
        ], $social);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('mode') !== 'existing_user') {
                return;
            }

            $userId = $this->input('user_id');
            if (! $userId) {
                return;
            }

            $user = \App\Models\User::with('role')->find($userId);
            if (! $user) {
                return;
            }

            if ($user->isSuperAdmin() || $user->isAdmin()) {
                $validator->errors()->add('user_id', 'Admin accounts cannot be linked as authors.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'user_id.unique' => 'This user already has an author profile.',
        ];
    }
}
