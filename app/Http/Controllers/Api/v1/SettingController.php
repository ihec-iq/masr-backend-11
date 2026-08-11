<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Http\Requests\StoreSettingRequest;
use App\Http\Resources\SettingResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->ok(SettingResource::collection(Setting::get())); // Retrieve all settings
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSettingRequest $request)
    {
        $setting = Setting::create($request->validated()); // Create a new setting
        return $this->ok(new SettingResource($setting), 201); // Return the created setting with a 201 status
    }

    /**
     * Display the specified resource.
     */
    public function show(Setting $setting)
    {
        if ($setting) return $this->ok(new SettingResource($setting)); // Return the specified setting
        return $this->error(); // Return the specified setting
    }
    public function showByKey(Request $request)
    {
        $setting = Setting::where('key', $request->key)->first();
        if ($setting) return $this->ok(new SettingResource($setting)); // Return the specified setting
        return $this->error();
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(StoreSettingRequest $request, Setting $setting)
    {
        $setting->update($request->validated()); // Update the specified setting
        return $this->ok(new SettingResource($setting)); // Return the updated setting
    }
    /**
     * Update the specified resource in storage.
     */
    public function updateByKey(StoreSettingRequest $request)
    {
        $setting = Setting::where('key', $request->key)->first();
        if (!$setting) return $this->error('Setting not found', 404); // Return error if setting not found
        $setting->update($request->validated()); // Update the specified setting
        return $this->ok(new SettingResource($setting)); // Return the updated setting
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Setting $setting)
    {
        $setting->delete(); // Delete the specified setting
        return $this->ok(null, 204); // Return a 204 status for successful deletion
    }
}
