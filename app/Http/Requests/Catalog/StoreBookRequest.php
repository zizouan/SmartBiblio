<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'isbn' => ['nullable', 'string', 'max:20', 'unique:books,isbn'],
            'title' => ['required', 'string', 'max:500'],
            'synopsis' => ['nullable', 'string'],
            'cover_url' => ['nullable', 'url', 'max:500'],
            'published_year' => ['nullable', 'integer', 'min:1000', 'max:'.(date('Y') + 1)],
            'language' => ['nullable', 'string', 'max:10'],
            'total_copies' => ['nullable', 'integer', 'min:1'],
            'available_copies' => ['nullable', 'integer', 'min:0'],
            'author_ids' => ['array'],
            'author_ids.*' => ['uuid', 'exists:authors,id'],
            'genre_ids' => ['array'],
            'genre_ids.*' => ['uuid', 'exists:genres,id'],
        ];
    }
}
