<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Validator;


class AuthController extends Controller
{

public function register(Request $request) 
{

        $validated = Validator::make($request->all(), [
            'name'=>'required',
            'email'=>'required|email|unique:users',
            'password'=>'required|min:6',
        ]);

        if (User::where('email', $request->email)->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'User already registered with this email . please login'
            ], 409); 
    }
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->save();

        $wallet = new Wallet();
        $wallet->user_id = $user->id;
        $wallet->balance = 0;
        $wallet->currency = 'INR';
        $wallet->save();

        $token = auth()->login($user);
        return response()->json([
            'success'=>true,
            'message'=>'User registered successfully',
            'user'=>$user
        ], 201);
}

public function login(Request $request)
{
    $credentials = $request->only('email','password');

    if (!$token = auth('api')->attempt($credentials)) {
        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    return response()->json([
        'success' => true,
        'message' => 'Login successful',
        'token' => $token,
        'token_type' => 'bearer',
    ]);
}

public function me()
{
    return response()->json([
        'success' => true,
        'message' => 'User profile retrieved successfully',
        'user' => auth()->user()
    ]);
}

}
