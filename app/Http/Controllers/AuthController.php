<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Pengurus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required','string'], // can be nim or email or pengurus username
            'password' => ['required','string']
        ]);

        // attempt by pengurus username first
        $pengurus = Pengurus::where('username', $credentials['username'])->first();
    if ($pengurus && $pengurus->password && Hash::check($credentials['password'], $pengurus->password)) {
            $user = $pengurus->user;
            if ($user->role !== 'pengurus') {
                return response()->json(['message' => 'Not authorized'], 403);
            }
            $guard = auth('api');
            if (!method_exists($guard, 'login')) {
                return response()->json(['message' => 'JWT guard not configured on server'], 500);
            }
            $token = call_user_func([$guard,'login'], $user);
            return $this->respondWithToken($token, $user);
        }

        // attempt by email
        $field = filter_var($credentials['username'], FILTER_VALIDATE_EMAIL) ? 'email' : 'nim';
        $user = User::where($field, $credentials['username'])->where('role','pengurus')->first();
    if (!$user || !$user->password || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }
        $guard = auth('api');
        if (!method_exists($guard, 'login')) {
            return response()->json(['message' => 'JWT guard not configured on server'], 500);
        }
        $token = call_user_func([$guard,'login'], $user);
        return $this->respondWithToken($token, $user);
    }

    public function logout()
    {
        $guard = auth('api');
        if (!method_exists($guard, 'logout')) {
            return response()->json(['message' => 'JWT guard not configured on server'], 500);
        }
        call_user_func([$guard,'logout']);
        return response()->json(['message' => 'Logged out']);
    }

    public function refresh()
    {
        $guard = auth('api');
        if (!method_exists($guard, 'refresh')) {
            return response()->json(['message' => 'JWT guard not configured on server'], 500);
        }
        $token = call_user_func([$guard,'refresh']);
        return $this->respondWithToken($token, $guard->user());
    }

    protected function respondWithToken($token, $user)
    {
        $username = optional($user->pengurus)->username;
        $ttlMinutes = (int) config('jwt.ttl', 180);
        return response()->json([
            'message' => 'Login success',
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $ttlMinutes * 60,
            'username' => $username,
            'user' => $user,
        ]);
    }
}
