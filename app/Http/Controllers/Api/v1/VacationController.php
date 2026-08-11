<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vacation\VacationStoreRequest;
use App\Http\Resources\Vacation\VacationResource;
use App\Http\Resources\Vacation\VacationResourceCollection;
use App\Models\Employee;
use App\Models\Vacation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Log;

class VacationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $data = Vacation::get();

        return VacationResource::collection($data);

        //return $this->ok(new VacationResourceCollection($data));
    }

    public function filter(Request $request)
    {
        $request->filled('limit') ? $limit = $request->limit : $limit = 10;

        $data = Vacation::withSum('VacationDaily as sumDaily', 'record')
            ->withSum('VacationTime as sumTime', 'record')
            ->withSum('VacationSick as sumSick', 'record'); //->orderBy('id', 'desc');
        $data = $data->whereRelation('Employee', 'is_person', '=', true);

        if (!$request->isNotFilled('employeeName') && $request->employeeName != '') {
            $data = $data->whereRelation('Employee', 'name', 'like', '%' . $request->employeeName . '%');
        }
        if (!$request->isNotFilled('record') && $request->record != '') {
            $data = $data->orWhere('record', 'like', '%' . $request->record . '%');
        }

        #region "Check Premission [vacation office ,vacation center ]"
        //Log::alert(Auth::user()->getAllPermissions()->pluck('name'));
        $data = $data->whereHas('Employee.EmployeeType', function ($query) {
            $employeeType = ['1'];
            if (Auth::user()->hasAnyPermission(['vacation office'])) {
                array_push($employeeType, '2');
            }
            if (Auth::user()->hasAnyPermission(['vacation center'])) {
                array_push($employeeType, '3');
            }
            array_push($employeeType, '4');
            $query->whereIn('id', $employeeType);
        });

        #endregion
        // if (! $request->isNotFilled('issueDateFrom') && $request->issueDateFrom != '') {
        //     $data = $data->where('day_from', '>=', $request->issueDateFrom, 'and', 'date', '<=', $request->issueDateTo);
        // }
        $data = $data->paginate($limit);
        if (empty($data) || $data == null) {
            return $this->error(__('general.loadFailed'));
        } else {
            return $this->ok(new VacationResourceCollection($data));
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(VacationStoreRequest $request)
    {
        $this->authorize('create', Vacation::class);
        
        $data = Vacation::create([
            'employee_id' => $request->employee_id,
            'old_record' => $request->old_record,
            'new_record' => $request->new_record,
            'record' => $request->record,
        ]);

        return $this->ok(new VacationResource($data));
    }

    public function makeVacation()
    {

        $employees = Employee::get();

        $data = [];
        foreach ($employees as $employee) {
            $data[] = Vacation::updateOrCreate([
                'employee_id' => $employee->id,
                'old_record' => $employee->init_vacation,
                'new_record' => $employee->take_vacation,
                'record' => $employee->init_vacation - $employee->take_vacation,
                'old_record_sick' => $employee->init_vacation_sick,
                'new_record_sick' => $employee->take_vacation_sick,
                'record_sick' => $employee->init_vacation_sick - $employee->take_vacation_sick,
            ]);
        }

        return $this->ok(VacationResource::collection($data));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $vacation = Vacation::
            withSum('VacationDaily as sumDaily', 'record')
            ->withSum('VacationTime as sumTime', 'record')
            ->withSum('VacationSick as sumSick', 'record')->find($id);
        
        if ($vacation) {
            $this->authorize('view', $vacation);
        }

        return $this->ok(new VacationResource($vacation));
    }

    public function update_vacations(string $id)
    {

        $vacation = Vacation::withSum('VacationDaily as sumDaily', 'record')
            ->withSum('VacationTime as sumTime', 'record')
            ->find($id);

        $vacation->record = $vacation->old_record -
            ($vacation->new_record + ($vacation->sumTime / 7) + $vacation->sumDaily);
        $vacation->save();
        return $vacation;
        //return $this->ok(new VacationResource($vacation));
    }
    public function update_sick_vacations(string $id)
    {

        $vacation = Vacation::withSum('VacationSick as sumSick', 'record')->find($id);

        $vacation->record_sick = $vacation->old_record_sick -
            ($vacation->new_record_sick + $vacation->sumSick);
        $vacation->save();
        return $vacation;
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
