<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['client_name'] = ['sometimes', 'required', 'string', 'max:255'];
        $rules['project_name'] = ['sometimes', 'required', 'string', 'max:255'];
        $rules['planned_at'] = ['sometimes', 'required', 'date', 'after_or_equal:in_progress_at'];
        $rules['priority'] = ['sometimes', 'required', Rule::in(['low', 'medium', 'high'])];

        return $rules;
    }
}
