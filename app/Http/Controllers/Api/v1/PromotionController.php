<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Promotion\PromotionStoreRequest;
use App\Http\Resources\Promotion\PromotionResource;
use App\Http\Resources\Promotion\PromotionResourceCollection;
use App\Http\Resources\GeneralIdNameResource;
use App\Http\Resources\PaginatedResourceCollection;
use App\Http\Resources\Promotion\PromotionDegreeStageResource;
use App\Models\Promotion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PromotionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Promotion::get();
        return $this->ok(PromotionResource::collection($data));
    }

    public function filter(Request $request)
    {
        $request->filled('limit') ? $limit = $request->limit : $limit = 10;
        $data = Promotion::orderBy('id', 'desc');
        $data = $data->whereRelation('Employee', 'is_person', '=', true);

        if (!$request->isNotFilled('employeeName') && $request->employeeName != '') {
            $data = $data->whereRelation('Employee', 'name', 'like', '%' . $request->employeeName . '%');
        }
        if (!$request->isNotFilled('record') && $request->record != '') {
            $data = $data->orWhere('record', 'like', '%' . $request->record . '%');
        }


        // if (! $request->isNotFilled('issueDateFrom') && $request->issueDateFrom != '') {
        //     $data = $data->where('day_from', '>=', $request->issueDateFrom, 'and', 'date', '<=', $request->issueDateTo);
        // }
        $data = $data->paginate($limit);
        if (empty($data) || $data == null) {
            return $this->error(__('general.loadFailed'));
        } else {
            return $this->ok(new PaginatedResourceCollection($data, PromotionResource::class));
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //return $request->all();
        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'degree_stage_id' => 'required|exists:bonus_degree_stages,id',
            'number' => 'required|string',
            'issue_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);
        try {
            $data = Promotion::create($validated);
            // must to check employee have level up promotion_degree_stage_id
            if ($data->Employee->degree_stage_id < $validated['degree_stage_id']) {
                $numberOfYear = 4;
                $data->Employee->update([
                    'date_last_promotion' => $validated['issue_date'],
                    'date_next_promotion' => Carbon::parse($validated['issue_date'])->addYears($numberOfYear),
                    'number_last_promotion' => $validated['number'],
                    'degree_stage_id' => $validated['degree_stage_id'],
                ]);
            }
            return $this->ok(new PromotionResource($data));
        } catch (\Exception $e) {
            return $this->error(__('general.saveFailed'), $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Promotion $promotion)
    {
        return $this->ok(new PromotionResource($promotion));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Promotion $promotion)
    {
        try {
            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'degree_stage_id' => 'required|exists:bonus_degree_stages,id',
                'number' => 'required|string',
                'issue_date' => 'required|date',
                'notes' => 'nullable|string',
            ]);
            $promotion->update($validated);
            // must to check employee have level up promotion_degree_stage_id to update it
            $employee = $promotion->Employee;
            if ($employee->degree_stage_id < $validated['degree_stage_id']) {
                $employee->update([
                    'date_last_promotion' => $validated['issue_date'],
                    'date_next_promotion' => Carbon::parse($validated['issue_date'])->addYears(4),
                    'number_last_promotion' => $validated['number'],
                    'degree_stage_id' => $validated['degree_stage_id'],
                ]);
            }
            // re check employee date promotion
            $hrDocument = new HrDocumentController();
            $hrDocument->update_employee_date_promotion($employee->id);
            return $this->ok(new PromotionResource($promotion));
        } catch (\Exception $e) {
            return $this->error(__('general.saveFailed'), $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $data = Promotion::findOrFail($id);
            $employee = $data->Employee;
            $countPromotiones = Promotion::where('employee_id', $employee->id)->count();
            if ($countPromotiones == 1) {
                return $this->error('لا يمكن حذف العلاوة الاولى');
            }

            $data->delete();
            // get last promotion
            $lastData = Promotion::where('employee_id', $employee->id)->orderBy('issue_date', 'desc')->first();

            $employee->update([
                'date_last_promotion' => $lastData->issue_date,
                'date_next_promotion' => Carbon::parse($lastData->issue_date)->addYears(1),
                'promotion_degree_stage_id' => $lastData->promotion_degree_stage_id,
                'number_last_promotion' => $lastData->number,
            ]);
            $hrDocument = new HrDocumentController();
            $hrDocument->update_employee_date_promotion($employee->id);
            return $this->ok(['message' => 'Promotion deleted successfully']);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
