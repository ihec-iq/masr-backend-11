<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Vacation\VacationDailyResource;
use App\Http\Resources\Vacation\VacationDailyResourceCollection;
use App\Models\VacationDaily;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;

class VacationDailyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

        $vacationDailies = VacationDaily::get();

        //return $this->ok($vacationDailies);

        return $this->ok(VacationDailyResource::collection($vacationDailies));
    }

    public function filter(Request $request)
    {
        $request->filled('limit') ? $limit = $request->limit : $limit = 10;

        $data = VacationDaily::orderBy('day_from', 'desc');

        if (!$request->isNotFilled('record') && $request->record != '') {
            $data = $data->orWhere('record', 'like', '%' . $request->record . '%');
        }
        if (!$request->isNotFilled('employeeId') && $request->employeeId != '0') {
            $data = $data->whereRelation('Vacation', 'employee_id', '=', $request->employeeId);
        }
        if (!$request->isNotFilled('employeeName') && $request->employeeName != '') {

            $data = $data->whereRelation('Vacation.Employee', 'name', 'like', '%' . $request->employeeName . '%');
            $data = $data->orWhere('id', '=', $request->employeeName);

        }
        if (
            !$request->isNotFilled('dayFrom') &&
            $request->dayFrom != '' &&
            !$request->isNotFilled('dayTo') &&
            $request->dayTo != '' &&
            !$request->isNotFilled('hasDate') &&
            $request->hasDate == 'true'
        ) {
            $from = date($request->dayFrom);
            $to = date($request->dayTo);
            $where[] = ['day_from', '>=', $from];
            $where[] = ['day_to', '<=', $to];
            $data = $data->where($where);
        }
        //region "Check Premission [vacation office ,vacation center ]"
        $data = $data->whereHas('Vacation.Employee.EmployeeType', function ($query) {
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
        //endregion

        $data = $data->paginate($limit);
        if (empty($data) || $data == null) {
            return $this->error(__('general.loadFailed'));
        } else {
            return $this->ok(new VacationDailyResourceCollection($data));
        }
    }


    public function dailyReportByEmployee($employeeId)
    {
        $data = VacationDaily::orderBy('day_from', 'desc')
            ->whereRelation('Vacation', 'employee_id', '=', $employeeId)
            ->get();
        $result = '';
        foreach ($data as $key => $value) {
            $result .= PHP_EOL .
                'تسلسل العملية :#  ' . $value->id . PHP_EOL .
                'تم استقطاع اجازة لمدة ' . $value->record . PHP_EOL .
                ' من رصيد الاجازات الخاص بك ' . PHP_EOL .
                ' تبدأ من تاريخ ' . $value->day_from . PHP_EOL .
                ' الى تاريخ' . $value->day_to . PHP_EOL .
                '------------------------------------';
        }

        return $result;
    }
    public function getDailyMyReport()
    {
        $response = [
            'myDailyVacationReport' => $this->dailyReportByEmployee(auth()->user()->Employee->id)
        ];
        return $this->ok($response);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $vacation = json_decode($request->Vacation);
        $reason = json_decode($request->Reason);
        $employeeAlter = json_decode($request->EmployeeAlter);
        $data = [
            'vacation_id' => $vacation->id,
            'day_from' => $request->dayFrom,
        ];
        $vacationDaily = VacationDaily::where($data)->first();
        if ($vacationDaily) {
            return $this->error(
                'this is Found in System',
                new VacationDailyResource($vacationDaily)
            );
        }
        $data = [
            'vacation_id' => $vacation->id,
            'employee_alter_id' => $employeeAlter->id,
            'vacation_reason_id' => $reason->id,
            'record' => $request->record,
            'day_from' => $request->dayFrom,
            'day_to' => $request->dayTo,
            'user_create_id' => Auth::user()->id,
            'user_update_id' => auth()->user()->id,
        ];
        $vacationDaily = VacationDaily::Create($data);

        $vacationResult = $this->update_vacations($vacation->id);
        if ($vacation->Employee->telegramId) {
            if (isset($vacation->Employee->telegramId) && $vacation->Employee->telegramId != '') {
                $botController = new BotController(new Api);
                $message =
                    'نوع العملية : اضافة اجازة اعتيادية' . PHP_EOL .
                    'تسلسل العملية :#  ' . $vacationDaily->id . PHP_EOL .
                    'تم استقطاع اجازة لمدة ' . $request->record . PHP_EOL .
                    ' من رصيد الاجازات الخاص بك تبدأ من تاريخ ' . $request->dayFrom . PHP_EOL .
                    ' الى تاريخ' . $request->dayTo . PHP_EOL .
                    'وان رصيدك المتبقي هو ' . round($vacationResult->record, 2) . ' يوم';
                $botController->sendMessageChatId($vacation->Employee->telegramId, $message);
            }
        }
        $result = new VacationDailyResource($vacationDaily);

        return $this->ok($result);
    }

    public function update_vacations($id)
    {
        $vacationController = new VacationController();

        return $vacationController->update_vacations($id);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $vacationDaily = VacationDaily::find($id);
        return $this->ok(new VacationDailyResource($vacationDaily));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {

        $vacation = json_decode($request->Vacation);
        $reason = json_decode($request->Reason);
        $employeeAlter = json_decode($request->EmployeeAlter);

        $vacationDaily = VacationDaily::find($id);
        $vacation = json_decode($request->Vacation);
        $reason = json_decode($request->Reason);
        $employeeAlter = json_decode($request->EmployeeAlter);

        $vacationDaily->vacation_id = $vacation->id;
        $vacationDaily->employee_alter_id = $employeeAlter->id;
        $vacationDaily->vacation_reason_id = $reason->id;
        $vacationDaily->record = $request->record;
        $vacationDaily->day_from = $request->dayFrom;
        $vacationDaily->day_to = $request->dayTo;
        $vacationDaily->user_update_id = Auth::user()->id;
        $vacationDaily->save();
        $vacationResult = $this->update_vacations($vacation->id);
        if ($vacation->Employee->telegramId) {
            if (isset($vacation->Employee->telegramId) && $vacation->Employee->telegramId != '') {
                $botController = new BotController(new Api);
                $message =
                    'نوع العملية : تعديل اجازة اعتيادية' . PHP_EOL .
                    'تسلسل العملية :#  ' . $vacationDaily->id . PHP_EOL .
                    'تم استقطاع اجازة لمدة ' . $request->record . PHP_EOL .
                    ' من رصيد الاجازات الخاص بك تبدأ من تاريخ ' . $request->dayFrom . PHP_EOL .
                    ' الى تاريخ' . $request->dayTo . PHP_EOL .
                    'وان رصيدك المتبقي هو ' . round($vacationResult->record, 2) . ' يوم';
                $botController->sendMessageChatId($vacation->Employee->telegramId, $message);
            }
        }

        return $this->ok(new VacationDailyResource($vacationDaily));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $vacationDaily = VacationDaily::find($id);
        if ($vacationDaily == null) {
            return $this->ok(null);
        }
        $vacation_id = $vacationDaily->vacation_id;
        $vacationDaily->delete();
        $vacation = $vacationDaily->Vacation;
        $vacationResult = $this->update_vacations($vacation_id);
        if ($vacation->Employee->telegramId) {
            if (isset($vacation->Employee->telegramId) && $vacation->Employee->telegramId != '') {
                $botController = new BotController(new Api);
                $message =
                    'نوع العملية : حذف اجازة اعتيادية' . PHP_EOL .
                    'تسلسل العملية :#  ' . $vacationDaily->id . PHP_EOL .
                    ' حذف الاستقطاع الخاص بالاجازة لمدة ' . $vacationDaily->record . PHP_EOL .
                    ' من رصيد الاجازات الخاص بك تبدأ من تاريخ ' . $vacationDaily->day_from . PHP_EOL .
                    ' الى تاريخ' . $vacationDaily->day_to . PHP_EOL .
                    'وان رصيدك المتبقي هو ' . round($vacationResult->record, 2) . ' يوم';
                $botController->sendMessageChatId($vacation->Employee->telegram, $message);
            }
        }

        return $this->ok(null);
    }
}
