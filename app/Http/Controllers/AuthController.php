<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Authentication
 * 
 * APIs to authenticate or register a new user.
 */
class AuthController extends Controller
{
    /**
     * Login
     */
    public function login(LoginRequest $request){
        if(auth()->user()){
            return response()->json(['message' => 'Already authenticated'], 401);
        }
        if(!$token = auth()->attempt(['name' => $request->username, 'password' => $request->password])){
            return response()->json(['message' => 'Authentication failed'], 401);
        }
        return response()->json(['message' => 'Login Successful'])
            ->withCookie(cookie('token', $token, 24 * 60, null, 'tournaments-pi.herokuapp.com', false, true));
    }
    
    /**
     * Register
     */
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

        return response()->json(['message' => 'Registration successful'], 200)
            ->withCookie(cookie('token', $token, 24 * 60, null, null, false, true));
    }
}
