<?php

namespace App\Http\Requests;

use App\Models\Borrowing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReturnBorrowingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['Super Admin', 'Librarian']) ?? false;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],
            'items.*.return_quantity' => ['required', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Borrowing|int|string|null $borrowing */
            $borrowing = $this->route('borrowing');

            if (! $borrowing) {
                return;
            }

            if (! $borrowing instanceof Borrowing) {
                $borrowing = Borrowing::with('items')->find($borrowing);
            } else {
                $borrowing->loadMissing('items');
            }

            if (! $borrowing) {
                $validator->errors()->add('borrowing', 'Borrowing data is invalid.');
                return;
            }

            $itemsById = $borrowing->items->keyBy('id');
            $hasPositiveReturn = false;

            foreach ($this->input('items', []) as $index => $item) {
                $borrowingItem = $itemsById->get($item['id'] ?? null);

                if (! $borrowingItem) {
                    $validator->errors()->add("items.$index.id", 'Borrowing item is invalid.');
                    continue;
                }

                $returnQuantity = (int) ($item['return_quantity'] ?? 0);
                $remainingQuantity = $borrowingItem->quantity - $borrowingItem->returned_quantity;

                if ($returnQuantity > $remainingQuantity) {
                    $validator->errors()->add(
                        "items.$index.return_quantity",
                        "Maximum return quantity for this item is {$remainingQuantity}.",
                    );
                }

                if ($returnQuantity > 0) {
                    $hasPositiveReturn = true;
                }
            }

            if (! $hasPositiveReturn) {
                $validator->errors()->add('items', 'At least one item must be returned.');
            }
        });
    }
}
