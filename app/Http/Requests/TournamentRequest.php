<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TournamentRequest extends FormRequest
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
                'description' => 'Name of the tournament. Must be less than or equal to 100 characters.',
                'example' => 'Tournament Awesome'
            ],
            'description' => [
                'description' => 'Short description of the tournament. Must be less than or equal to 500 characters. Note: WYSIWYG tags and markdowns are considered too.',
                'example' => 'Lorem ipsum dolor sit...'
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
            'name' => 'required|max:100',
            'description' => 'max:500'
        ];
    }

    public function messages(){
        return [
            'name.required' => 'Please give a name to the tournament',
            'name.max' => 'Name must be under 100 characters',
            'description.max' => 'Description must be under 500 characters'
        ];
    }
}
