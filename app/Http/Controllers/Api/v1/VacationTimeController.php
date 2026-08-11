<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Vacation\VacationTimeResource;
use App\Http\Resources\Vacation\VacationTimeResourceCollection;
use App\Models\VacationTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;

class VacationTimeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $vacationDailies = VacationTime::get();

        //return $this->ok($vacationDailies);

        return $this->ok(new VacationTimeResource($vacationDailies));
    }

    public function filter(Request $request)
    {
        $request->filled('limit') ? $limit = $request->limit : $limit = 10;

        $data = VacationTime::orderBy('date', 'desc');

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
            $where[] = ['date', '>=', $from];
            $where[] = ['date', '<', $to];
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
            return $this->ok(new VacationTimeResourceCollection($data));
        }
    }

    public function getTimelyMyReport()
    {
        $response = [
            'myTimelyVacationReport' => $this->timeReportByEmployee(auth()->user()->Employee->id)
        ];
        return $this->ok($response);
    }
    public function timeReportByEmployee($employeeId)
    {
        $data = VacationTime::orderBy('time_from', 'desc')
            ->whereRelation('Vacation', 'employee_id', '=', $employeeId)
            ->get();
        $result = '';
        foreach ($data as $key => $value) {
            $result .=
                'تسلسل العملية :#  ' . $value->id . PHP_EOL .
                'تم استقطاع اجازة لمدة ' . $value->record . ' ساعة ليوم ' . $value->date . PHP_EOL .
                ' من رصيد الاجازات الخاص بك ' . PHP_EOL .
                ' تبدأ من وقت ' . $value->time_from . PHP_EOL .
                ' الى وقت' . $value->time_to . PHP_EOL .
                '-----------------------------------------'. PHP_EOL;
        }
        return $result;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $vacation = json_decode($request->Vacation);
        $reason = json_decode($request->Reason);
        $data = [
            'vacation_id' => $vacation->id,
            'date' => $request->date,
        ];
        $vacationTime = VacationTime::where($data)->first();
        if ($vacationTime) {
            return $this->error(
                'this is Found in System',
                new VacationTimeResource($vacationTime)
            );
        }
        $data = [
            'vacation_id' => $vacation->id,
            'vacation_reason_id' => $reason->id,
            'record' => $request->record,
            'date' => $request->date,
            'time_from' => $request->timeFrom,
            'time_to' => $request->timeTo,
            'user_create_id' => auth()->user()->id,
            'user_update_id' => auth()->user()->id,
        ];
        $vacationTime = VacationTime::create($data);
        $vacationResult = $this->update_vacations($vacation->id);
        if ($vacation->Employee->telegramId) {
            if (isset($vacation->Employee->telegramId) && $vacation->Employee->telegramId != '') {
                $botController = new BotController(new Api());
                $message =
                    'نوع العملية : اضافة اجازة زمنية' . PHP_EOL .
                    'تسلسل العملية :#' . $vacationTime->id . PHP_EOL .
                    ' تم استقطاع اجازة عدد ساعات ' . $request->record . PHP_EOL .
                    ' من رصيد الاجازات الخاص بك ليوم ' . $request->date . PHP_EOL .
                    'وان رصيدك المتبقي هو ' . round($vacationResult->record, 2) . ' يوم';
                $botController->sendMessageChatId($vacation->Employee->telegramId, $message);
            }
        }

        return $this->ok(new VacationTimeResource($vacationTime));
    }

    /**
     * Display the specified resource.
     */
    public function show(VacationTime $vacationTime)
    {
        return $this->ok(new VacationTimeResource($vacationTime));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $vacationTime = VacationTime::find($id);
        $vacation = json_decode($request->Vacation);
        $Reason = json_decode($request->Reason);
        $vacationTime->vacation_id = $vacation->Employee->id;
        $vacationTime->vacation_reason_id = $Reason->id;
        $vacationTime->record = $request->record;
        $vacationTime->date = $request->date;
        $vacationTime->time_from = $request->timeFrom;
        $vacationTime->time_to = $request->timeTo;
        $vacationTime->user_update_id = Auth::user()->id;
        $vacationTime->save();
        $vacationResult = $this->update_vacations($vacation->id);
        if ($vacation->Employee->telegramId) {
            if (isset($vacation->Employee->telegramId) && $vacation->Employee->telegramId != '') {
                $botController = new BotController(new Api());
                $message =
                    'نوع العملية : تعديل اجازة زمنية' . PHP_EOL .
                    'تسلسل العملية :#' . $vacationTime->id . PHP_EOL .
                    ' تم استقطاع اجازة عدد ساعات ' . $request->record . PHP_EOL .
                    ' من رصيد الاجازات الخاص بك ليوم ' . $request->date . PHP_EOL .
                    'وان رصيدك المتبقي هو ' . round($vacationResult->record, 2) . ' يوم';
                $botController->sendMessageChatId($vacation->Employee->telegramId, $message);
            }
        }

        return $this->ok(new VacationTimeResource($vacationTime));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $vacationTime = VacationTime::find($id);
        $vacationTime->delete();
        if ($vacationTime == null) {
            return $this->ok(null);
        }
        $vacation_id = $vacationTime->vacation_id;
        $vacation = $vacationTime->Vacation;

        $vacationTime->delete();
        $vacationResult = $this->update_vacations($vacation_id);
        if ($vacation->Employee->telegramId) {
            if (isset($vacation->Employee->telegramId) && $vacation->Employee->telegramId != '') {
                $botController = new BotController(new Api());
                $message =
                    'نوع العملية : حذف اجازة زمنية' . PHP_EOL .
                    'تسلسل العملية :#' . $vacationTime->id . PHP_EOL .
                    '  استقطاع اجازة عدد ساعات ' . $vacationTime->record . PHP_EOL .
                    ' من رصيد الاجازات الخاص بك ليوم ' . $vacationTime->date . PHP_EOL .
                    'وان رصيدك المتبقي هو ' . round($vacationResult->record, 2) . ' يوم';
                $botController->sendMessageChatId($vacation->Employee->telegram, $message);
            }
        }

        return $this->ok(null);
    }

    public function update_vacations($id)
    {
        $vacationController = new VacationController();

        return $vacationController->update_vacations($id);
    }
}
