<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return ['status' => 400, 'message' => 'Malformed request'];
        }

        $credentials = $validator->valid();

        if (Auth::attempt($credentials)) {
            $user = $request->user();
            $token = $user->createToken('diet-journal-auth-token');
            $user->token = $token->plainTextToken;

            return ['status' => 'success', 'user' => $user];
        }

        return ['status' => 401, 'message' => 'Unauthorized'];
    }
}
