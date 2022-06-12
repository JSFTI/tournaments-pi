<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    // public function authorize()
    // {
    //     return false;
    // }

    public function bodyParameters(){
        return [
            'username' => [
                'description' => 'New username',
                'example' => 'username'
            ],
            'email' => [
                'description' => 'New email',
                'example' => 'email@example.com'
            ],
            'password' => [
                'description' => 'Your password',
                'example' => 'password'
            ],
            'password_confirmation' => [
                'description' => 'Same value as ```password```',
                'example' => 'password'
            ]
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'username' => ['required', 'unique:users,name'],
            'email' => ['required', 'unique:users,email'],
            'password' => ['required', 'confirmed'],
            'password_confirmation' => []
        ];
    }

    public function messages()
    {
        return [
            'username.required' => 'Please provide a new username',
            'username.unique' => ':value is taken',
            'email.required' => 'Please provide an email',
            'email.unique' => ':value is already registered',
            'password.required' => 'Please provide a password',
            'password.confirmed' => 'Please confirm your password'
        ];
    }
}
