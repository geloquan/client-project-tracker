<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return $this->projectRules();
    }

    public function messages(): array
    {
        return [
            'client_name.required' => 'Client Name is required.',
            'project_name.required' => 'Project Name is required.',
            'status.in' => 'Status must be valid.',
            'priority.in' => 'Priority must be valid.',
            'planned_at.after_or_equal' => 'Due Date cannot be earlier than Start Date.',
        ];
    }

    protected function projectRules(): array
    {
        return [
            'client_name' => ['required', 'string', 'max:255'],
            'project_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'planned_at' => ['required', 'date', 'after_or_equal:today'],
            'priority' => ['required', 'in:low,medium,high'],
        ];
    }
}
