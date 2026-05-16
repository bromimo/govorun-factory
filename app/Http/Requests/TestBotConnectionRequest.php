<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Валидация тестового запроса по подключению. */
class TestBotConnectionRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'method' => ['required', 'in:GET,POST,PUT,DELETE,PATCH'],
            'path' => ['required', 'string', 'max:2048'],
            'headers' => ['array'],
            'headers.*.key' => ['string', 'max:255'],
            'headers.*.value' => ['string', 'max:4096'],
            'query' => ['array'],
            'query.*.key' => ['string', 'max:255'],
            'query.*.value' => ['string', 'max:4096'],
            'body_mode' => ['required', 'in:none,json,form'],
            'body' => ['nullable'],
            'state_sample' => ['array'],
        ];
    }
}
