<?php

namespace App\Http\Requests\User;

use App\Helpers\Roles;
use App\Http\Requests\BaseRequest;

class StoreUserRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'email_verified_at' => 'nullable|date',
            'password' => 'required|string',
            'email' => 'nullable|string|unique:users,email',
            'login' => 'nullable|string|unique:users,login',
            'phone' => 'required|string|unique:users,phone',
            'status' => 'required|integer|in:0,1',
            'remember_token' => 'nullable|string',
            'photo'=>'nullable|integer|exists:files,id',
            'role' => 'required|in:' . implode(',', Roles::asArray()),
        ];
    }
}
