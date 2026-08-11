<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\User\RolePermissionResource;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Arr;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = Role::get();

        if (empty($data) || $data == null) {
            return $this->error(__('general.loadFailed'));
        } else {
            return $this->ok(RolePermissionResource::collection($data));
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'checkedPermission' => 'array|nullable',
        ]);
        try {
            $role = Role::create([
                'name'       => $request->name,
                'guard_name' => 'web'
            ]);
            if ($request->filled('checkedPermission')) {
                $permissions = Arr::flatten($request->checkedPermission);
                $validPermissions = Permission::whereIn('name', $permissions)
                    ->pluck('name')
                    ->toArray();
                if (!empty($validPermissions)) {
                    $role->syncPermissions($validPermissions);
                }
            }
            $role->load('permissions');
            return $this->ok(new RolePermissionResource($role));
        } catch (\Throwable $e) {
            // \Log::error('Role creation error: ' . $e->getMessage());
            return $this->error(__('general.saveUnsuccessfully'));
        }
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(int $id)
    {
        $data = Role::findById($id, 'web');

        if (empty($data) || $data == null) {
            return $this->error(__('general.loadFailed'));
        } else {
            return $this->ok(new RolePermissionResource($data));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, int $id)
    {
        $request->validate([
            'name'              => 'nullable|string',
            'checkedPermission' => 'array|nullable', // لائحة الصلاحيات المختارة
        ]);

        // ✅ العثور على الدور المطلوب أو إرجاع خطأ إذا غير موجود
        $role = Role::findById($id, 'web');
        if (!$role) {
            return $this->error(__('general.loadFailed'));
        }

        // ✅ تحديث الاسم إذا تم إرساله
        if ($request->filled('name')) {
            $role->name = $request->name;
            $role->save();
        }

        // ✅ تحديث الصلاحيات إذا تم إرسالها
        if ($request->has('checkedPermission')) {
            // هنا تأكدنا أنه تم إرسال صلاحيات، حتى لو كانت فارغة
            $permissions = Arr::flatten($request->checkedPermission ?? []);

            // تحقق من أن الصلاحيات المرسلة موجودة فعليًا
            $validPermissions = Permission::whereIn('name', $permissions)->pluck('name')->toArray();

            // تحديث الصلاحيات باستخدام syncPermissions
            $role->syncPermissions($validPermissions);
        }

        // ✅ إعادة الدور مع الصلاحيات بعد التحديث
        $role->load('permissions');

        return $this->ok(new RolePermissionResource($role));
    }

    /**
     * set role for user.
     */
    public function set_role(int $role_id, int $user_id)
    {
        $user = User::find($user_id);
        if (empty($user) || $user == null) {
            return $this->error(__('general.loadFailed'));
        }
        $role = Role::findById($role_id, 'web');
        if (empty($role) || $role == null) {
            return $this->error(__('general.loadFailed'));
        }
        $user->assignRole($role);

        return $this->ok(new UserResource($user));
    }

    /**
     * remove role form user.
     */
    public function remove_role(int $role_id, int $user_id)
    {
        $user = User::find($user_id);
        $user->removeRole($role_id);

        if (empty($user) || $user == null) {
            return $this->error(__('general.loadFailed'));
        } else {
            return $this->ok(new UserResource($user));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(int $id)
    {
        $data = Role::findById($id, 'web');
        $data->delete();

        if (empty($data) || $data == null) {
            return $this->error(__('general.deleteUnsuccessfully'));
        } else {
            return $this->ok(new RolePermissionResource($data));
        }
    }
}
