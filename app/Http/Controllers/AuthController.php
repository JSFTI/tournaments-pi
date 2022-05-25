<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(){
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }
    
    public function login(LoginRequest $request){
        if(auth()->user()){
            return response()->json(['message' => 'Already authenticated'], 401);
        }
        if(!$token = auth()->attempt(['name' => $request->username, 'password' => $request->password])){
            return response()->json(['message' => 'Authentication failed'], 401);
        }

        $user = auth()->user();

        return response()->json([
            'message' => 'Login Successful',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name
            ]
        ]);
    }
    
    public function register(RegisterRequest $request){
        if(auth()->user()){
            return response()->json(['message' => 'Already authenticated'], 401);
        }

        $user = new User();
        $user->name = $request->username;
        $user->email = $request->email;
        $user->password = password_hash($request->password, PASSWORD_ARGON2ID);
        $user->save();

        $token = auth()->login($user);

        return response()->json([
            'message' => 'Registration successful',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name
            ]
        ]);
    }
}
