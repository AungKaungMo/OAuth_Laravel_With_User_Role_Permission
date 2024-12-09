<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\ApiResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // public function __construct()
    // {
    //     $this->middleware('permission:user-list', ['only' => ['index']]);
    //     $this->middleware('permission:user-create', ['only' => ['store']]);
    //     $this->middleware('permission:user-edit', ['only' => ['update']]);
    //     $this->middleware('permission:user-delete', ['only' => ['destroy']]);
    // }

    public function index(Request $request)
    {
        $page = $request->query('page');
        $perPage = $request->query('per_page');

        $query = User::where('type', '!=', 'DEALER')
            ->select(['id', 'name', 'email', 'phone', 'status', 'is_deleted'])
            ->orderBy('id', 'desc');

        if ($page && $perPage) {
            $paginatedUsers = $query->paginate($perPage, $page);
        } else {
            $paginatedUsers = $query->get();
        }

        if ($paginatedUsers) {
            $paginatedUsers->transform(function ($user) {
                $child_result['id'] = $user->id;
                $child_result['name'] = $user->name;
                $child_result['email'] = $user->email;
                $child_result['status'] = $user->status;
                $child_result['is_deleted'] = $user->is_deleted;

                $role_ids = DB::table('model_has_roles')
                    ->where('model_id', $user->id)
                    ->pluck('role_id')->toArray();

                $roles = DB::table('roles')
                    ->whereIn('id', $role_ids)
                    ->get(['id', 'name', 'status', 'is_deleted']);

                $child_result['roles'] = $roles;

                return $child_result;
            });

            return ApiResponse::success($paginatedUsers, 'Get all users successful', 200);
        }

        return ApiResponse::error(null, 'User data is empty', 204);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required',
            'password' => 'required|same:confirm-password',
            // 'type' => 'required'
            // 'roles' => 'required'
        ]);

        if ($validator->fails()) {
            return ApiResponse::error(null, "Field validation Error", 400);
        }

        $input = $request->all();
        $input['password'] = Hash::make($input['password']);
        $input['type'] = $input['type'] ?? 'USER';
        $input['status'] = 1;
        $input['is_deleted'] = 0;

        $user = User::create($input);

        if (!empty($request->roles)) {
            $user->assignRole($request->input('roles'));
        }

        $user = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'type' => $user->type,
            'status' => $user->status,
            'is_deleted' => $user->is_deleted,
            'role'  => $user->roles ? $user->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'status' => $role->status,
                    'is_deleted' => $role->is_deleted
                ];
            })->toArray() : null
        ];
        return ApiResponse::success($user, 'User created successfully.', 201);
    }

    public function show($id)
    {
        $user = User::where('id', $id)->first(['id', 'name', 'email', 'phone', 'type', 'status', 'is_deleted']);
        $role_ids = DB::table('model_has_roles')->where('model_id', $id)->pluck('role_id')->toArray();
        $roles = DB::table('roles')->whereIn('id', $role_ids)->get(['id', 'name', 'status', 'is_deleted']);

        $user = [
            "id" => $user->id,
            "name" => $user->name,
            "email" => $user->email,
            "phone" => $user->phone,
            "status" => $user->status,
            "is_deleted" => $user->is_deleted,
            "roles" => $roles ? $roles->map(function ($role) {
                return [
                    "id" => $role->id,
                    "name" => $role->name,
                    "status" => $role->status,
                    "is_deleted" => $role->is_deleted
                ];
            })->toArray() : null
        ];

        if (!empty($user) && $user != null) {
            return ApiResponse::success($user, 'Get User detail is Successful', 200);
        } else {
            return ApiResponse::error(null, "User not found with your provided id", 404);
        }
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!empty($user)) {
            $user->name = $request->has('name') ? $request->name : $user->name;
            $user->email = $request->has('email') ? $request->email : $user->email;
            $user->phone = $request->has('phone') ? $request->phone : $user->phone;
            $user->type = $request->has('type') ? $request->type : $user->type;
            $user->status = 1;
            $user->is_deleted = 0;
            $user->save();

            if (!empty($request->roles)) {
                DB::table('model_has_roles')
                    ->where('model_id', $id)
                    ->delete();

                $user->assignRole($request->input('roles'));
            }

            $user = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'type' => $user->type,
                'status' => $user->status,
                'is_deleted' => $user->is_deleted,
                'roles' => $user->roles ? $user->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'status' => $role->status,
                        'is_deleted' => $role->is_deleted
                    ];
                })->toArray() : null
            ];

            return ApiResponse::success($user, 'User updated successfully.', 200);
        }

        return ApiResponse::error(null, 'User not found with your provided ID.', 404);
    }

    public function destroy($id)
    {
        $user = User::find($id);
        if ($user) {
            $user->update([
                'is_deleted' => 1
            ]);
            return ApiResponse::success(null, 'User deleted successfully', 200);
        } else {
            return ApiResponse::error(null, 'User not found with your provided id', 404);
        }
    }

    public function unArchive($id)
    {
        $user = User::find($id);
        if ($user) {
            $user->update([
                'is_deleted' => 0
            ]);
            return ApiResponse::success(null, 'User archived successfully', 200);
        } else {
            return ApiResponse::error(null, 'User not found with your provided id', 404);
        }
    }

    public function permanentDestroy($id)
    {
        $user = User::find($id);
        if (!$user) {
            return ApiResponse::error(null, 'No dealer found with your provided id', 404);
        }

        $user->delete();
        return ApiResponse::success(null, 'User permanently deleted', 200);
    }

    public function changePassword(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error(null, 'Field validation error', 400);
        }

        $user = User::find($id);

        if (!$user) {
            return ApiResponse::error(null, 'User not found with your provided id', 404);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return ApiResponse::error(null, 'Your current password is not valid', 401);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return ApiResponse::success(null, 'Change Password is successful', 200);
    }
}
