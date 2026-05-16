<?php

namespace App\Http\Requests\Members;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore((string) $this->route('member'))],
            'password' => ['nullable', 'string', 'min:8'],
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'role' => ['sometimes', Rule::in(UserRole::values())],
            'is_active' => ['nullable', 'boolean'],
            'suspension_until' => ['nullable', 'date'],
        ];
    }
}
