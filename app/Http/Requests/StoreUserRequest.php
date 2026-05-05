<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('Super Admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'borrow_limit' => ['required', 'integer', 'min:1', 'max:50'],
            'role' => ['required', 'string', Rule::in($this->allowedRoles())],
        ];
    }

    protected function allowedRoles(): array
    {
        return ['Librarian', 'Member'];
    }
}
