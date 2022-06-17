<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\ResponseFormatter;
use Laravel\Fortify\Rules\Password;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function register(Request $request){
        try {
            $request->validate([
                'name' => ['required', 'string','max:255'],
                'username' => ['required', 'string','max:255','unique:users,username'],
                'email' => ['required', 'email','max:255','unique:users,email','string'],
                'password' => ['required', 'string', new Password],
            ]);

            User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $user = User::where('email',$request->email)->first();
            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success([
                'access_token'=>$tokenResult,
                'token_type'=>'Bearer',
                'user'=>$user
            ],'User berhasil dibuat');
        }catch(Exception $error){
            return ResponseFormatter::error([
                'message'=>'Something went wrong',
                'error'=>$error->getMessage()
            ],'Auth failed',500);
        }
    }

    public function login(Request $request){
        try{
            $request->validate([
                'email'=> ['required','email'],
                'password'=> ['required']
            ]);

            $credentials = request(['email','password']);

            if(!Auth::attempt($credentials)){
                return ResponseFormatter::error([
                    'message'=>'unauthorized'
                ],'authentication failed',500);
            }

            $user =  User::where('email',$request->email)->first();

            if(!Hash::check($request->password, $user->password, [])){
                throw new \Exception('invalid credentials');
            }

            $tokenResult = $user->createToken('authToken')->plainTextToken;
            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ],'auth berhasil');
        }catch(Exception $error){
            return ResponseFormatter::error([
                'message'=>'something error ',
                'error'=> $error
            ],'authentication failed',500);
        }
    }

    public function fetch(Request $request){
        return ResponseFormatter::success($request->user(),'Data Profile user berhasil diambil');
    }

    public function updateProfile(Request $request){
        $data = $request->all();

        $user = Auth::user();
        $user->update($data);

        return ResponseFormatter::success($user,'profile updated');
    }

    public function logout(Request $request){
        $token = $request->user()->currentAccessToken()->delete();
        return ResponseFormatter::success($token,'Token revoked');
    }
}
