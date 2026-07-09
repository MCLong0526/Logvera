<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    // Admins only.
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
            'is_admin' => ['nullable', 'boolean'],
        ];
    }
}
