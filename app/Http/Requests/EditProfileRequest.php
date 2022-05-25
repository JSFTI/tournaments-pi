<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class EditProfileRequest extends FormRequest
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
                'description' => 'Changed username',
                'example' => 'username'
            ],
            'email' => [
                'description' => 'Changed email',
                'example' => 'email@example.com'
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
            'username' => ['required', function($attribute, $value, $fail){
                $user = User::where('name', $value)
                    ->where('id', '!=', auth()->user()->id)->first();
                if($user){
                    $fail('Username taken.');
                }
            }],
            'email' => ['required', function($attribute, $value, $fail){
                $user = User::where('email', $value)
                    ->where('id', '!=', auth()->user()->id)->first();
                if($user){
                    $fail('Emaul taken.');
                }
            }]
        ];
    }

    public function messages()
    {
        return [
            'username.required' => 'Please provide a new username',
            'email.required' => 'Please provide an email',
        ];
    }
}
