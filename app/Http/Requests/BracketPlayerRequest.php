<?php

namespace App\Http\Requests;

use App\Models\Bracket;
use App\Models\Tournament;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BracketPlayerRequest extends FormRequest
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
            'player_id' => [
                'description' => 'Target player.',
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
            'player_id' => ['required', Rule::exists('players', 'id')->where(function($q){
                $bracket = Bracket::select('tournament_id')->where('id', request()->bracket)->first();
                return $q->where('tournament_id', $bracket->tournament_id);
            })]
        ];
    }

    public function messages()
    {
        return [
            'player_id.required' => 'Player ID is required',
            'player_id.exists' => 'Player is not part of this tournament'
        ];
    }
}
