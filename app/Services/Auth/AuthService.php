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
            'is_active' => true,
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

        if (! $user->is_active) {
            throw new AccountDeactivatedException;
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
        if ($user->staffMember) {
            $staffMember = $user->staffMember;
            $staffMember->load(['officeLocation', 'division', 'jobTitle', 'user']);

            $userData['staff_member'] = [
                'id' => $staffMember->id,
                'full_name' => $staffMember->full_name,
                'profile_image' => $staffMember->profile_image,
                'staff_code' => $staffMember->staff_code,
                'personal_email' => $staffMember->personal_email,
                'mobile_number' => $staffMember->mobile_number,
                'birth_date' => $staffMember->birth_date,
                'gender' => $staffMember->gender,
                'home_address' => $staffMember->home_address,
                'nationality' => $staffMember->nationality,
                'passport_number' => $staffMember->passport_number,
                'country_code' => $staffMember->country_code,
                'region' => $staffMember->region,
                'city_name' => $staffMember->city_name,
                'postal_code' => $staffMember->postal_code,
                'office_location_id' => $staffMember->office_location_id,
                'office_location' => $staffMember->officeLocation?->title,
                'division_id' => $staffMember->division_id,
                'division' => $staffMember->division?->title,
                'job_title_id' => $staffMember->job_title_id,
                'job_title' => $staffMember->jobTitle?->title,
                'hire_date' => $staffMember->hire_date,
                'employment_status' => $staffMember->employment_status,
                'employment_type' => $staffMember->employment_type,
                'compensation_type' => $staffMember->compensation_type,
                'base_salary' => $staffMember->base_salary,
                'biometric_id' => $staffMember->biometric_id,
                'bank_account_name' => $staffMember->bank_account_name,
                'bank_account_number' => $staffMember->bank_account_number,
                'bank_name' => $staffMember->bank_name,
                'bank_branch' => $staffMember->bank_branch,
                'emergency_contact_name' => $staffMember->emergency_contact_name,
                'emergency_contact_phone' => $staffMember->emergency_contact_phone,
                'emergency_contact_relationship' => $staffMember->emergency_contact_relationship,
                'org_id' => $staffMember->org_id,
                'company_id' => $staffMember->company_id,
            ];
        }

        return [
            'user' => $userData,
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

    /**
     * Format user data for response.
     */
    protected function formatUserData(User $user): array
    {
        $user->load(['roles' => function ($query) {
            $query->orderBy('hierarchy_level');
        }, 'roles.permissions', 'staffMember', 'organization', 'company']);

        $roles = $user->roles;
        $primaryRole = $roles->sortBy('hierarchy_level')->first();
        $permissions = $user->getAllPermissions()->pluck('name')->unique()->values()->toArray();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $primaryRole ? $primaryRole->name : 'user',
            'role_display' => $primaryRole ? ucwords(str_replace('_', ' ', $primaryRole->name)) : 'User',
            'roles' => $roles->pluck('name')->toArray(),
            'permissions' => $permissions,
            'primary_role' => $primaryRole ? $primaryRole->name : 'user',
            'primary_role_icon' => $primaryRole ? $primaryRole->icon : 'User',
            'primary_role_hierarchy' => $primaryRole ? $primaryRole->hierarchy_level : 5,
            'staff_member_id' => $user->staffMember?->id,
            'org_id' => $user->org_id,
            'company_id' => $user->company_id,
            'organization_name' => $user->organization?->name,
            'company_name' => $user->company?->company_name,
        ];
    }
}
