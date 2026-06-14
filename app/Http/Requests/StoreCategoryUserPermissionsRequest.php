<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryUserPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'users' => ['required', 'array'],
            'users.*' => ['required', 'string'],

            // permissions is an array of permission_id selected for the chosen users
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['required', 'string'],
        ];
    }
}
