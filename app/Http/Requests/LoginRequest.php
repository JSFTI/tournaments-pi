<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
                'description' => 'Your username',
                'example' => 'username'
            ],
            'password' => [
                'description' => 'Your password',
                'example' => 'password'
            ]
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'username' => ['required', 'exists:users,name'],
            'password' => ['required', function($attribute, $value, $fail){
                $username = request()->username;
                if($username){
                    $user = User::where('name', $username)->select('password')->first();
                    if($user?->password && !password_verify($value, $user->password)){
                        $fail('Wrong password');
                    }
                }
            }] 
        ];
    }

    public function messages()
    {
        return [
            'username.required' => 'Please provide a registered username',
            'username.exists' => 'Username not found',
            'password.required' => 'Plese provide the password'
        ];
    }
}
