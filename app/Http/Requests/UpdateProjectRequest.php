<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class UpdateProjectRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['client_name'] = ['sometimes', 'required', 'string', 'max:255'];
        $rules['project_name'] = ['sometimes', 'required', 'string', 'max:255'];
        $rules['planned_at'] = ['sometimes', 'required', 'date'];
        $rules['priority'] = ['sometimes', 'required', 'in:low,medium,high'];

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $project = $this->route('project');

            if ($this->filled('planned_at') && $project !== null
                && Carbon::parse($this->input('planned_at'))->endOfDay()->lt($project->in_progress_at)) {
                $validator->errors()->add('planned_at', 'Due Date cannot be earlier than Start Date.');
            }
        });
    }
}
