<?php

namespace App\Http\Controllers\API\Admin;

use App\ApiResponse;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
// use App\Http\Middleware\CustomValidation;

class RoleController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('permission:role-list', ['only' => ['index']]);
    //     $this->middleware('permission:role-create', ['only' => ['store']]);
    //     $this->middleware('permission:role-edit', ['only' => ['update']]);
    //     $this->middleware('permission:role-delete', ['only' => ['destroy']]);
    // }

    public function index(Request $request)
    {
        $page = $request->query('page');
        $perPage = $request->query('per_page');

        $query = Role::select(['id', 'name', 'status', 'is_deleted'])
            ->orderBy('id', 'desc');

        if ($page && $perPage) {
            $roles = $query->paginate($perPage, $page);
        } else {
            $roles = $query->get();
        }

        return ApiResponse::success($roles, 'Success', 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:roles,name',
            // 'permissions' => 'required|array',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error(null, "Field validation error", 400);
        }

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'api',
            'status' => 1,
            'is_deleted' => 0
        ]);

        if (!empty($request->permissions)) {
            $role->syncPermissions($request->permissions);
        }

        $role = [
            "id" => $role->id,
            "name" => $role->name,
            "status" => $role->status,
            "is_deleted" => $role->is_deleted,
            "permissions" => $role->permissions ? $role->permissions->map(function ($permission) {
                return [
                    "id" => $permission->id,
                    "name" => $permission->name,
                    "status" => $permission->status,
                    "is_deleted" => $permission->is_deleted
                ];
            })->toArray() : null
        ];

        return ApiResponse::success($role, 'Role create successful', 201);
    }

    public function show($id)
    {
        $role = Role::find($id);
        $rolePermissions = Permission::join("role_has_permissions", "role_has_permissions.permission_id", "=", "permissions.id")
            ->where("role_has_permissions.role_id", $id)
            ->select('permissions.id', 'permissions.name', 'permissions.status', 'permissions.is_deleted')
            ->get();

        if (!empty($role) && $role != null) {

            $role = [
                'id' => $role->id,
                'name' => $role->name,
                'status' => $role->status,
                'is_deleted' => $role->is_deleted,
                'permissions' => $rolePermissions
            ];

            return ApiResponse::success($role, 'Get role detail successul', 200);
        } else {
            return ApiResponse::error(null, 'Role not found with your provided id', 404);
        }
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required|unique:roles,name,' . $id,
            // 'permissions' => 'required|array',
        ]);

        $role = Role::find($id);

        if ($role) {
            $role->update([
                'name' => $request->name,
                'status' => 1,
                'is_deleted' > 0
            ]);

            if ($request->permissions) {
                $role->syncPermissions($request->permissions);
            }

            $role = [
                'id' => $role->id,
                'name' => $role->name,
                'status' => $role->status,
                'is_deleted' => $role->is_deleted,
                'permissions' => $role->permissions ? $role->permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'status' => $permission->status,
                        'is_deleted' => $permission->is_deleted
                    ];
                })->toArray() : null
            ];

            return ApiResponse::success($role, 'Successfully updated', 201);
        } else {
            return ApiResponse::error(null, 'Role not found with your provided id', 404);
        }
    }

    public function destroy($id)
    {
        $role = Role::find($id);
        if ($role) {
            $role->update([
                'is_deleted' => 1
            ]);
            return ApiResponse::success(null, 'Role deleted successfully', 200);
        } else {
            return ApiResponse::error(null, 'Role not found with your provided id', 404);
        }
    }
}
