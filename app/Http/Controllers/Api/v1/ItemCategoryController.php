<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Item\ItemCategoryStoreRequest;
use App\Http\Resources\Item\ItemCategoryResource;
use App\Http\Resources\Item\ItemCategoryResourceCollection;
use App\Models\ItemCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ItemCategoryController extends Controller
{
    public function index()
    {
        return $this->ok(ItemCategoryResource::collection(Cache::rememberForever('item_categories', function () {
            return ItemCategory::get();
        })));
    }


    public function filter(Request $request)
    {
        $filter_bill = [];
        $request->filled('limit') ? $limit = $request->limit : $limit = 10;
        // if (Auth::user()->hasAnyPermission(['Administrator', 'Super-Admin'])) {
        // } else {
        //     $filter_bill[] = ['section_id',  Auth::user()->sections()->pluck('id')];
        // }

        if (!$request->isNotFilled('name') && $request->name != '') {
            $filter_bill[] = ['name', 'like', '%' . $request->name . '%'];
        }
        $data = ItemCategory::orderBy('id', 'desc')->where($filter_bill)->paginate($limit);
        if (empty($data) || $data == null) {
            return $this->error(__('general.loadFailed'));
        } else {
            return $this->ok(new ItemCategoryResourceCollection($data));
        }
    }

    public function store(ItemCategoryStoreRequest $request)
    {
        $data = ItemCategory::create([
            'name' => $request->name,
            'description' => $request->description,
            'user_create_id' => Auth::user()->id,
            'user_update_id' => Auth::user()->id,
        ]);

        return $this->ok(new ItemCategoryResource($data));
    }

    public function show(string $id)
    {
        $data = ItemCategory::find($id);

        return $this->ok(new ItemCategoryResource($data));
    }

    public function update(ItemCategoryStoreRequest $request, ItemCategory $itemCategory)
    {
        if ($itemCategory->name != $request->name) {
            $itemCategory->name = $request->name;
        }
        $itemCategory->description = $request->description;

        $itemCategory->save();

        return $this->ok(new ItemCategoryResource($itemCategory));
    }

    public function destroy(string $id)
    {
        $data = ItemCategory::find($id);
        if ($data->items()->count() > 0) {
            return $this->error('This Category has Items !!!', $data, $status = 403);
        }
        $data->delete();

        return $this->ok(null);
    }
}
