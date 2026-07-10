<?php

namespace App\Http\Requests;

use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Any authenticated user may create a task (route is behind auth:sanctum).
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'status' => ['sometimes', new Enum(TaskStatus::class)],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('status') || blank($this->input('status'))) {
            $this->merge(['status' => TaskStatus::Pending->value]);
        }
    }
}
