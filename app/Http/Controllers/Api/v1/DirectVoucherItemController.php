<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Voucher\DirectVoucherItemResource;
use App\Http\Resources\Voucher\DirectVoucherItemResourceCollection;
use App\Models\DirectVoucherItem;
use Illuminate\Http\Request;

class DirectVoucherItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $result = DirectVoucherItem::get();

        return $this->ok($result);
    }

    /**
     * Display a listing of the resource.
     */
    public function getAvailableItemsVSelect()
    {
        // $result = DirectVoucherItem::select('item')->get();
        $result = DirectVoucherItem::get();

        return $this->ok($result);
    }

    public function filter(Request $request)
    {
        $filter_bill = [];
        $request->filled('limit') ? $limit = $request->limit : $limit = 10;

        if (! $request->isNotFilled('item') && $request->item != '') {
            $filter_bill[] = ['item', 'like', '%'.$request->item.'%'];
        }
        if (! $request->isNotFilled('notes') && $request->notes != '') {
            $filter_bill[] = ['notes', 'like', '%'.$request->notes.'%'];
        }

        $data = DirectVoucherItem::orderBy('id', 'desc')->where($filter_bill)->paginate($limit);
        if (empty($data) || $data == null) {
            return $this->error(__('general.loadFailed'));
        } else {
            //return $this->ok($data);
            return $this->ok(new DirectVoucherItemResourceCollection($data));
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $directVoucherItem = new DirectVoucherItem();
        $directVoucherItem->direct_voucher_id = $request->DirectVoucherId;
        $directVoucherItem->item = $request->item;
        //$directVoucherItem->description = $request->description;
        $directVoucherItem->count = $request->count;
        $directVoucherItem->price = $request->price * 100;
        $directVoucherItem->value = $request->price * $request->count * 100;
        $directVoucherItem->notes = $request->notes;
        $directVoucherItem->save();

        return $this->ok(new DirectVoucherItemResource($directVoucherItem));

    }

    /**
     * Display the specified resource.
     */
    public function show(DirectVoucherItem $directVoucherItem)
    {
        return $this->ok(new DirectVoucherItemResource($directVoucherItem));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DirectVoucherItem $directVoucherItem)
    {
        $directVoucherItem->direct_voucher_id = $request->DirectVoucherId;
        $directVoucherItem->item = $request->item;
        //$directVoucherItem->description = $request->description;
        $directVoucherItem->count = $request->count;
        $directVoucherItem->price = $request->price * 100;
        $directVoucherItem->value = $request->price * $request->count * 100;
        $directVoucherItem->notes = $request->notes;
        $directVoucherItem->save();

        return $this->ok(new DirectVoucherItemResource($directVoucherItem));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $data = DirectVoucherItem::find($id);
        $data->delete();

        return $this->ok(null);
    }
}
