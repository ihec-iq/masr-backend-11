<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Inventory\InventoryResource;
use App\Http\Resources\PaginatedResourceCollection;
use App\Http\Resources\Store\InventroyHistoryResource;
use App\Http\Resources\Store\StoreItemHistoryResource;
use App\Http\Resources\Store\StoreItemHistoryResourceCollection;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * GET /api/inventory/balances?stock_id=1&item_id=5
     * - إذا ما ترسل stock_id => يرجع الرصيد لكل مخزن ولكل مادة
     * - إذا ترسل stock_id => يرجع لكل مادة داخل هذا المخزن
     * - إذا ترسل item_id => يفلتر على مادة محددة
     */
    public function balances(Request $request)
    {
        $request->validate([
            'stockId' => ['nullable', 'integer', 'exists:stocks,id'],
            'itemId'  => ['nullable', 'integer', 'exists:items,id'],
            'itemName'  => ['nullable', 'string'],
        ]);
        $request->filled('limit') ? $limit = $request->limit : $limit = 10;
        $query = DB::table('inventory_movements')
            ->join('items', 'items.id', '=', 'inventory_movements.item_id')
            ->join('stocks', 'stocks.id', '=', 'inventory_movements.stock_id')
            ->whereNull('items.deleted_at')
            ->whereNull('stocks.deleted_at');

        if ($request->filled('stockId')) {
            $query->where('inventory_movements.stock_id', (int) $request->stockId);
        }

        if ($request->filled('itemId')) {
            $query->where('inventory_movements.item_id', (int) $request->itemId);
        }
        if ($request->filled('itemName')) {
            $query->where('items.name', 'like', '%' . $request->itemName . '%');
        }

        // إذا ماكو stock_id نخليها group by على stock + item
        // وإذا موجود stock_id هم نفس الشي، فقط يضيق النتائج
        $data = $query
            ->groupBy(
                'inventory_movements.stock_id',
                'inventory_movements.unit_price',
                'stocks.name',
                'inventory_movements.item_id',
                'items.name'
            )
            ->selectRaw('
                inventory_movements.stock_id,
                stocks.name as stock_name,
                inventory_movements.item_id,
                inventory_movements.unit_price,
                items.name as item_name,
                SUM(inventory_movements.quantity) as balance
            ')
            ->orderBy('stocks.name')
            ->orderBy('items.name')
            ->paginate($limit);
        if (empty($data) || $data == null) {
            return $this->error(__('general.loadFailed'));
        } else {
            //return $this->ok($data);
            return $this->ok(new PaginatedResourceCollection($data, InventoryResource::class));
        }
    }

    /**
     * GET /api/inventory/balance?stock_id=1&item_id=5
     * - يرجع رقم واحد (SUM) حسب الفلاتر
     * - إذا ما ترسل شي => يرجع إجمالي رصيد كل شيء (كل المواد بكل المخازن)
     * - إذا ترسل item_id فقط => إجمالي رصيد المادة بكل المخازن
     * - إذا ترسل stock_id فقط => إجمالي رصيد المخزن لكل المواد
     * - إذا ترسل الاثنين => رصيد مادة داخل مخزن
     */
    public function balance(Request $request)
    {
        $request->validate([
            'stock_id'   => ['nullable', 'integer', 'exists:stocks,id'],
            'item_id'    => ['nullable', 'integer', 'exists:items,id'],
            'item_name'  => ['nullable', 'string'],
        ]);

        $query = InventoryMovement::query();

        if ($request->filled('stock_id')) {
            $query->where('inventory_movements.stock_id', (int) $request->stock_id);
        }

        if ($request->filled('item_id')) {
            $query->where('inventory_movements.item_id', (int) $request->item_id);
        }

        if ($request->filled('item_name')) {
            $query->join('items', 'items.id', '=', 'inventory_movements.item_id')
                ->whereNull('items.deleted_at')
                ->where('items.name', 'like', '%' . $request->item_name . '%');
        }

        $balance = (int) $query->sum('inventory_movements.quantity');

        return response()->json([
            'stock_id'   => $request->filled('stock_id') ? (int) $request->stock_id : null,
            'item_id'    => $request->filled('item_id') ? (int) $request->item_id : null,
            'item_name'  => $request->filled('item_name') ? $request->item_name : null,
            'balance'    => $balance,
        ]);
    }


    /**
     * GET /api/inventory/movements?stock_id=1&item_id=5&from=2026-01-01&to=2026-01-31&movement_type=OUTPUT
     * - كل الشروط اختيارية
     */

    public function movements_total(Request $request)
    {
        $request->validate([
            'stock_id'      => ['nullable', 'integer', 'exists:stocks,id'],
            'item_id'       => ['nullable', 'integer', 'exists:items,id'],
            'from'          => ['nullable', 'date'],
            'to'            => ['nullable', 'date'],
            'movement_type' => ['nullable', 'string', 'max:50'],
            'per_page'      => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $query = InventoryMovement::query()
            ->with(['item:id,name', 'stock:id,name'])
            ->orderByDesc('movement_date')
            ->orderByDesc('id');

        if ($request->filled('stock_id')) {
            $query->where('stock_id', (int) $request->stock_id);
        }

        if ($request->filled('item_id')) {
            $query->where('item_id', (int) $request->item_id);
        }

        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }

        if ($request->filled('from')) {
            $query->whereDate('movement_date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('movement_date', '<=', $request->to);
        }

        $perPage = (int) ($request->per_page ?? 50);

        return $query->paginate($perPage);
    }

    public function movements(Request $request)
    {
        $request->validate([
            'stockId'      => ['nullable', 'integer', 'exists:stocks,id'],
            'itemId'       => ['nullable', 'integer', 'exists:items,id'],
            'employeeId'   => ['nullable', 'integer', 'exists:employees,id'],
            'from'         => ['nullable', 'date'],
            'to'           => ['nullable', 'date'],
            'movementType' => ['nullable', 'string', 'max:50'],
            // alias support
            'limit'        => ['nullable', 'integer', 'min:1', 'max:200'],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $inputVoucherItemType     = \App\Models\InputVoucherItem::class;
        $outputVoucherItemType    = \App\Models\OutputVoucherItem::class;
        $retrievalVoucherItemType = \App\Models\RetrievalVoucherItem::class;

        $query = \App\Models\InventoryMovement::query()
            ->leftJoin('items', 'items.id', '=', 'inventory_movements.item_id')
            ->leftJoin('stocks', 'stocks.id', '=', 'inventory_movements.stock_id')
            ->leftJoin('employees', 'employees.id', '=', 'inventory_movements.employee_id')

            // source line joins (حسب source_line_type)
            ->leftJoin('input_voucher_items', function ($join) use ($inputVoucherItemType) {
                $join->on('input_voucher_items.id', '=', 'inventory_movements.source_line_id')
                    ->where('inventory_movements.source_line_type', '=', $inputVoucherItemType);
            })
            ->leftJoin('output_voucher_items', function ($join) use ($outputVoucherItemType) {
                $join->on('output_voucher_items.id', '=', 'inventory_movements.source_line_id')
                    ->where('inventory_movements.source_line_type', '=', $outputVoucherItemType);
            })
            ->leftJoin('retrieval_voucher_items', function ($join) use ($retrievalVoucherItemType) {
                $join->on('retrieval_voucher_items.id', '=', 'inventory_movements.source_line_id')
                    ->where('inventory_movements.source_line_type', '=', $retrievalVoucherItemType);
            })

            ->whereNull('items.deleted_at')
            ->whereNull('stocks.deleted_at')
            ->orderByDesc('inventory_movements.movement_date')
            ->orderByDesc('inventory_movements.id');

        if ($request->filled('stockId')) {
            $query->where('inventory_movements.stock_id', (int) $request->stockId);
        }

        if ($request->filled('itemId')) {
            $query->where('inventory_movements.item_id', (int) $request->itemId);
        }

        if ($request->filled('employeeId')) {
            $query->where('inventory_movements.employee_id', (int) $request->employeeId);
        }

        if ($request->filled('movementType')) {
            $query->where('inventory_movements.movement_type', $request->movementType);
        }

        if ($request->filled('from')) {
            $query->whereDate('inventory_movements.movement_date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('inventory_movements.movement_date', '<=', $request->to);
        }

        // ✅ أهم إصلاح: إزالة الفاصلة الأخيرة قبل إغلاق selectRaw
        $query->selectRaw('
        inventory_movements.movable_id as voucherId,
        inventory_movements.item_id as itemId,
        items.name as itemName,
        stocks.id as stockId,
        stocks.name as stockName,

        COALESCE(
            input_voucher_items.description,
            input_voucher_items.notes,
            output_voucher_items.notes,
            retrieval_voucher_items.notes,
            inventory_movements.notes,
            ""
        ) as description,

        inventory_movements.unit_price as price,

        CASE
            WHEN inventory_movements.quantity >= 0 THEN "input"
            ELSE "output"
        END as billType,

        COALESCE(
            input_voucher_items.count,
            output_voucher_items.count * -1,
            retrieval_voucher_items.count,
            inventory_movements.quantity
        ) as count,

        employees.id as employeeId,
        employees.name as employeeName
    ');

        // Pagination (آمن)
        $perPage = (int) ($request->limit ?? $request->per_page ?? 50);
        $perPage = max(1, min($perPage, 200));

        $data = $query->paginate($perPage);

        // تحويل النتيجة للشكل النهائي المطلوب
        // $data->getCollection()->transform(function ($row) {
        //     return [
        //         'voucherId'   => $row->voucherId !== null ? (int) $row->voucherId : null,
        //         'itemId'      => (int) $row->itemId,
        //         'itemName'    => (string) $row->itemName,
        //         'stockId'     => (int) $row->stockId,
        //         'stockName'   => (string) $row->stockName,
        //         'description' => (string) ($row->description ?? ''),
        //         'Employee'    => [
        //             'id'   => $row->employeeId !== null ? (int) $row->employeeId : null,
        //             'name' => $row->employeeName !== null ? (string) $row->employeeName : null,
        //         ],
        //         'price'       => $row->price !== null ? (int) $row->price : null,
        //         'billType'    => (string) $row->billType,
        //         'count'       => (int) $row->count,
        //     ];
        // });

        // return $data;
        if (empty($data) || $data == null) {
            return $this->error(__('general.loadFailed'));
        } else {
            //return $this->ok($data);
            return $this->ok(new PaginatedResourceCollection($data, InventroyHistoryResource::class));
        }
    }
}
