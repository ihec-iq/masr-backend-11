<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Voucher\RetrievalVoucherRequest;
use App\Http\Resources\Voucher\RetrievalVoucherResource;
use App\Http\Resources\Voucher\RetrievalVoucherResourceCollection;
use App\Models\RetrievalVoucher;
use App\Models\RetrievalVoucherItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RetrievalVoucherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->ok(RetrievalVoucherResource::collection(RetrievalVoucher::get()));
    }

    public function filter(Request $request)
    {

        $request->filled('limit') ? $limit = $request->limit : $limit = 10;

        $data = RetrievalVoucher::orderBy('id', 'desc');

        if (!$request->isNotFilled('name') && $request->name != '') {
            $data = $data->orWhere('number', 'like', '%' . $request->name . '%');
        }
        if (!$request->isNotFilled('name') && $request->name != '') {
            $data = $data->orWhere('notes', 'like', '%' . $request->name . '%');

        }
        if (!$request->isNotFilled('issueDateFrom') && $request->issueDateFrom != '') {
            $data = $data->where('date', '>=', $request->issueDateFrom, 'and', 'date', '<=', $request->issueDateTo);
        }
        $data = $data->paginate($limit);

        if (empty ($data) || $data == null) {
            return $this->error(__('general.loadFailed'));
        } else {
            return $this->ok(new RetrievalVoucherResourceCollection($data));
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RetrievalVoucherRequest $request)
    {
        $employee = json_decode($request->Employee, true);
        $data = RetrievalVoucher::create([
            'number' => $request->number,
            'date' => $request->date,
            'employee_id' => $employee['id'],
            'notes' => $request->notes,
            'retrieval_voucher_item_type_id' => $request->TypeId,
            'user_create_id' => auth()->user()->id,
            'user_update_id' => auth()->user()->id,
        ]);

        //$arrayItems = json_decode($request->items, true);
        $arrayItems = json_decode($request->Items, true);
        $arrayItemInsert = [];
        foreach ($arrayItems as $key => $item) {
            $newItem = new RetrievalVoucherItem();
            $newItem->item_id = $item['InputVoucherItem']['Item']['id'];
            $newItem->input_voucher_item_id = $item['inputVoucherItemId'];
            $newItem->employee_id = $employee['id'];
            $newItem->count = $item['count'];
            $newItem->notes = $item['notes'];
            $newItem->price = $item['price'] * 100;
            $newItem->value = $newItem->count * $newItem->price ;
            $newItem->retrieval_voucher_item_type_id = $request->TypeId;
            array_push($arrayItemInsert, $newItem);
        }
        $data->Items()->saveMany($arrayItemInsert);

        return $this->ok(new RetrievalVoucherResource($data));
    }

    /**
     * Display the specified resource.
     */
    public function show(RetrievalVoucher $retrievalVoucher)
    {
        return $this->ok(new RetrievalVoucherResource($retrievalVoucher));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RetrievalVoucherRequest $request, RetrievalVoucher $retrievalVoucher)
    {
        $employee = json_decode($request->Employee, true);

        $retrievalVoucher->number = $request->number;
        $retrievalVoucher->date = $request->date;
        $retrievalVoucher->employee_id = $employee['id'];
        $retrievalVoucher->retrieval_voucher_item_type_id = $request->TypeId;
        $retrievalVoucher->notes = $request->notes;
        $retrievalVoucher->user_update_id = auth()->user()->id;

        $arrayItems = json_decode($request->Items, true);
        $arrayNewItemInsert = [];
        foreach ($arrayItems as $key => $item) {
            if ($item['id'] > 0) {
                // update already item immediately
                $newItem = RetrievalVoucherItem::find($item['id']);
                $newItem->item_id = $item['InputVoucherItem']['Item']['id'];
                $newItem->input_voucher_item_id = $item['inputVoucherItemId'];
                $newItem->employee_id = $employee['id'];
                $newItem->count = $item['count'];
                $newItem->notes = $item['notes'];
                $newItem->price = $item['price'] * 100;
                $newItem->value = $newItem->count * $newItem->price ;
                $newItem->retrieval_voucher_item_type_id = $request->TypeId;
                $newItem->save();
            } else {
                // for collect new items
                $newItem = new RetrievalVoucherItem();
                $newItem->item_id = $item['InputVoucherItem']['Item']['id'];
                $newItem->input_voucher_item_id = $item['inputVoucherItemId'];
                $newItem->employee_id = $employee['id'];
                $newItem->count = $item['count'];
                $newItem->notes = $item['notes'];
                $newItem->price = $item['price'] * 100;
                $newItem->value = $newItem->count * $newItem->price;
                $newItem->retrieval_voucher_item_type_id = $request->TypeId;
                array_push($arrayItemInsert, $newItem);
            }
        }

        // for save new items at once
        if (count($arrayNewItemInsert) > 0) {
            $retrievalVoucher->Items()->saveMany($arrayNewItemInsert);
        }

        $retrievalVoucher->save();

        return $this->ok(new RetrievalVoucherResource($retrievalVoucher));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $data = RetrievalVoucher::find($id);
        $data->delete();

        return $this->ok(null);
    }
}
