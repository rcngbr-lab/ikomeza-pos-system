<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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

            'email' => [

                'required',
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
}
