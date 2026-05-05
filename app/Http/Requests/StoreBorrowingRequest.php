<?php

namespace App\Http\Requests;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBorrowingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['Super Admin', 'Librarian']) ?? false;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'exists:users,id'],
            'borrowed_at' => ['required', 'date'],
            'due_at' => ['required', 'date', 'after_or_equal:borrowed_at'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.book_id' => ['required', 'distinct', 'exists:books,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $items = $this->input('items', []);

            if (! is_array($items) || $items === []) {
                return;
            }

            $books = Book::query()
                ->select('id', 'title', 'stock', 'is_active')
                ->whereIn('id', array_column($items, 'book_id'))
                ->get()
                ->keyBy('id');

            foreach ($items as $index => $item) {
                $bookId = $item['book_id'] ?? null;
                $quantity = (int) ($item['quantity'] ?? 0);
                $book = $bookId ? $books->get($bookId) : null;

                if (! $book) {
                    continue;
                }

                if (! $book->is_active) {
                    $validator->errors()->add(
                        "items.$index.book_id",
                        'Selected book is not active.',
                    );
                }

                if ($book->stock < 1) {
                    $validator->errors()->add(
                        "items.$index.book_id",
                        'Selected book is out of stock.',
                    );
                }

                if ($quantity > $book->stock) {
                    $validator->errors()->add(
                        "items.$index.quantity",
                        "Available stock for {$book->title} is only {$book->stock}.",
                    );
                }
            }
        });
    }
}
