<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{

    public function login(Request $request)
    {
        $user = User::where('username', $request->username)->first();

        if (!$user) {
            return response()->json(['error_message' => 'Invalid Username']);
        } else if (Hash::check($request->password, $user->password)) {
            $user->api_token = bin2hex(openssl_random_pseudo_bytes(16));
            $user->save();
            return $user->only('id', 'username', 'department_id', 'api_token');
        } else {
            return response()->json(['error_message' => 'Invalid Credential']);
        }
    }

    public function logout(Request $request)
    {

        $user = User::find($request->id);
        
        if($user) {
            
            DB::beginTransaction();

            $user->api_token = null;
            $user->save();

            DB::commit();

            return response()->json(['result' => 1]);
        }
        // 1 representing success and 0 failure
        return response()->json(['result' => 0]);
    }

}
