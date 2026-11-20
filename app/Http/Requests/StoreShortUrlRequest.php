<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShortUrlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('original_url'))) {
            $this->merge([
                'original_url' => trim($this->input('original_url')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'original_url' => ['required', 'string', 'url:http,https', 'max:2048'],
        ];
    }
}
