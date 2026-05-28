<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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

            'email' => [

                'required',
                'email',

                Rule::unique('users')
                    ->ignore($this->user)

            ],

            'phone' => [

                'nullable',
                'string',
                'max:20'

            ],

            'branch_id' => [

                'required',
                'exists:branches,id'

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
}