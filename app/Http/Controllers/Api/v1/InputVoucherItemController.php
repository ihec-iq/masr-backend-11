<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Voucher\InputVoucherItemHistoryResource;
use App\Http\Resources\Voucher\InputVoucherItemResource;
use App\Http\Resources\Voucher\InputVoucherItemResourceCollection;
use App\Http\Resources\Voucher\InputVoucherItemVSelectResource;
use App\Models\InputVoucherItem;
use App\Models\ItemStoreView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InputVoucherItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        return $this->ok(InputVoucherItemResource::collection(InputVoucherItem::get()));
    }

    public function getAvailableItemsVSelect2(Request $request)
    {
        $results = ItemStoreView::whereRaw('countIn-countOut + countReIn-countReOut >0')
            ->select(
                'id',
                'itemId',
                'itemName',
                'code',
                'ItemDescription',
                'description',
                'notes',
                'price',
                'itemCategoryId',
                'itemCategoryName',
                'stockId',
                'stockName',
                'countIn',
                'countOut',
                'countReIn',
                'countReOut'
            );


        if (isset($request->employeeId) && $request->employeeId != "0")
            $results = $results->whereRaw('employeeId=' . $request->employeeId);
        if (isset($request->itemName) && $request->itemName != "")
            $results = $results->whereRaw('itemName', 'like', $request->itemName);
        $results = $results->get(); //->take(1);
        return $this->ok(InputVoucherItemVSelectResource::collection($results));
    }

    /**
     * Display a Items For VSelect that show only available items.
     */

    public function getAvailableItemsVSelectNew(Request $request)
    {
        // $results = ItemStoreView::whereRaw('countIn-countOut + countReIn-countReOut >0')
        //     ->select(
        //         'id',
        //         'itemId',
        //         'itemName',
        //         'code',
        //         'ItemDescription',
        //         'description',
        //         'notes',
        //         'price',
        //         'itemCategoryId',
        //         'itemCategoryName',
        //         'stockId',
        //         'stockName',
        //         'countIn',
        //         'countOut',
        //         'countReIn',
        //         'countReOut'
        //     );
        $results = DB::table('input_voucher_items as inputVoucherItems')
            ->join('input_vouchers as inputVouchers', 'inputVoucherItems.input_voucher_id', '=', 'inputVouchers.id')
            ->join('stocks as stocks', 'inputVouchers.stock_id', '=', 'stocks.id')
            ->join('items as items', 'inputVoucherItems.item_id', '=', 'items.id')
            ->join('item_categories as itemCategories', 'items.item_category_id', '=', 'itemCategories.id')
            ->leftJoinSub(
                DB::table('output_voucher_items')
                    ->select('input_voucher_item_id', DB::raw('SUM(count) as total_count_out'))
                    ->groupBy('input_voucher_item_id'),
                'outputTotals',
                'outputTotals.input_voucher_item_id',
                '=',
                'inputVoucherItems.id'
            )
            ->leftJoinSub(
                DB::table('retrieval_voucher_items')
                    ->select('input_voucher_item_id', DB::raw('SUM(count) as total_count_rein'))
                    ->whereIn('retrieval_voucher_item_type_id', [1])
                    ->groupBy('input_voucher_item_id'),
                'retrievalInTotals',
                'retrievalInTotals.input_voucher_item_id',
                '=',
                'inputVoucherItems.id'
            )
            ->leftJoinSub(
                DB::table('retrieval_voucher_items')
                    ->select('input_voucher_item_id', DB::raw('SUM(count) as total_count_reout'))
                    ->whereNotIn('retrieval_voucher_item_type_id', [1])
                    ->groupBy('input_voucher_item_id'),
                'retrievalOutTotals',
                'retrievalOutTotals.input_voucher_item_id',
                '=',
                'inputVoucherItems.id'
            )
            ->select([
                'inputVoucherItems.id as id',
                'items.id as   itemId',
                'items.name as itemName',
                'items.code as code',
                'items.description as ItemDescription',
                'inputVoucherItems.description as description',
                'inputVoucherItems.notes as notes',
                'inputVoucherItems.price as price',
                'itemCategories.id as itemCategoryId',
                'itemCategories.name as itemCategoryName',
                'stocks.id as stockId',
                'stocks.name as stockName',
                DB::raw('COALESCE(inputVoucherItems.count,0) as countIn'),
                DB::raw('COALESCE(outputTotals.total_count_out,0) as countOut'),
                DB::raw('COALESCE(retrievalInTotals.total_count_rein,0) as countReIn'),
                DB::raw('COALESCE(retrievalOutTotals.total_count_reout,0) as countReOut'),
            ]);

        if ($q = request('itemName')) {
            // استعمال FULLTEXT أسرع من LIKE
            //$results->orWhereRaw("MATCH(items.name) AGAINST (? IN BOOLEAN MODE)", ["+$q"]);
        }

        // $list = $base->orderBy('inputVoucherItems.id')->paginate(12);

        if (isset($request->employeeId) && $request->employeeId != "0") {
            $results = $results->whereRaw('employeeId=' . $request->employeeId);
        }

        if (!empty($request->itemName)) {
            $results = $results->where('items.name', 'like', '%' . trim($request->itemName) . '%');
        }
        //Log::info($results->toSql());
        $results = $results->get(); //->take(1);
        //Log::alert($results);
        return $this->ok(InputVoucherItemVSelectResource::collection($results));
    }
    public function getAvailableItemsVSelect(Request $request)
    {
        $results = ItemStoreView::whereRaw('countIn-countOut + countReIn-countReOut >0')
            ->select(
                'id',
                'itemId',
                'itemName',
                'code',
                'ItemDescription',
                'description',
                'notes',
                'price',
                'itemCategoryId',
                'itemCategoryName',
                'stockId',
                'stockName',
                'countIn',
                'countOut',
                'countReIn',
                'countReOut'
            );

        if (isset($request->employeeId) && $request->employeeId != "0") {
            $results = $results->whereRaw('employeeId=' . $request->employeeId);
        }

        if (!empty($request->itemName)) {
            $results = $results->where('itemName', 'like', '%' . trim($request->itemName) . '%');
        }
         //Log::info($results->toSql());
        $results = $results->get(); //->take(1);
        return $this->ok(InputVoucherItemVSelectResource::collection($results));
    }
    public function getAvailableItemsVSelectByEmployeeId($employeeId)
    {
        $results = ItemStoreView::whereRaw('countIn-`countOut` + countReIn-countReOut>0')
            ->select(
                'id',
                'itemId',
                'itemName',
                'code',
                'ItemDescription',
                'description',
                'notes',
                'price',
                'itemCategoryId',
                'itemCategoryName',
                'stockId',
                'stockName',
                'countIn',
                'countOut',
                'countReIn',
                'countReOut'
            );
        if (isset($employeeId) && $employeeId != "0")
            $results = $results->whereRaw('employeeId=' . $employeeId);
        $results = $results->get();
        return $this->ok(InputVoucherItemVSelectResource::collection($results));
    }
    public function getAllItemsVSelect($storeId = "0")
    {
        Log::info($storeId);
        $results = ItemStoreView::whereRaw('countIn-countOut>0 || (countOut>0 && countIn)')
            ->select(
                'id',
                'itemId',
                'itemName',
                'code',
                'ItemDescription',
                'description',
                'notes',
                'price',
                'itemCategoryId',
                'itemCategoryName',
                'stockId',
                'stockName',
                'countIn',
                'countOut'
            );
        if ($storeId <> "0")
            $results = $results->whereRaw('stockId=' . $storeId);
        $results = $results->get();
        return $this->ok(InputVoucherItemVSelectResource::collection($results));
    }

    public function filter(Request $request)
    {
        $filter_bill = [];
        $request->filled('limit') ? $limit = $request->limit : $limit = 10;

        if (!$request->isNotFilled('stockId') && $request->stockId != -1) {
            $filter_bill[] = ['stock_id', $request->stockId];
        }
        if (!$request->isNotFilled('itemId') && $request->itemId != -1) {
            $filter_bill[] = ['item_id', $request->itemId];
        }
        if (!$request->isNotFilled('description') && $request->description != '') {
            $filter_bill[] = ['description', 'like', '%' . $request->description . '%'];
        }
        if (!$request->isNotFilled('notes') && $request->notes != '') {
            $filter_bill[] = ['notes', 'like', '%' . $request->notes . '%'];
        }

        $data = InputVoucherItem::orderBy('id', 'desc')->where($filter_bill)->paginate($limit);
        if (empty($data) || $data == null) {
            return $this->error(__('general.loadFailed'));
        } else {
            //return $this->ok($data);
            return $this->ok(new InputVoucherItemResourceCollection($data));
        }
    }

    /**
     * Store a newly created resource in storage.
     * InputVoucherItemRequest
     */
    public function store(Request $request)
    {
        $data = InputVoucherItem::create([
            'input_voucher_id' => $request->inputVoucherId,
            'item_id' => $request->itemId,
            'description' => $request->description,
            'count' => $request->count,
            'price' => $request->price * 100,
            'value' => $request->price * $request->count * 100,
            'notes' => $request->notes,
        ]);

        return $this->ok(new InputVoucherItemResource($data));
    }

    /**
     * Display the specified resource.
     */
    public function show(InputVoucherItem $inputVoucherItem)
    {
        return $this->ok(new InputVoucherItemHistoryResource($inputVoucherItem));
    }

    /**
     * Update the specified resource in storage.
     * InputVoucherItemRequest
     */
    public function update(Request $request, InputVoucherItem $inputVoucherItem)
    {

        $inputVoucherItem->input_voucher_id = $request->inputVoucherId;
        $inputVoucherItem->item_id = $request->itemId;
        $inputVoucherItem->description = $request->description;
        $inputVoucherItem->count = $request->count;
        $inputVoucherItem->price = $request->price * 100;
        $inputVoucherItem->value = $request->price * $request->count * 100;
        $inputVoucherItem->notes = $request->notes;
        $inputVoucherItem->save();

        return $this->ok(new InputVoucherItemResource($inputVoucherItem));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $data = InputVoucherItem::find($id);
        if ($data->OutputVoucherItems()->exists()) {
            return $this->error('This Item Have Output Vouchers !!!', $data, $status = 403);
        }
        $data->delete();
        return $this->ok(null);
    }
}
