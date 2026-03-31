<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        if ($request->has('company_id')) {
            return $this->getUsersByCompanyId($request);
        }
        return $this->getUsersByOrgId($request);
    }

    public function getUsersByOrgId(Request $request): JsonResponse
    {
        $query = User::with(['roles' => function ($q) {
            $q->orderBy('hierarchy_level');
        }]);

        // Filter by org_id only if provided in request
        if ($request->has('org_id')) {
            $query->where('org_id', $request->org_id);
        }

        // Filter by company_id only if provided in request
        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->has('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        $perPage = $request->get('per_page', 15);
        $users = $query->paginate($perPage);

        $users->getCollection()->transform(function ($user) {
            $primaryRole = $user->roles->sortBy('hierarchy_level')->first();
            $user->primary_role = $primaryRole ? $primaryRole->name : null;
            $user->primary_role_icon = $primaryRole ? $primaryRole->icon : null;
            $user->roles_list = $user->roles->pluck('name')->toArray();

            return $user;
        });

        return $this->success([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ], 'Users retrieved successfully');
    }

    public function getUsersByCompanyId(Request $request): JsonResponse
    {
        $authUser = $request->user();

        $query = User::with(['roles' => function ($q) {
            $q->orderBy('hierarchy_level');
        }]);

        // Filter by authenticated user's company_id
        if ($authUser->company_id) {
            $query->where('company_id', $authUser->company_id);
        } elseif ($request->has('company_id')) {
            // Fallback for super admins who might want to view a specific company
            $query->where('company_id', $request->company_id);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $perPage = $request->get('per_page', 15);
        $users = $query->paginate($perPage);

        $users->getCollection()->transform(function ($user) {
            $primaryRole = $user->roles->sortBy('hierarchy_level')->first();
            $user->primary_role = $primaryRole ? $primaryRole->name : null;
            $user->primary_role_icon = $primaryRole ? $primaryRole->icon : null;
            $user->roles_list = $user->roles->pluck('name')->toArray();

            return $user;
        });

        return $this->success([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ], 'Users retrieved successfully');
    }

    public function dropdown(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->has('org_id')) {
            $query->where('org_id', $request->org_id);
        }

        if ($request->has('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        $users = $query->where('is_active', true)
            ->get(['id', 'name', 'email']);

        return $this->success($users, 'Users for dropdown retrieved successfully');
    }
}
