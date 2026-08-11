<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BackupAdminRequest;
use App\Models\BackupAdmin;
use Illuminate\Support\Facades\Log;

class BackupAdminController extends Controller
{
    public function index()
    {
        return BackupAdmin::orderBy('id', 'desc')->get();
    }

    public function store(BackupAdminRequest $request)
    {
        $admin = BackupAdmin::create($request->validated());
        return response()->json($admin, 201);
    }

    public function update(BackupAdminRequest $request, BackupAdmin $backupAdmin)
    {
        Log::alert($request);
        $backupAdmin->update($request->validated());
        return $backupAdmin;
    }

    public function destroy(BackupAdmin $backupAdmin)
    {
        $backupAdmin->delete();
        return response()->json(['deleted' => true]);
    }
}
