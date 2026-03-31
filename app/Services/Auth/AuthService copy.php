<?php

namespace App\Services\Auth;

use App\Exceptions\AccountDeactivatedException;
use App\Exceptions\InvalidCredentialsException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole('user');

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $this->formatUserData($user),
            'token' => $token,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw new InvalidCredentialsException;
        }


        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $this->formatUserData($user),
            'token' => $token,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ];
    }

    /**
     * Revoke current access token.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    /**
     * Get user profile with roles and permissions.
     */
    public function getProfile(User $user): array
    {
        $userData = $this->formatUserData($user);

        // Add full staff member details if user has a staff member record
        if (method_exists($user, 'staffMember') && $user->staffMember) {
            $staffMember = $user->staffMember;
            $staffMember->load(['officeLocation', 'division', 'jobTitle', 'user']);

            $userData['staff_member'] = [
                'id' => $staffMember->id,
                'full_name' => $staffMember->full_name,
                'profile_image' => $staffMember->profile_image,
                'staff_code' => $staffMember->staff_code,
                // ... other fields as per requirement ...
            ];
        }

        return [
            'user' => $userData,
        ];
    }

    /**
     * Format user data for response.
     */
    protected function formatUserData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'roles' => [], 
            'permissions' => [],
        ];
    }

    /**
     * Send password reset link.
     */
    public function sendPasswordResetLink(string $email): string
    {
        $status = Password::sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return $status;
    }

    /**
     * Reset password with token.
     */
    public function resetPassword(array $data): string
    {
        $status = Password::reset(
            $data,
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return $status;
    }
}
