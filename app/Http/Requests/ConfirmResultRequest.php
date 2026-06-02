<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'judge';
    }

    public function rules(): array
    {
        return [
            // Scores are optional because a judge may simply confirm the latest auto-check result.
            'score_backend' => ['sometimes', 'numeric', 'between:0,40'],
            'score_frontend' => ['sometimes', 'numeric', 'between:0,25'],
            'score_integration' => ['sometimes', 'numeric', 'between:0,20'],
            'score_deployment' => ['sometimes', 'numeric', 'between:0,10'],
            'score_code_quality' => ['sometimes', 'numeric', 'between:0,5'],
            'judge_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
