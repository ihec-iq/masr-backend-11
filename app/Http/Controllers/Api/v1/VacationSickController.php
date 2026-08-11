<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Vacation\VacationSickResource;
use App\Http\Resources\Vacation\VacationSickResourceCollection;
use App\Models\VacationSick;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;

class VacationSickController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $vacationDailies = VacationSick::get();

        //return $this->ok($vacationDailies);

        return $this->ok(new VacationSickResource($vacationDailies));
    }

    public function filter(Request $request)
    {

        $request->filled('limit') ? $limit = $request->limit : $limit = 10;

        $data = VacationSick::orderBy('day_from', 'desc');

        if (!$request->isNotFilled('record') && $request->record != '' && $request->record != '0') {
            $data = $data->orWhere('record', 'like', '%' . $request->record . '%');
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
            $where[] = ['day_to', '<', $to];
            $data = $data->where($where);
        }
        #region "Check Premission [vacation office ,vacation center ]"
        $data = $data->whereHas('Vacation.Employee.EmployeeType', function ($query) {
            $employeeType = ["1"];
            if (Auth::user()->hasAnyPermission(['vacation office'])) {
                array_push($employeeType, "2");
            }
            if (Auth::user()->hasAnyPermission(['vacation center'])) {
                array_push($employeeType, "3");
            }
            array_push($employeeType, "4");
            $query->whereIn('id', $employeeType);
        });
        #endregion
        $data = $data->paginate($limit);

        if (empty($data) || $data == null) {
            return $this->error(__('general.loadFailed'));
        } else {
            return $this->ok(new VacationSickResourceCollection($data));
        }
    }
    public function sickReportByEmployee($employeeId)
    {
        $data = VacationSick::orderBy('day_from', 'desc')
            ->whereRelation('Vacation', 'employee_id', '=', $employeeId)
            ->get();
        $result = '';
        foreach ($data as $key => $value) {
            $result .=
                "تسلسل العملية :#  " . $value->id . PHP_EOL .
                "تم استقطاع اجازة لمدة " . $value->record . PHP_EOL .
                " من رصيد الاجازات الخاص بك " . PHP_EOL .
                " تبدأ من تاريخ " . $value->day_from . PHP_EOL .
                " الى تاريخ" . $value->day_to . PHP_EOL .
                "--------------------------------------------------";
        }
        return $result;
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $vacation = json_decode($request->Vacation);
        $data = [
            'vacation_id' => $vacation->id,
            'day_from' => $request->dayFrom,
        ];

        $vacationTime = VacationSick::where($data)->first();
        if ($vacationTime) {
            return $this->error(
                "this is Found in System",
                new VacationSickResource($vacationTime)
            );
        }
        $data = [
            'vacation_id' => $vacation->id,
            'record' => $request->record,
            'day_from' => $request->dayFrom,
            'day_to' => $request->dayTo,
            'user_create_id' => auth()->user()->id,
            'user_update_id' => auth()->user()->id
        ];
        $vacationSick = VacationSick::create($data);
        $vacationResult = $this->update_vacations($vacation->id);
        if ($vacation->Employee->telegramId) {
            if (isset($vacation->Employee->telegramId) && $vacation->Employee->telegramId != '') {
                $botController = new BotController(new Api);
                $message =
                    "نوع العملية : اضافة اجازة مرضية" . PHP_EOL .
                    "تسلسل العملية :#  " . $vacationSick->id . PHP_EOL .
                    "تم استقطاع اجازة لمدة " . $request->record . PHP_EOL .
                    " من رصيد الاجازات الخاص بك تبدأ من تاريخ " . $request->dayFrom . PHP_EOL .
                    " الى تاريخ" . $request->dayTo . PHP_EOL .
                    "وان رصيدك المتبقي هو " . round($vacationResult->record_sick, 2) . " يوم";
                $botController->sendMessageChatId($vacation->Employee->telegramId, $message);
            }
        }
        return $this->ok(new VacationSickResource($vacationSick));
    }

    /**
     * Display the specified resource.
     */
    public function show(VacationSick $vacationSick)
    {
        return $this->ok(new VacationSickResource($vacationSick));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $vacationSick = VacationSick::find($id);
        $vacation = json_decode($request->Vacation);

        $vacationSick->vacation_id = $vacation->id;
        $vacationSick->record = $request->record;
        $vacationSick->day_from = $request->dayFrom;
        $vacationSick->day_to = $request->dayTo;
        $vacationSick->user_update_id = Auth::user()->id;
        $vacationSick->save();
        $vacationResult = $this->update_vacations($vacation->id);
        if ($vacation->Employee->telegramId) {
            if (isset($vacation->Employee->telegramId) && $vacation->Employee->telegramId != '') {
                $botController = new BotController(new Api);
                $message =
                    "نوع العملية : تعديل اجازة مرضية" . PHP_EOL .
                    "تسلسل العملية :#  " . $vacationSick->id . PHP_EOL .
                    "تم استقطاع اجازة لمدة " . $request->record . PHP_EOL .
                    " من رصيد الاجازات الخاص بك تبدأ من تاريخ " . $request->dayFrom . PHP_EOL .
                    " الى تاريخ" . $request->dayTo . PHP_EOL .
                    "وان رصيدك المتبقي هو " . round($vacationResult->record_sick, 2) . " يوم";
                $botController->sendMessageChatId($vacation->Employee->telegramId, $message);
            }
        }
        return $this->ok(new VacationSickResource($vacationSick));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {



        $vacationSick = VacationSick::find($id);
        if ($vacationSick == null)
            return $this->ok(null);
        $vacation_id = $vacationSick->vacation_id;
        $vacationSick->delete();
        $vacation = $vacationSick->Vacation;
        $vacationResult = $this->update_vacations($vacation_id);
        if ($vacation->Employee->telegramId) {
            if (isset($vacation->Employee->telegramId) && $vacation->Employee->telegramId != '') {
                $botController = new BotController(new Api());
                $message =
                    "نوع العملية : حذف اجازة مرضية" . PHP_EOL .
                    "تسلسل العملية :# " . $vacationSick->id . PHP_EOL .
                    " حذف الاستقطاع الخاص بالاجازة لمدة " . $vacationSick->record . PHP_EOL .
                    " من رصيد الاجازات الخاص بك تبدأ من تاريخ " . $vacationSick->day_from . PHP_EOL .
                    " الى تاريخ" . $vacationSick->day_to . PHP_EOL .
                    "وان رصيدك المتبقي هو " . round($vacationResult->record_sick, 2) . " يوم";
                $botController->sendMessageChatId($vacation->Employee->telegram, $message);
            }
        }
        return $this->ok(null);
    }
    public function update_vacations($id)
    {
        $vacationController = new VacationController();
        return $vacationController->update_sick_vacations($id);
    }
}
