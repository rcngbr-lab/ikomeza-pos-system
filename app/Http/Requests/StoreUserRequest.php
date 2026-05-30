<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'name' => [

                'required',
                'string',
                'max:255'

            ],

            'username' => [

                'required',
                'string',
                'min:3',
                'max:80',
                'regex:/^[a-z0-9._-]+$/',
                'unique:users,username'

            ],

            'email' => [

                'nullable',
                'email',
                'unique:users,email'

            ],

            'phone' => [

                'nullable',
                'string',
                'max:20'

            ],

            'password' => [

                'required',
                'min:6'

            ],

            'branch_id' => [

                'required',
                'exists:branches,id'

            ],

            'department_id' => [

                'nullable',
                'exists:departments,id'

            ],

            'role' => [

                'required',
                'exists:roles,name'

            ],

            'status' => [

                'required',
                'in:ACTIVE,INACTIVE,SUSPENDED'

            ],

        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => Str::lower(trim((string) $this->input('username'))),
            'email' => $this->filled('email')
                ? Str::lower(trim((string) $this->input('email')))
                : null,
        ]);
    }
}
