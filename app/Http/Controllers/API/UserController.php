<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\User;
use GuzzleHttp\Psr7\Message;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use Laravel\Fortify\Rules\Password;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function register(Request $request)
    {
        try {
           $request->validate([
               'name' => ['required', 'string', 'max:255'],
               'username' => ['required', 'string', 'max:255', 'unique:users'],
               'email' => ['required', 'string', 'email','max:255'],
               'phone' => ['nullable', 'string','max:255'],
               'password' => ['required', 'string', new Password],
           ]);
           User::create([
               'name' => $request->name,
               'username' => $request->username,
               'email' => $request->email,
               'phone' => $request->phone,
               'password' => Hash::make($request->password),
           ]);

           $user = User::where('email', $request->email)->first();

           $tokenResult = $user->createToken('authToken')->plainTextToken;

           return ResponseFormatter::success([
               'access_token' => $tokenResult,
               'token_type' => 'Bearer',
               'user' => $user
           ], 'User Registered');
        } catch (Exception $error)
        {
            return ResponseFormatter::error([
                'message' => 'Something when wrong',
                'error' => $error
            ], 'Authentication Failed', 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'email|required',
                'password' => 'required',
            ]);
            $credentiial = request(['email','password']);
            if (!Auth::attempt($credentiial)){
                return ResponseFormatter::error([
                    'message' => 'Unauthorized'
                ], 'Authentication Failed', 500);
            } 

            $user = User::where('email', $request->email)->first();

            if(! Hash::check($request->password, $user->password, [])){
                throw new \Exception('Invalid Credentials');
            }

            $tokenResult = $user->createToken('authToken')->plainTextToken;
            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user,
            ], 'Authenticated');
        } catch (\Exception $error) {
            return ResponseFormatter::error([
                'message' => 'Something when wrong',
                'error' => $error
            ], 'Authentication Failed', 500);
        }
    }

    public function fetch(Request $request)
    {
        return ResponseFormatter::success($request->user(),'Data Profile User Berhasil diambil');
    }
    
    public function updateProfile(Request $request)
    {
        $data = $request->all();

        $user = Auth::user();
        $user->update($data);

        return ResponseFormatter::success($user, 'Profile Update');
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccesToken()->delete();
        return ResponseFormatter::success($token, 'Token Revoked');
    }

}
