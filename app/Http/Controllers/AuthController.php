<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * @group Authentication Endpoints
 */
class AuthController extends Controller
{
    public function __construct(){
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }
    
    /**
     * Login
     * 
     * Returns JWT, user ID, and username if credentials match.
     * 
     * @responseField message string Status message.
     * @responseField token string JWT value.
     * @responseField user object.
     * @responseField user.id int user ID.
     * @responseField user.name int Authenticated username.
     * 
     * @response 200 scenario="OK" {"message": "Login successful", "token": "JWT TOKEN", "user": {"id": 1, "name": "userame"}}
     * @response 401 scenario="Unauthorized" {"message": "Already authenticated"}
     * @responseFile 422 scenario="Unprocessable Entity" ./responses/auth/login.error.json
     */
    public function login(LoginRequest $request){
        if(auth()->user()){
            return response()->json(['message' => 'Already authenticated'], 401);
        }
        if(!$token = auth()->attempt(['name' => $request->username, 'password' => $request->password])){
            return response()->json(['message' => 'Authentication failed'], 401);
        }

        $user = auth()->user();

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name
            ]
        ]);
    }

    /**
     * Register
     * 
     * Registers a new user. Returns JWT, user ID, and username.
     * 
     * @responseField message string Status message.
     * @responseField token string JWT value.
     * @responseField user object.
     * @responseField user.id int user ID.
     * @responseField user.name int Authenticated username.
     * 
     * @response 200 scenario="OK" {"message": "Registration successful", "token": "JWT TOKEN", "user": {"id": 1, "name": "userame"}}
     * @response 401 scenario="Unauthorized" {"message": "Already authenticated"}
     * @responseFile 422 scenario="Unprocessable Entity" ./responses/auth/register.error.json
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
