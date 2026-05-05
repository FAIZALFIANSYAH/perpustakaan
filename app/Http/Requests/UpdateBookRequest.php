<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['Super Admin', 'Librarian']) ?? false;
    }

    public function rules(): array
    {
        $book = $this->route('book');

        return [
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'isbn' => ['nullable', 'string', 'max:255', Rule::unique('books', 'isbn')->ignore($book?->id)],
            'publish_year' => ['nullable', 'digits:4', 'integer', 'min:1000', 'max:' . date('Y')],
            'stock' => ['required', 'numeric', 'min:0'],
            'cover' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = $this->all();
        
        // Remove cover field if it's a string (existing path) to avoid validation error
        if (isset($data['cover']) && is_string($data['cover'])) {
            unset($data['cover']);
            $this->replace($data);
        }
        
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'stock' => (int) $this->input('stock', 0),
        ]);
    }
}
