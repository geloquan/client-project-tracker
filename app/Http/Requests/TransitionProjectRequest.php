<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransitionProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:on_hold,complete'],
        ];
    }
}
