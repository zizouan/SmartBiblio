<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookCopyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $copyId = (string) $this->route('book_copy');

        return [
            'book_id' => ['sometimes', 'uuid', 'exists:books,id'],
            'qr_code' => ['sometimes', 'string', 'max:255', Rule::unique('book_copies', 'qr_code')->ignore($copyId)],
            'condition' => ['nullable', 'string', 'max:40'],
            'shelf_location' => ['nullable', 'string', 'max:100'],
        ];
    }
}
