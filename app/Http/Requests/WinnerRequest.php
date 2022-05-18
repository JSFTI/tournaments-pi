<?php

namespace App\Http\Requests;

use App\Models\Bracket;
use Illuminate\Foundation\Http\FormRequest;

class WinnerRequest extends FormRequest
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
                'description' => 'The winner of the match.',
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
            'player_id' => [
                'required',
                function($attr, $value, $fail){
                    $req = request();
                    $match = Bracket::where('tournament_id', $req->tournament)
                        ->where('match', $req->match_num)->with('children')->first();
                    if($match->children[0]->player_id !== $value && $match->children[1]->player_id !== $value){
                        $fail('Winner must come from previous match');
                    }
                }
            ]
        ];
    }

    public function messages()
    {
        return [
            'player_id.required' => 'Please provide winner\'s player ID'
        ];
    }
}
