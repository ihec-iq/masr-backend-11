<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Voucher\OutputVoucherRequest;
use App\Http\Resources\Voucher\OutputVoucherResource;
use App\Http\Resources\Voucher\OutputVoucherResourceCollection;
use App\Models\OutputVoucher;
use App\Models\OutputVoucherItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OutputVoucherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->ok(OutputVoucherResource::collection(OutputVoucher::get()));
    }

    public function filter(Request $request)
    {
        $request->filled('limit') ? $limit = $request->limit : $limit = 10;

        $data = OutputVoucher::orderBy('id', 'desc');

        if (!$request->isNotFilled('name') && $request->name != '') {
            $data = $data->orWhere('number', 'like', '%' . $request->name . '%');
        }
        if (!$request->isNotFilled('name') && $request->name != '') {
            $data = $data->orWhereRelation('Employee', 'name', 'like', '%' . $request->name . '%');
        }

        if (!$request->isNotFilled('issueDateFrom') && $request->issueDateFrom != '') {
            $data = $data->where('date', '>=', $request->issueDateFrom, 'and', 'date', '<=', $request->issueDateTo);
        }
        $data = $data->paginate($limit);

        if (empty($data) || $data == null) {
            return $this->error(__('general.loadFailed'));
        } else {
            return $this->ok(new OutputVoucherResourceCollection($data));
        }
    }
    public function checkBillExists(Request $request)
    {
        $data = OutputVoucher::orderBy('id');
        if (!$request->isNotFilled('number') && $request->number != '') {
            $data = $data->Where('number',   $request->number);
        }
        if (!$request->isNotFilled('date') && $request->date != '') {
            $data = $data->where('date', $request->date);
        }
        $data = $data->get();
        if ($data->isEmpty()) {
            return $this->error(__('general.loadFailed'));
        } else {
            return $this->ok(data: OutputVoucherResource::collection($data));
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'number' => [
                'required',
                'string',
                // function ($attribute, $value, $fail) use ($request) {
                //     $year = date('Y', strtotime($request->date));
                //     $exists = \App\Models\OutputVoucher::where('number', $value)
                //         ->whereYear('date', $year)
                //         ->exists();
                //     if ($exists) {
                //         $fail(__('validation.unique', ['attribute' => $attribute]));
                //     }
                // },
            ],
            'date' => 'required|date',
            'notes' => 'nullable|string',
            'dateBill' => 'nullable|date',
            'numberBill' => 'nullable|string',
        ]);
        $data = OutputVoucher::create([
            'number' => $request->number,
            'date' => $request->date,
            'employee_id' => $request->employeeRequestId,
            'date_bill' => $request->dateBill,
            'number_bill' => $request->numberBill,
            'notes' => $request->notes,
            'user_create_id' => Auth::user()->id,
            'user_update_id' => Auth::user()->id,
        ]);
        if ($request->hasfile('FilesDocument')) {
            $document = new DocumentController();
            $document->store_multi(
                request: $request,
                documentable_id: $data->id,
                documentable_type: OutputVoucher::class,
                pathFolder: "OutputVoucher"
            );
        }
        $arrayItems = json_decode($request->Items, true);
        $arrayItemInsert = [];
        foreach ($arrayItems as $key => $item) {Log::alert($item);
            $newItem = new OutputVoucherItem();
            $newItem->item_id = $item['Item']['id'];
            $newItem->input_voucher_item_id = $item['inputVoucherItemId'];
            $newItem->count = $item['count'];
            $newItem->notes = $item['notes'];
            $newItem->employee_id = $request->employeeRequestId;
            $newItem->price = $item['price'] * 100;
            $newItem->value = $newItem->count * $newItem->price ;
            array_push($arrayItemInsert, $newItem);
        }

        $data->Items()->saveMany($arrayItemInsert);

        return $this->ok(new OutputVoucherResource($data));
    }

    /**
     * Display the specified resource.
     */
    public function show(OutputVoucher $outputVoucher)
    {
        return $this->ok(new OutputVoucherResource($outputVoucher));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OutputVoucher $outputVoucher)
    {
        $request->validate([
            'number' => [
                'required',
                'string',
                // function ($attribute, $value, $fail) use ($request, $outputVoucher) {
                //     $year = date('Y', strtotime($request->date));
                //     $exists = \App\Models\OutputVoucher::where('number', $value)
                //         ->whereYear('date', $year)
                //         ->where('id', '!=', $outputVoucher->id)
                //         ->exists();
                //     if ($exists) {
                //         $fail(__('validation.unique', ['attribute' => $attribute]));
                //     }
                // },
            ],
            'date' => 'required|date',
            'employeeRequestId' => 'required|integer|exists:employees,id',
            'notes' => 'nullable|string',
            'dateBill' => 'nullable|date',
            'numberBill' => 'nullable|string',
        ]);
        $outputVoucher->number = $request->number;
        $outputVoucher->date = $request->date;
        $outputVoucher->employee_id = $request->employeeRequestId;
        $outputVoucher->notes = $request->notes;

        $outputVoucher->date_bill = $request->dateBill;
        $outputVoucher->number_bill = $request->numberBill;

        $outputVoucher->user_update_id = Auth::user()->id;
        if ($request->hasfile('FilesDocument')) {
            $document = new DocumentController();
            $document->store_multi(
                request: $request,
                documentable_id: $outputVoucher->id,
                documentable_type: OutputVoucher::class,
                pathFolder: "OutputVoucher"
            );
        }
        $outputVoucher->save();
        $arrayItems = json_decode(json: $request->Items, associative: true);
        $arrayNewItemInsert = [];
        foreach ($arrayItems as $key => $item) {
            // item schema {"id":0,"input_voucher_id":0,"item":{"name":"","id":0,"code":"","description":"","itemCategory":{"id":0,"name":""},"measuringUnit":""},"description":"66666666","count":0,"price":0,"value":0,"notes":""}
            if ($item['id'] > 0) {
                // update already item immediately
                $newItem = OutputVoucherItem::find($item['id']);
                $newItem->item_id = $item['Item']['id'];
                $newItem->count = $item['count'];
                $newItem->notes = $item['notes'];
                $newItem->employee_id = $request->employeeRequestId;
                $newItem->price = $item['price'] * 100;
                $newItem->value = $newItem->count * $newItem->price;
                $newItem->input_voucher_item_id = $item['inputVoucherItemId'];
                $newItem->save();
            } else {Log::alert( $item);
                // for collect new items
                $newItem = new OutputVoucherItem();
                $newItem->item_id = $item['Item']['id'];
                $newItem->input_voucher_item_id = $item['inputVoucherItemId'];
                $newItem->count = $item['count'];
                $newItem->notes = $item['notes'];
                $newItem->employee_id = $request->employeeRequestId;
                $newItem->price = $item['price'] * 100;
                $newItem->value = $newItem->count * $newItem->price * 100;
                $newItem->output_voucher_id = $outputVoucher->id;

                array_push($arrayNewItemInsert, $newItem);
            }
        }
        // for save new items at once
        if (count($arrayNewItemInsert) > 0) {
            $outputVoucher->Items()->saveMany($arrayNewItemInsert);
        }
        return $this->ok(new OutputVoucherResource($outputVoucher));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $data = OutputVoucher::find($id);
        $data->delete();
        return $this->ok(null);
    }
}
