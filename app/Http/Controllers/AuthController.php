<?php

namespace App\Http\Controllers;

use App\Mail\RegisterMail;
use App\User;
use App\UserProfiles;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException as ValidationValidationException;


use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;


class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // create token sanctum
        $token = $user->createToken('member', ['accessLogin'])->plainTextToken;

        return response()->json([
            'message' => 'Login Sukses',
            'data' => [
                'token' => $token,
            ],
        ], 200);
    }

    public function registration(Request $request)
    {
        $validateData = Validator::make($request->only('name', 'email', 'password'), [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|max:12'
        ]);

        if ($validateData->errors()->has('name')) {
            return response()->json([
                'message' => 'Name harus diisi'
            ], 400);
        }

        if ($validateData->errors()->has('email')) {
            return response()->json([
                'message' => 'Email tidak dapat digunakan'
            ], 400);
        }

        if ($validateData->errors()->has('password')) {
            return response()->json([
                'message' => 'Password Minimal 6 Karakter dan Maksimal 6 Karakter'
            ], 400);
        }

        try {
            DB::beginTransaction();
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = bcrypt($request->password);
            $user->save();

            $userProfile = new UserProfiles();
            $userProfile->user_id = $user->id;
            $userProfile->save();
            // send email
            Mail::to($user->email)->send(new RegisterMail($user->name));

            DB::commit();

            $status = true;
        } catch (Exception $e) {
            DB::rollback();
            $status = false;
        }

        if ($status == true) {
            return response()->json([
                'message' => 'Data berhasil disimpan'
            ], 200);
        } else {
            return response()->json([
                'message' => 'Data gagal disimpan'
            ], 200);
        }
    }
}
