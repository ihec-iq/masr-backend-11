<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaginatedResourceCollection;
use App\Http\Resources\Store\StoreItemHistoryResourceCollection;
use App\Http\Resources\Store\StoreResourceCollection;
use App\Http\Resources\Store\StoreSummationResourceCollection;
use App\Http\Resources\Voucher\BarrenReportResource;
use App\Models\InputVoucherItem;
use App\Models\ItemStoreView;
use App\Models\OutputVoucherItem;
use App\Models\OutputVoucherItemView;
use App\Models\RetrievalVoucherItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StoreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }
    public function filter()
    {
        $results = DB::table('input_voucher_items as input_item')
            ->leftJoin('items as item', 'input_item.item_id', '=', 'item.id')
            ->leftJoin('input_vouchers as input_voucher', 'input_item.input_voucher_id', '=', 'input_voucher.id')
            ->leftJoin('stocks as stock', 'input_voucher.stock_id', '=', 'stock.id')
            ->leftJoin('output_voucher_items as output_item', 'input_item.id', '=', 'output_item.input_voucher_item_id')
            ->leftJoin('retrieval_voucher_items as retrieval_in_item', function ($join) {
                $join->on('input_item.id', '=', 'retrieval_in_item.input_voucher_item_id')
                    ->where('retrieval_in_item.retrieval_voucher_item_type_id', '=', 1);
            })
            ->leftJoin('retrieval_voucher_items as retrieval_out_item', function ($join) {
                $join->on('input_item.id', '=', 'retrieval_out_item.input_voucher_item_id')
                    ->where('retrieval_out_item.retrieval_voucher_item_type_id', '!=', 1);
            })
            ->select([
                'input_item.id as id',
                'item.name as item_name',
                'input_item.description as description',
                'input_item.count as count_in',
                DB::raw('COALESCE(output_item.count, 0) + COALESCE(retrieval_out_item.count, 0) as count_out'),
                'input_item.price as price',
                'stock.name as stock_in_name',
                'input_voucher.date as date'
            ])
            ->orderByDesc('input_voucher.date')
            ->get();

        return response()->json($results);
    }
    public function total(Request $request)
    {
        $filter_bill = [];
        $filter_billOR = [];
        $request->filled('limit') ? $limit = $request->limit : $limit = 10;

        if (!$request->isNotFilled('item') && $request->item != '') {
            $filter_bill[] = ['items.name', 'like', '%' . $request->item . '%'];
        }

        $inventory = DB::table('input_voucher_items')
            ->join('input_vouchers', 'input_vouchers.id', '=', 'input_voucher_items.input_voucher_id')
            ->join('items', 'items.id', '=', 'input_voucher_items.item_id')
            ->leftJoin('output_voucher_items', function ($join) {
                $join->on('output_voucher_items.input_voucher_item_id', '=', 'input_voucher_items.id')
                    ->whereNull('output_voucher_items.deleted_at');
            })
            ->whereNull('input_voucher_items.deleted_at')
            ->whereNull('input_vouchers.deleted_at')
            ->whereNull('items.deleted_at')
            ->where($filter_bill)
            ->groupBy('input_vouchers.stock_id', 'input_voucher_items.item_id', 'items.name')
            ->selectRaw('
                            input_vouchers.stock_id as stock_id,
                            input_voucher_items.item_id as item_id,
                            items.name as item_name,
                            COALESCE(SUM(input_voucher_items.count), 0) as total_input,
                            COALESCE(SUM(output_voucher_items.count), 0) as total_output,
                            COALESCE(SUM(input_voucher_items.count), 0) - COALESCE(SUM(output_voucher_items.count), 0) as current_balance
                        ')
            ->orderBy('items.name')
            ->paginate($limit);

        return response()->json($inventory);
    }

    public function filter1(Request $request)
    {
        $filter_bill = [];
        $filter_billOR = [];
        $request->filled('limit') ? $limit = $request->limit : $limit = 10;

        if (!$request->isNotFilled('item') && $request->item != '') {
            $filter_bill[] = ['items.name', 'like', '%' . $request->item . '%'];
        }
        if (!$request->isNotFilled('description') && $request->description != '') {
            $filter_billOR[] = ['input_voucher_items.description', 'like', '%' . $request->description . '%'];
        }
        // if (! $request->isNotFilled('isIn') && $request->isIn != -1) {
        //     $filter_bill[] = ['is_in', $request->isIn];
        // }
        $data = DB::table('input_voucher_items')
            ->leftJoin('items', 'input_voucher_items.item_id', '=', 'items.id')
            ->leftJoin('input_vouchers', 'input_voucher_items.input_voucher_id', '=', 'input_vouchers.id')
            ->leftJoin('stocks', 'input_vouchers.stock_id', '=', 'stocks.id')
            ->select(
                'items.id as itemId',
                'items.name as itemName',
                'items.description as description',
                'stocks.name as stockName',
                'price',
                DB::raw('sum(count) as count'),
                DB::raw('sum(count) as "countIn"'),
                DB::raw('sum(count) as "countReIn"'),
                DB::raw('sum(count) as "countOut"'),
                DB::raw('sum(count) as "countReOut"'),
                DB::raw('0 as "out"')
            )
            ->groupBy(['items.name', 'items.id', 'stocks.name', 'description', 'price'])
            ->where($filter_bill)
            ->orWhere($filter_billOR)
            ->paginate($limit);
        if (empty($data) || $data == null) {
            return $this->error(__('general.loadFailed'));
        } else {
            //return $this->ok($data);
            return $this->ok(new StoreResourceCollection($data));
        }
    }

    public function summation(Request $request)
    {
        $filter_bill = [];
        $filter_billOR = [];
        $request->filled('limit') ? $limit = $request->limit : $limit = 10;
        $data = ItemStoreView::orderBy('id', 'desc');
        if (!$request->isNotFilled('item') && $request->item != '') {
            $filter_bill[] = ['itemName', 'like', '%' . $request->item . '%'];
        }
        if (!$request->isNotFilled('description') && $request->description != '') {
            $filter_billOR[] = ['description', 'like', '%' . $request->description . '%'];
        }
        $data = $data->where($filter_bill)->paginate($limit);
        // if (! $request->isNotFilled('isIn') && $request->isIn != -1) {
        //     $filter_bill[] = ['is_in', $request->isIn];
        // }
        // if (!$request->isNotFilled('item') && $request->item != '') {
        //     $filter_bill[] = ['items.name', 'like', '%' . $request->item . '%'];
        // }
        // if (!$request->isNotFilled('description') && $request->description != '') {
        //     $filter_billOR[] = ['input_voucher_items.description', 'like', '%' . $request->description . '%'];
        // }
        // $data = DB::table('input_voucher_items')
        //     ->join('items', 'input_voucher_items.item_id', '=', 'items.id')
        //     ->join('stocks', 'input_voucher_items.stock_id', '=', 'stocks.id')
        //     ->leftJoin('output_voucher_items', 'input_voucher_items.id', '=', 'output_voucher_items.input_voucher_item_id')
        //     ->leftJoin('output_voucher_items', 'input_voucher_items.id', '=', 'output_voucher_items.input_voucher_item_id')
        //     ->select(
        //         'items.id as itemId',
        //         'items.name as itemName',
        //         'description as description',
        //         'input_voucher_items.price as price',
        //         'stocks.name as stockName',
        //         DB::raw('IFNULL(SUM(input_voucher_items.count),0) as "in"'),
        //         DB::raw('IFNULL(SUM(output_voucher_items.count),0) as "out"'),
        //         DB::raw('IFNULL(SUM(input_voucher_items.count),0)- IFNULL(SUM(output_voucher_items.count),0) as "count"')
        //     )
        //     ->groupBy(['input_voucher_items.price', 'items.id', 'items.name', 'stocks.name', 'description'])
        //     ->paginate($limit);

        //return $data;
        if (empty($data) || $data == null) {
            return $this->error(__('general.loadFailed'));
        } else {
            //return $this->ok($data);
            return $this->ok(new StoreSummationResourceCollection($data));
        }
    }
    public function barrenSection(Request $request)
    {
        $filter_bill = [];
        $filter_billOR = [];
        $request->filled('limit') ? $limit = $request->limit : $limit = 10;
        $data = OutputVoucherItemView::select('sectionId', 'sectionName', DB::raw('SUM(count) as count'))
            ->groupBy('sectionId', 'sectionName')
            ->orderBy('sectionName');


        if (!$request->isNotFilled('item') && $request->item != '') {
            $filter_bill[] = ['sectionName', 'like', '%' . $request->item . '%'];
        }
        $data = $data->where($filter_bill)->paginate($limit);

        if (empty($data) || $data == null) {
            return $this->error(__('general.loadFailed'));
        } else {
            //return $this->ok($data);
            return $this->ok(($data));
        }
    }
    public function barrenSectionId($id, Request $request)
    {
        $filter_bill = [];
        $filter_billOR = [];
        $request->filled('limit') ? $limit = $request->limit : $limit = 10;
        $data = OutputVoucherItemView::select('OutputId', 'itemId', 'itemName',  'count', 'numberOutput', 'dateOutput', 'price', 'employeeName', 'employeeId')
            ->orderBy('employeeName');
        $data = $data->where('sectionId', $id);


        if (!$request->isNotFilled('item') && $request->item != '') {
            $filter_bill[] = ['itemName', 'like', '%' . $request->item . '%'];
        }
        if (!$request->isNotFilled('item') && $request->item != '') {
            $filter_billOR[] = ['employeeName', 'like', '%' . $request->item . '%'];
        }
        $data = $data->where($filter_bill)->orWhere($filter_billOR)->paginate($limit);

        if (empty($data) || $data == null) {
            return $this->error(__('general.loadFailed'));
        } else {
            //return $this->ok($data);
            return $this->ok(new PaginatedResourceCollection($data, BarrenReportResource::class));
        }
    }

    public function showItemHistory(Request $request, string $id)
    {
        $filter_bill = [];
        $filter_billOR = [];
        $request->filled('limit') ? $limit = $request->limit : $limit = 10;

        if (!$request->isNotFilled('item') && $request->item != '') {
            $filter_bill[] = ['items.name', 'like', '%' . $request->item . '%'];
        }
        if (!$request->isNotFilled('sectionId') && $request->sectionId != '') {
            $filter_bill[] = ['employees.section_id', '=',   $request->sectionId];
        }
        if (!$request->isNotFilled('description') && $request->description != '') {
            $filter_billOR[] = ['input_voucher_items.description', 'like', '%' . $request->description . '%'];
        }
        if (!$request->isNotFilled('employeeName') && $request->employeeName != '') {
            $filter_billOR[] = ['employees.name', 'like', '%' . $request->employeeName . '%'];
        }
        // if (! $request->isNotFilled('isIn') && $request->isIn != -1) {
        //     $filter_bill[] = ['is_in', $request->isIn];
        // }
        $data = DB::table('input_voucher_items')
            ->join('items', 'input_voucher_items.item_id', '=', 'items.id')
            ->join('input_vouchers', 'input_voucher_items.input_voucher_id', '=', 'input_vouchers.id')
            ->join('stocks', 'input_vouchers.stock_id', '=', 'stocks.id')
            ->select(
                'input_voucher_items.input_voucher_id as voucherId',
                'items.id as itemId',
                'items.name as itemName',
                'input_voucher_items.description as description',
                'input_voucher_items.price as price',
                'stocks.name as stockName',
                DB::raw('"0" as employeeId'),
                DB::raw('"" as employeeName'),
                DB::raw('"in" as billType'),
                'input_voucher_items.count as count',
            )
            ->where('items.id', $id)
            ->union(
                DB::table('output_voucher_items')
                    ->join(
                        'input_voucher_items',
                        'input_voucher_items.id',
                        '=',
                        'output_voucher_items.input_voucher_item_id'
                    )
                    ->join('items', 'input_voucher_items.item_id', '=', 'items.id')
                    ->join('input_vouchers', 'input_voucher_items.input_voucher_id', '=', 'input_vouchers.id')
                    ->join('stocks', 'input_vouchers.stock_id', '=', 'stocks.id')
                    ->join('employees', 'employees.id', '=', 'output_voucher_items.employee_id')
                    ->select(
                        'output_voucher_items.output_voucher_id as voucherId',
                        'items.id as itemId',
                        'items.name as itemName',
                        'input_voucher_items.description as description',
                        'input_voucher_items.price as price',
                        'stocks.name as stockName',
                        'employees.id as employeeId',
                        'employees.name as employeeName',
                        DB::raw('"out" as billType'),
                        DB::raw('IFNULL(output_voucher_items.count,0) * -1 as count')
                    )
                    ->where('items.id', $id)
            )
            ->union(
                DB::table('retrieval_voucher_items')
                    ->join(
                        'input_voucher_items',
                        'retrieval_voucher_items.input_voucher_item_id',
                        '=',
                        'input_voucher_items.id'
                    )
                    ->join('items', 'input_voucher_items.item_id', '=', 'items.id')
                    ->join('input_vouchers', 'input_voucher_items.input_voucher_id', '=', 'input_vouchers.id')
                    ->join('stocks', 'input_vouchers.stock_id', '=', 'stocks.id')
                    ->join('employees', 'employees.id', '=', 'retrieval_voucher_items.employee_id')
                    ->select(
                        'retrieval_voucher_items.retrieval_voucher_id as voucherId',
                        'items.id as itemId',
                        'items.name as itemName',
                        'input_voucher_items.description as description',
                        'input_voucher_items.price as price',
                        'stocks.name as stockName',
                        'employees.id as employeeId',
                        'employees.name as employeeName',
                        DB::raw('"reIn" as billType'),
                        DB::raw('IFNULL(retrieval_voucher_items.count,0)   as count')
                    )
                    ->where('items.id', $id)
                    ->whereIn('retrieval_voucher_items.retrieval_voucher_item_type_id', [1, 2])
            )
            //->groupBy(['input_voucher_items.price', 'items.id', 'items.name', 'stocks.name', 'description'])
            ->paginate($limit);
        //->toSql();
        //->get();

        //return $data;
        if (empty($data) || $data == null) {
            return $this->error(__('general.loadFailed'));
        } else {
            //return $this->ok($data);
            return $this->ok(new StoreItemHistoryResourceCollection($data));
        }
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
