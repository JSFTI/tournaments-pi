<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlayerRequest extends FormRequest
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
            'name' => [
                'description' => 'Name of the player. Must be less than or equal to 100 characters.',
                'example' => 'Adi'
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
            'name' => 'required|max:100'
        ];
    }
    
    public function messages()
    {
        return [
            'name.required' => 'Please provide a name',
            'name.max' => 'Name must be under 100 characters'
        ];
    }
}
