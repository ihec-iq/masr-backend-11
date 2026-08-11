<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Item\ItemGetFilterRequest;
use App\Http\Requests\Item\ItemStoreRequest;
use App\Http\Resources\Item\ItemResource;
use App\Http\Resources\Item\ItemResourceCollection;
use App\Models\InputVoucherItem;
use App\Models\Item;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = ItemResource::collection(Item::get());

        return $this->ok($data);
    }

    public function filter(Request $request)
    {

        $request->validate([
            'limit' => ['required', 'integer', 'min:1'],
            'name' => ['sometimes', 'string', 'max:255', 'nullable'],
            'description' => ['sometimes', 'string', 'max:1000', 'nullable'],
            'code' => ['sometimes', 'string', 'max:50', 'nullable'],
            'itemCategoryId' => ['sometimes', 'integer', 'exists:item_categories,id', 'nullable'],
            'measuringUnit' => ['sometimes', 'string', 'max:50', 'nullable'],
        ]);

        $filter_bill = [];
        $request->filled('limit') ? $limit = $request->limit : $limit = 10;
        // if (Auth::user()->hasAnyPermission(['Administrator', 'Super-Admin'])) {
        // } else {
        //     $filter_bill[] = ['section_id',  Auth::user()->sections()->pluck('id')];
        // }

        if (! $request->isNotFilled('name') && $request->name != '') {
            $filter_bill[] = ['name', 'like', '%' . $request->name . '%'];
        }
        if (! $request->isNotFilled('description') && $request->description != '') {
            $filter_bill[] = ['description', 'like', '%' . $request->description . '%'];
        }
        if (! $request->isNotFilled('code') && $request->code != '') {
            $filter_bill[] = ['code', 'like', '%' . $request->code . '%'];
        }
        if (! $request->isNotFilled('isIn') && $request->is_in != -1) {
            $filter_bill[] = ['is_in', $request->is_in];
        }
        if (! $request->isNotFilled('measuringUnit') && $request->measuringUnit != -1) {
            $filter_bill[] = ['measuring_unit', $request->measuringUnit];
        }

        //$filter_bill[] = ['issue_date', '>=', $request->issueDateFrom, 'and', 'issue_date', '<=', $request->issueDateTo];

        $data = Item::orderBy('id', 'desc')->where($filter_bill)->paginate($limit);
        if (empty($data) || $data == null) {
            return $this->error(__('general.loadFailed'));
        } else {
            //return $this->ok($data);
            return $this->ok(new ItemResourceCollection($data));
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'string|max:50|nullable',
            'description' => 'string|max:1000|nullable',
            'category_id' => 'integer|exists:item_categories,id',
            'measuringUnit' => 'string|max:50|nullable',
        ]);
        $data = Item::create([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'item_category_id' => $request->category_id,
            'measuring_unit' => $request->measuringUnit,
            'user_create_id' => Auth::user()->id,
            'user_update_id' =>  Auth::user()->id,
        ]);

        return $this->ok(new ItemResource($data));
    }

    public function showHistory(string $id)
    {
        $data = Item::find($id);

        return $this->ok(new ItemResource($data));
    }

    public function show(string $id)
    {
        $data = Item::find($id);

        return $this->ok(new ItemResource($data));
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|unique:items,name,' . $id,
            'code' => 'string|unique:items,code,' . $id . '|nullable',
            'description' => 'string|nullable',
            'category_id' => 'integer|exists:item_categories,id',
            'measuring_unit' => 'string|nullable',
        ]);
        $data = Item::find($id);
        $data->name = $request->name;
        $data->code = $request->code;
        $data->description = $request->description;
        $data->item_category_id = $request->category_id;
        $data->measuring_unit = $request->measuring_unit;
        $data->user_update_id = Auth::user()->id;

        $data->save();

        return $this->ok(new ItemResource($data));
    }

    public function destroy(Item $item)
    {
        if ($item->InputVoucherItems()->exists())
            return $this->error('This Item Have InputVoucher!!!');
        $item->delete();
        return $this->ok(null);
    }
}
