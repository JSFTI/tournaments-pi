<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ChangeProfilePasswordRequest extends FormRequest
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
            'oldPassword' => [
                'description' => 'Old password. Necessary for security reasons.',
                'example' => 'password'
            ],
            'password' => [
                'description' => 'New password.',
                'example' => 'newPassword'
            ],
            'password_confirmation' => [
                'description' => 'Confirm new password.',
                'example' => 'newPassword'
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
            'oldPassword' => ['required', function($attribute, $value, $fail){
                $user = User::find(auth()->user()->id);
                if(!password_verify($value, $user->password)){
                    $fail('Wrong password');
                }
            }],
            'password' => ['required', 'confirmed'],
            'password_confirmation' => []
        ];
    }

    public function messages()
    {
        return [
            'oldPassword.required' => 'Please give the old password',
            'password.required' => 'Please provide a password',
            'password.confirmed' => 'Please confirm your password'
        ];
    }
}
