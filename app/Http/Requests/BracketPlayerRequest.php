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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'player_id' => ['required', Rule::exists('players', 'id')->where(function($q){
                $bracket = Bracket::select('tournament_id')->where('player_id', request('player_id'))->first();
                return $q->where('tournament_id', $bracket->tournament_id);
            })]
        ];
    }

    public function messages()
    {
        return [
            'player_id.required' => 'Target player ID is required',
            'player.exists' => 'Target plater ID is not found in tournament brackets'
        ];
    }
}
