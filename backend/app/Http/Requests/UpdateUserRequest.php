<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    // Admins only.
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'job_title' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:6'],
            'is_admin' => ['nullable', 'boolean'],
        ];
    }
}
