<?php

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\Restaurant;
use App\Models\Plan;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthService
{
    public function registerRestaurant(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $plan = Plan::query()
                ->where('is_active', true)
                ->when($data['plan'] ?? null, fn ($query, $slug) => $query->where('slug', $slug))
                ->orderBy('display_order')
                ->orderBy('id')
                ->first();

            if (! $plan) {
                throw ApiException::badRequest('No active plans are available for registration.');
            }

            $restaurant = Restaurant::create([
                'name' => $data['restaurant_name'],
                'subscription_plan' => $plan->slug,
                'subscription_status' => 'active',
                'subscription_start_date' => now(),
                'subscription_end_date' => now()->addDays(30),
                'max_orders_per_month' => 200,
            ]);

            Tenant::set($restaurant->id);

            $owner = User::create([
                'restaurant_id' => $restaurant->id,
                'name' => $data['owner_name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $owner->assignRole('owner');

            $token = $owner->createToken('auth_token')->plainTextToken;

            return ['token' => $token, 'user' => $owner, 'restaurant' => $restaurant];
        });
    }

    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! $user->is_active || ! Hash::check($password, $user->password)) {
            throw ApiException::unauthorized('Invalid credentials');
        }

        if (! $user->hasRole('superadmin')) {
            $restaurant = Restaurant::allRestaurants()->find($user->restaurant_id);

            if (! $restaurant || ! $restaurant->is_active) {
                throw ApiException::forbidden('This restaurant account is suspended. Contact support.');
            }
            if (! $restaurant->isSubscriptionActive()) {
                throw ApiException::paymentRequired('Your subscription has expired. Please contact support to renew.');
            }
        }

        $user->update(['last_login_at' => now()]);
        $user->load('branch');

        $token = $user->createToken('auth_token')->plainTextToken;

        return ['token' => $token, 'user' => $user];
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ApiException::badRequest('Current password is incorrect');
        }

        $user->update(['password' => $newPassword]); // 'hashed' cast rehashes automatically
    }

    public function sendPasswordResetLink(string $email): void
    {
        Password::sendResetLink(['email' => $email]);
    }

    public function resetPassword(string $email, string $token, string $newPassword): void
    {
        $status = Password::reset(
            ['email' => $email, 'token' => $token, 'password' => $newPassword],
            function (User $user, string $password) {
                $user->update(['password' => $password]);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ApiException::badRequest('This reset link is invalid or has expired');
        }
    }
}