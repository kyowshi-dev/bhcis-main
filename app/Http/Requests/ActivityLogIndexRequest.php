<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ActivityLogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'query' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'action' => ['nullable', 'string', 'max:100'],
            'table_name' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'string', 'date_format:d/m/Y'],
            'date_to' => ['nullable', 'string', 'date_format:d/m/Y'],
            'failures_only' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'string', 'in:newest,oldest'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}
