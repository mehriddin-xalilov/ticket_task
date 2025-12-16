<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseRequest;

class UpdateUserRequest extends BaseRequest
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
        $userId = $this->route('user');

        return [
            'name' => 'nullable|string|max:255',
            'email_verified_at' => 'nullable|date',
            'password' => 'required|string|min:6',
            'email' => 'nullable|string|email|max:255|unique:users,email,' . $userId,
            'login' => 'nullable|string|max:255|unique:users,login,' . $userId,
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $userId,
            'status' => 'required|integer|in:0,1',
            'remember_token' => 'nullable|string',
            'photo' => 'nullable|integer|exists:files,id',
        ];
    }
}
