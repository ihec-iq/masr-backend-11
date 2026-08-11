<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Voucher\RetrievalVoucherItemTypeResource;
use App\Models\RetrievalVoucherItemType;
use Illuminate\Http\Request;

class RetrievalVoucherItemTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return RetrievalVoucherItemTypeResource::collection(RetrievalVoucherItemType::get());
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
