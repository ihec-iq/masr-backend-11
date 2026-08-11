<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vacation\StoreVacationReasonRequest;
use App\Http\Requests\Vacation\UpdateVacationReasonRequest;
use App\Http\Resources\Vacation\VacationReasonResource;
use App\Models\VacationReason;

class VacationReasonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->ok(VacationReasonResource::collection(VacationReason::get()));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVacationReasonRequest $request)
    {
        $vacationReason = new VacationReason();
        $vacationReason->name = $request->name;
        $vacationReason->save();

        return $this->ok(new VacationReasonResource($vacationReason));
    }

    /**
     * Display the specified resource.
     */
    public function show(VacationReason $vacationReason)
    {
        return $this->ok(new VacationReasonResource($vacationReason));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVacationReasonRequest $request, VacationReason $vacationReason)
    {
        $vacationReason->name = $request->name;
        $vacationReason->save();

        return $this->ok(new VacationReasonResource($vacationReason));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VacationReason $vacationReason)
    {
        $vacationReason->delete();

        return $this->ok(new VacationReasonResource($vacationReason));

    }
}
