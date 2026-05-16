<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookCopyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'book_id' => ['required', 'uuid', 'exists:books,id'],
            'qr_code' => ['nullable', 'string', 'max:255', 'unique:book_copies,qr_code'],
            'condition' => ['nullable', 'string', 'max:40'],
            'shelf_location' => ['nullable', 'string', 'max:100'],
        ];
    }
}
