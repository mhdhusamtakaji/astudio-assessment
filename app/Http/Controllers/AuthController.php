<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ValidationTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ValidationTrait;

    /**
     * Enforce auth middleware only for logout.
     */
    public function __construct()
    {
        // Only "logout" requires an authenticated user.
        $this->middleware('auth:api')->only(['logout']);
    }

    /**
     * Handle user registration.
     * POST /api/register
     */
    public function register(Request $request)
    {
        // Validate request data using our trait
        $data = $this->runValidation($request, [
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|email|unique:users,email',
            'password'   => 'required|string|min:6|confirmed',
        ]);

        // Create the user
        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'email'      => $data['email'],
            'password'   => Hash::make($data['password']),
        ]);

        // Generate a Passport token
        $token = $user->createToken('API Token')->accessToken;

        return response()->json([
            'message' => 'User registered successfully.',
            'user'    => $user,
            'token'   => $token
        ], 201);
    }

    /**
     * Handle user login.
     * POST /api/login
     */
    public function login(Request $request)
    {
        // Validate request data
        $data = $this->runValidation($request, [
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        // Attempt login
        if (!Auth::attempt([
            'email'    => $data['email'],
            'password' => $data['password']
        ])) {
            // Throw a validation exception if credentials are invalid
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Retrieve the authenticated user
        $user  = Auth::user();
        // Create a new token
        $token = $user->createToken('API Token')->accessToken;

        return response()->json([
            'message' => 'Login successful.',
            'user'    => $user,
            'token'   => $token
        ], 200);
    }

    /**
     * Handle user logout.
     * POST /api/logout
     */
    public function logout()
    {
        // Revoke the token used to authenticate the current request
        $token = Auth::user()->token();
        $token->revoke();

        return response()->json([
            'message' => 'Successfully logged out'
        ], 200);
    }
}
