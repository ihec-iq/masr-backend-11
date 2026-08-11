<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Permission::get();
        // $data['permissions'] =  $data->pluck('name');

        // return Auth::user()->getAllPermissions()->pluck('name') ;

        // // $permissions = getAllPermissions()->pluck('name');

        if (empty($data) || $data == null) {
            return $this->error(__('general.saveUnsuccessfully'));
        } else {
            return $this->ok(PermissionResource::collection($data), __('general.saveSuccessfully'));
        }
    }

    /**
     * get user permissions.
     */
    public function get_user_permission(int $user_id)
    {
        $data = User::find($user_id)->getAllPermissions();

        if (empty($data) || $data == null) {
            return $this->error(__('general.saveUnsuccessfully'));
        } else {
            return $this->ok(PermissionResource::collection($data), __('general.saveSuccessfully'));
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
