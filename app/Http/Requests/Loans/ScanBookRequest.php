<?php

namespace App\Http\Requests\Loans;

use Illuminate\Foundation\Http\FormRequest;

class ScanBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'book_copy_qr' => ['required', 'string', 'max:255'],
            'user_id' => ['required', 'uuid', 'exists:users,id'],
        ];
    }
}
