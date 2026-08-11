<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Bonus\BonusStoreRequest;
use App\Http\Resources\Bonus\BonusResource;
use App\Http\Resources\GeneralIdNameResource;
use App\Http\Resources\Bonus\BonusDegreeStageResource;
use App\Http\Resources\PaginatedResourceCollection;
use App\Models\Bonus;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BonusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->ok(
            BonusResource::collection(Cache::rememberForever('bonuses', function () {
                return Bonus::get();
            }))
        );
    }

    public function Study()
    {
        return $this->ok(
            GeneralIdNameResource::collection(Cache::rememberForever('studies', function () {
                return \App\Models\Study::get();
            }))
        );
    }

    public function Certificate()
    {
        return $this->ok(
            GeneralIdNameResource::collection(Cache::rememberForever('certificates', function () {
                return \App\Models\Certificate::get();
            }))
        );
    }
    public function Bonus_degree_stage()
    {
        return $this->ok(
            BonusDegreeStageResource::collection(Cache::rememberForever('bonus_degree_stages', function () {
                return \App\Models\BonusDegreeStage::get();
            }))
        );
    }

    public function filter(Request $request)
    {
        $request->filled('limit') ? $limit = $request->limit : $limit = 10;
        $data = Bonus::orderBy('id', 'desc');
        $data = $data->whereRelation('Employee', 'is_person', '=', true);

        if (!$request->isNotFilled('employeeName') && $request->employeeName != '') {
            $data = $data->whereRelation('Employee', 'name', 'like', '%' . $request->employeeName . '%');
        }
        if (!$request->isNotFilled('employeeId') && $request->employeeId != '') {
            $data = $data->whereRelation('Employee', 'id', '=',  $request->employeeId);
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
            return $this->ok(new PaginatedResourceCollection($data, BonusResource::class));
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //return $request->get();
        try {
            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'degree_stage_id' => 'required|exists:bonus_degree_stages,id',
                'number' => 'nullable|string',
                'issue_date' => 'required|date',
                'notes' => 'nullable|string',
            ]);
            $bonus = Bonus::create($validated());
            // must to check employee have level up degree_stage_id
            $employee = $bonus->Employee;
            if ($employee->degree_stage_id < $validated['degree_stage_id']) {
                $employee->update([
                    'date_last_bonus' => $validated['issue_date'],
                    'date_next_bonus' => Carbon::parse($validated['issue_date'])->addYears(1),
                    'degree_stage_id' => $validated['degree_stage_id'],
                    'number_last_bonus' => $validated['number'],
                ]);
            }
            return $this->ok(new BonusResource($bonus));
        } catch (\Exception $e) {
            return $this->error(__('general.saveFailed'), $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $bonus = Bonus::findOrFail($id);
        return $this->ok(new BonusResource($bonus));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $bonus = Bonus::findOrFail($id);
            $validated = $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'degree_stage_id' => 'required|exists:bonus_degree_stages,id',
                'number' => 'nullable|string',
                'issue_date' => 'required|date',
                'notes' => 'nullable|string',
            ]);
            $bonus->update($validated);
            // must to check employee have level up degree_stage_id to update it
            $employee = $bonus->Employee;
            if ($employee->degree_stage_id < $validated['degree_stage_id']) {
                $employee->update([
                    'date_last_bonus' => $validated['issue_date'],
                    'date_next_bonus' => Carbon::parse($validated['issue_date'])->addYears(1),
                    'degree_stage_id' => $validated['degree_stage_id'],
                    'number_last_bonus' => $validated['number'],
                ]);
            }
            // re check employee date bonus
            $hrDocument = new HrDocumentController();
            $hrDocument->update_employee_date_bonus($employee->id);
            return $this->ok(new BonusResource($bonus));
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
            $data = Bonus::findOrFail($id);
            $employee = $data->Employee;
            $countBonuses = Bonus::where('employee_id', $employee->id)->count();
            if ($countBonuses == 1) {
                return $this->error('لا يمكن حذف العلاوة الاولى');
            }

            $data->delete();
            // get last bonus
            $lastData = Bonus::where('employee_id', $employee->id)->orderBy('issue_date', 'desc')->first();
            if ($lastData) {
                $employee->update([
                    'date_last_bonus' => $lastData->issue_date,
                    'date_next_bonus' => Carbon::parse($lastData->issue_date)->addYears(1),
                    'degree_stage_id' => $lastData->degree_stage_id,
                    'number_last_bonus' => $lastData->number,
                ]);
            }
            $hrDocument = new HrDocumentController();
            $hrDocument->update_employee_date_bonus($employee->id);
            return $this->ok(['message' => 'Bonus deleted successfully']);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
