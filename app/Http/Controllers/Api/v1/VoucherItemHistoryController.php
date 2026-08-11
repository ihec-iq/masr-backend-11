<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Voucher\InputVoucherItemVSelectResource;
use App\Http\Resources\Voucher\VoucherItemHistoryResource;
use App\Http\Resources\Voucher\VoucherItemHistoryResourceCollection;
use App\Models\VoucherItemHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VoucherItemHistoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->ok(VoucherItemHistoryResource::collection(VoucherItemHistory::get()));
    }

    public function filter(Request $request)
    {
        $filter_bill = [];
        $request->filled('limit') ? $limit = $request->limit : $limit = 10;
        // if (! $request->isNotFilled('name') && $request->name != '') {
        //     $filter_bill[] = ['name', 'like', '%'.$request->name.'%'];
        // }
        if (
            !$request->isNotFilled('inputVoucherItemId') &&
            $request->inputVoucherItemId != ''
            && $request->inputVoucherItemId != '0'
        ) {
            $filter_bill[] = ['input_voucher_item_id', $request->inputVoucherItemId];
        }
        if (!$request->isNotFilled('employeeId') && $request->employeeId != '' && $request->employeeId != '0') {
            $filter_bill[] = ['employee_id', $request->employeeId];
        }
        $filter_bill[] = ['voucher_item_historiable_type', '!=', 'App\\Models\\InputVoucherItem'];
        //return ($filter_bill);

        $data = VoucherItemHistory::orderBy('id', 'desc')->where($filter_bill)->paginate($limit);
        //$data = VoucherItemHistory::orderBy('id', 'desc')->paginate($limit);

        if (empty($data) || $data == null) {
            return $this->error(__('general.loadFailed'));
        } else {
            return $this->ok(new VoucherItemHistoryResourceCollection($data));
        }
    }
    public function reportStorage(Request $request)
    {
        $filter_bill = [];
        $request->filled('limit') ? $limit = $request->limit : $limit = 10;

        // if (
        //     !$request->isNotFilled('inputVoucherItemId') &&
        //     $request->inputVoucherItemId != ''
        //     && $request->inputVoucherItemId != '0'
        // ) {
        //     $filter_bill[] = ['input_voucher_item_id', $request->inputVoucherItemId];
        // }
        if (!$request->isNotFilled('employeeId') && $request->employeeId != '' && $request->employeeId != '0') {
            $filter_bill[] = ['employee_id', $request->employeeId];
        }
        // $filter_bill[] = ['voucher_item_historiable_type', '!=', 'App\\Models\\InputVoucherItem'];
        //return ($filter_bill);

        $query = VoucherItemHistory::where($filter_bill)
            ->join('items', 'voucher_item_histories.item_id', '=', 'items.id')
            ->join('item_categories', 'items.item_category_id', '=', 'item_categories.id')
            ->join('input_voucher_items', 'voucher_item_histories.input_voucher_item_id', '=', 'input_voucher_items.id');
        // ->join('input_voucher_items', 'input_voucher_items.id', '=', 'voucher_item_histories.input_voucher_item_id')
        // ->join('stocks', 'stocks.id', '=', 'input_voucher_items.stock_id')


        if (!$request->isNotFilled('name') && $request->name != '') {
            $query->where('items.name', 'like', '%'.$request->name.'%');
        }


        $data = $query->selectRaw('
            voucher_item_histories.item_id as itemId,
            voucher_item_histories.input_voucher_item_id as inputVoucherItemId,
            input_voucher_items.input_voucher_id as inputVoucherId,
            items.name as itemName,
            items.code as code,
            items.description as ItemDescription,
            item_categories.id as itemCategoryId,
            item_categories.name as itemCategoryName,
            voucher_item_histories.price as price,
            SUM(IF(voucher_item_histories.count > 0, voucher_item_histories.count, 0)) as `countIn`,
            SUM(IF(voucher_item_histories.count < 0, ABS(voucher_item_histories.count), 0)) as `countOut`,
            SUM(voucher_item_histories.count) as count,
            SUM(voucher_item_histories.count) as total
        ')
            ->groupBy(
                'voucher_item_histories.item_id',
                'voucher_item_histories.input_voucher_item_id',
                'input_voucher_items.input_voucher_id',
                'items.name',
                'voucher_item_histories.price',
                'item_categories.id',
                'item_categories.name',
                'items.code',
                'items.description'
            )
            ->get();
        // $data = VoucherItemHistory::orderBy('id', 'desc')->where($filter_bill)->paginate($limit);
        return $this->ok(InputVoucherItemVSelectResource::collection($data));
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
