<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['sometimes', Rule::in(Task::TYPES)],
            'priority' => ['sometimes', Rule::in(Task::PRIORITIES)],
            'status' => ['sometimes', Rule::in(Task::STATUSES)],
            'progress' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'target_date' => ['nullable', 'date'],
            'target_time' => ['nullable', 'date_format:H:i'],
            'assigned_user_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
