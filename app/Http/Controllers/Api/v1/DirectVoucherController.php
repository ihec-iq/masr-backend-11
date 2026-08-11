<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Voucher\DirectVoucherRequest;
use App\Http\Resources\Voucher\DirectVoucherResource;
use App\Http\Resources\Voucher\DirectVoucherResourceCollection;
use App\Models\DirectVoucher;
use App\Models\DirectVoucherItem;
use Illuminate\Http\Request;

class DirectVoucherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->ok(DirectVoucherResource::collection(DirectVoucher::get()));
    }

    public function filter(Request $request)
    {
        $request->filled('limit') ? $limit = $request->limit : $limit = 10;

        $data = DirectVoucher::orderBy('id', 'desc');

        if (! $request->isNotFilled('name') && $request->name != '') {
            $data = $data->orWhere('number', 'like', '%'.$request->name.'%');
        }
        if (! $request->isNotFilled('name') && $request->name != '') {
            $data = $data->orWhere('notes', 'like', '%'.$request->name.'%');
        }
        if (! $request->isNotFilled('issueDateFrom') && $request->issueDateFrom != '') {
            $data = $data->where('date', '>=', $request->issueDateFrom, 'and', 'date', '<=', $request->issueDateTo);
        }
        $data = $data->paginate($limit);

        if (empty($data) || $data == null) {
            return $this->error(__('general.loadFailed'));
        } else {
            return $this->ok(new DirectVoucherResourceCollection($data));
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DirectVoucherRequest $request)
    {
        $data = DirectVoucher::create([
            'number' => $request->number,
            'date' => $request->date,
            'employee_id' => $request->employeeRequestId,
            'notes' => $request->notes,
            'user_create_id' => auth()->user()->id,
            'user_update_id' => auth()->user()->id,
        ]);
        $arrayItems = json_decode($request->items, true);
        $arrayItemInsert = [];
        foreach ($arrayItems as $key => $item) {
            $newItem = new DirectVoucherItem();
            $newItem->item = $item['item'];
            $newItem->count = $item['count'];
            $newItem->notes = $item['notes'];
            $newItem->employee_id = $request->employeeRequestId;
            $newItem->price = $item['price'] * 100;
            $newItem->value = $newItem->count * $newItem->price ;
            array_push($arrayItemInsert, $newItem);
        }
        $data->Items()->saveMany($arrayItemInsert);

        return $this->ok(new DirectVoucherResource($data));
    }

    /**
     * Display the specified resource.
     */
    public function show(DirectVoucher $directVoucher)
    {
        return $this->ok(new DirectVoucherResource($directVoucher));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DirectVoucherRequest $request, DirectVoucher $directVoucher)
    {
        $directVoucher->number = $request->number;
        $directVoucher->date = $request->date;
        $directVoucher->employee_id = $request->employeeRequestId;
        $directVoucher->notes = $request->notes;
        $directVoucher->user_update_id = auth()->user()->id;

        $arrayItems = json_decode($request->items, true);
        $arrayNewItemInsert = [];
        foreach ($arrayItems as $key => $item) {
            if ($item['id'] > 0) {
                // update already item immediately
                $newItem = DirectVoucherItem::find($item['id']);
                $newItem->item = $item['item'];
                $newItem->count = $item['count'];
                $newItem->notes = $item['notes'];
                $newItem->employee_id = $request->employeeRequestId;
                $newItem->price = $item['price'] * 100;
                $newItem->value = $newItem->count * $newItem->price ;
                $newItem->save();
            } else {
                // for collect new items
                $newItem = new DirectVoucherItem();
                $newItem->item = $item['item'];
                $newItem->count = $item['count'];
                $newItem->notes = $item['notes'];
                $newItem->employee_id = $request->employeeRequestId;
                $newItem->price = $item['price'] * 100;
                $newItem->value = $newItem->count * $newItem->price ;
                array_push($arrayNewItemInsert, $newItem);
            }
        }

        // for save new items at once
        if (count($arrayNewItemInsert) > 0) {
            $directVoucher->Items()->saveMany($arrayNewItemInsert);
        }

        $directVoucher->save();

        return $this->ok(new DirectVoucherResource($directVoucher));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $data = DirectVoucher::find($id);
        $data->delete();

        return $this->ok(null);
    }
}
