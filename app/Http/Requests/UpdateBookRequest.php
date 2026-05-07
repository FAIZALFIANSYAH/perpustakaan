<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
            'remove_cover' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->logPotentialPayloadIssue();

        // Keep uploaded file intact. Only normalize string-based sentinel/value from edit form.
        if ($this->input('cover') === 'REMOVE') {
            $this->request->remove('cover');
            $this->merge(['remove_cover' => true]);
        } elseif (is_string($this->input('cover')) && $this->input('cover') !== '') {
            // Existing path string should not be re-validated as image on update.
            $this->request->remove('cover');
        }
        
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'stock' => (int) $this->input('stock', 0),
            'remove_cover' => $this->boolean('remove_cover'),
        ]);
    }

    protected function logPotentialPayloadIssue(): void
    {
        $contentLength = (int) $this->server('CONTENT_LENGTH', 0);
        $postMaxSizeBytes = $this->toBytes((string) ini_get('post_max_size'));
        $hasNoInputPayload = empty($this->all()) && empty($this->allFiles());

        if ($contentLength > 0 && $hasNoInputPayload) {
            Log::warning('Book update arrived without payload.', [
                'route' => $this->path(),
                'method' => $this->method(),
                'content_length' => $contentLength,
                'post_max_size' => ini_get('post_max_size'),
                'post_max_size_bytes' => $postMaxSizeBytes,
                'content_length_exceeds_post_max' => $postMaxSizeBytes > 0 ? $contentLength > $postMaxSizeBytes : null,
                'content_type' => $this->header('Content-Type'),
                'user_id' => $this->user()?->id,
            ]);

            $message = 'Upload gagal: payload request kosong. Kemungkinan ukuran request melebihi batas server (post_max_size/upload_max_filesize).';
            throw ValidationException::withMessages([
                'cover' => $message,
            ]);
        }
    }

    protected function toBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $number = (int) $value;
        $unit = strtolower(substr($value, -1));

        return match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
