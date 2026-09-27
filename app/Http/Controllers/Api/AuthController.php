<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRestaurantRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\RestaurantResource;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(protected AuthService $authService) {}

    public function registerRestaurant(RegisterRestaurantRequest $request)
    {
        $result = $this->authService->registerRestaurant($request->validated());

        return $this->success([
            'token' => $result['token'],
            'user' => new UserResource($result['user']),
            'restaurant' => new RestaurantResource($result['restaurant']),
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $result = $this->authService->login($request->email, $request->password);

        return $this->success([
            'token' => $result['token'],
            'user' => new UserResource($result['user']),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->message('Logged out successfully');
    }

    public function me(Request $request)
    {
        return $this->success([
            'user' => new UserResource($request->user()->load('branch')),
        ]);
    }

    public function changePassword(ChangePasswordRequest $request)
    {
        $this->authService->changePassword($request->user(), $request->current_password, $request->new_password);

        return $this->message('Password changed successfully');
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $this->authService->sendPasswordResetLink($request->email);
        
        return $this->message('If an account exists with that email, a reset link has been sent.');
    }

    public function resetPassword(ResetPasswordRequest $request, string $token)
    {
        $this->authService->resetPassword($request->email, $token, $request->password);

        return $this->message('Password reset successfully. You can now sign in.');
    }
}