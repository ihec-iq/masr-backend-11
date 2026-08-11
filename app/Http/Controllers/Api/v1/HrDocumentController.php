<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Bonus\BonusDegreeStageResource;
use App\Http\Resources\Hr\HrDocumentResource;
use App\Http\Resources\Hr\HrDocumentResourceCollection;
use App\Models\Employee;
use App\Models\HrDocument;
use App\Enum\EnumTypeChoseShareDocument;
use App\Http\Resources\Bonus\BonusDegreeStageMiniResource;
use App\Http\Resources\Employee\EmployeeResource;
use App\Http\Resources\Hr\HrDocumentMiniResource;
use App\Models\BonusDegreeStage;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HrDocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->ok(HrDocumentResource::collection(HrDocument::get()));
    }

    public function filter(Request $request)
    {
        $request->filled('limit') ? $limit = $request->limit : $limit = 10;
        $data = HrDocument::orderBy('id', 'desc');
        if (!$request->isNotFilled('employeeName') && $request->employeeName != '' && $request->employeeName != null) {
            $data = $data->orWhereRelation('Employee', 'name', 'like', '%' . $request->employeeName . '%');
            $data = $data->orWhere('title', 'like', '%' . $request->employeeName . '%');
        }

        if (!$request->isNotFilled('employeeId') && $request->employeeId != 0 && $request->employeeId != null) {
            $data = $data->Where('employee_id', '=', $request->employeeId);
        }
        $data = $data->paginate($limit); //return $data;
        if (empty($data) || $data == null) {
            return $this->error(__('general.loadFailed'));
        } else {
            return $this->ok(new HrDocumentResourceCollection($data));
        }
    }
    /**
     * Store a newly created resource in storage.
     */
    public function addHrDocument(Request $request, $employeeId): HrDocument
    {
        $data = HrDocument::create([
            'title' => $request->title,
            'number' => $request->number,
            'issue_date' => $request->issue_date,
            'employee_id' => $employeeId,
            'hr_document_type_id' => $request->hr_document_type_id,
            'add_days' => $request->add_days,
            'add_months' => $request->add_months,
            'user_create_id' => Auth::user()->id,
            'user_update_id' => Auth::user()->id,
            'notes' => $request->notes,
        ]);

        if ($request->hasfile('FilesDocument')) {
            $document = new DocumentController();
            $document->store_multi_hr(
                request: $request,
                documentable_id: $data->id,
                documentable_type: HrDocument::class,
                pathFolder: $employeeId
            );
        }
        $this->update_employee_date_bonus($employeeId);
        return $data;
    }
    public function update_employee_date_bonus($employeeId)
    {
        $employeeInfo = $this->check_bonus_employee($employeeId); //return $employeeInfo['nextDateBonus'];
        $employee = Employee::find($employeeId);
        $employee->date_next_bonus = $employeeInfo['nextDateBonus'];
        $employee->save();
        return new EmployeeResource($employee);
    }
    public function check_bonus_employee($employeeId)
    {
        $employee = Employee::find($employeeId);
        $filteredArray = [];
        if ($employee) {
            $increseDay = 0;
            $increseMonths = 0;
            $date_last_bonus = $employee->date_last_bonus; //return $date_last_bonus;

            if ($date_last_bonus) {
                #region Add Ponus
                $HrDocuments = HrDocument::where('employee_id', $employeeId)
                    ->whereBetween('issue_date', [$date_last_bonus, Carbon::parse($date_last_bonus)->addYear()])
                    ->where("is_active", "=", true)
                    ->where(function ($query) {
                        $query->where('add_days', '>', 0)
                            ->orWhere('add_months', '>', 0);
                    })
                    ->with('Type')
                    ->orderBy('add_months', 'DESC')
                    ->orderBy('add_days', 'DESC')
                    ->get()
                    ->take(4);
                $repeted6Month = 0; //Log::info($HrDocuments);
                foreach ($HrDocuments as $row) {
                    if ($row->add_months == 6) {
                        if ($repeted6Month < 1) {
                            $repeted6Month++;
                            $filteredArray[] = $row;
                            $increseDay += $row->add_days;
                            $increseMonths += $row->add_months;
                        }
                        continue;
                    }
                    $filteredArray[] = $row;
                    $increseDay += $row->add_days;
                    $increseMonths += $row->add_months;
                }
                #endregion
                #region Add Subtract
                $HrDocuments = HrDocument::where('employee_id', $employeeId)
                    ->whereBetween('issue_date', [$date_last_bonus, Carbon::parse($date_last_bonus)->addYear()])
                    ->where("is_active", "=", true)
                    ->where('add_days', '<', 0)
                    ->orWhere('add_months', '<', 0)
                    ->with('Type')
                    ->orderBy('add_months', 'DESC')
                    ->orderBy('add_days', 'DESC')
                    ->first();
                if ($HrDocuments) {
                    $increseDay -= $HrDocuments->add_days;
                    $increseMonths += $HrDocuments->add_months;
                    $filteredArray[] = $HrDocuments;
                }
                #endregion
            }

            $result = [
                'id' => $employee->id,
                'name' => $employee->name,
                'currentDateBonus' => Carbon::parse($date_last_bonus)->format('Y-m-d'),
                'numberIncreseDayes' => $increseDay,
                'numberIncreseMonths' => $increseMonths,
                'nextDateBonus' => Carbon::parse($date_last_bonus)->addYear(1)->addDay($increseDay * -1)->addMonths($increseMonths * -1)->format('Y-m-d'),
                'Documents' => HrDocumentResource::collection($filteredArray)
            ];
            return $result;
        }
    }


    public function check_bonus_employee_total(Request $request, $employeeId)
    {
        $attration = 4;
        if ($request->has('attration')) {
            $attration = $request->attration;
        }
        if ($attration <= 0) return response()->json([]);

        $employee = Employee::find($employeeId);
        if (!$employee) {
            return response()->json(['message' => 'Employee not found'], 404);
        }

        // If there's no baseline, there's nothing to compute
        if (empty($employee->date_last_bonus))  return response()->json([]);

        $date_last_bonus = $employee->date_last_bonus; //return $date_last_bonus;
        $degree_stage_id = $employee->degree_stage_id; //return $degree_stage_id;
        $result = [];
        if ($date_last_bonus) {
            $nextBonus = $this->getNextBonus(
                $employee->id,
                (string) $employee->name,
                $date_last_bonus,
                $degree_stage_id
            );
            $result[] = $nextBonus;

            $attration--;
            if ($attration == 0) return $result;

            $date_last_bonus = $nextBonus['nextDateBonus'];
            $degree_stage_id = $nextBonus['DegreeStage']['id'];
            $nextBonus = $this->getNextBonus(
                $employee->id,
                (string) $employee->name,
                $date_last_bonus,
                $degree_stage_id
            );
            $result[] = $nextBonus;
            $attration--;
            if ($attration == 0) return $result;

            while ($nextBonus['nextDateBonus']  < Carbon::now()->addYear()) {
                $date_last_bonus = $nextBonus['nextDateBonus'];
                $degree_stage_id = $nextBonus['DegreeStage']['id'];
                $nextBonus = $this->getNextBonus(
                    $employee->id,
                    (string) $employee->name,
                    $date_last_bonus,
                    $degree_stage_id
                );
                $result[] = $nextBonus;
                $attration--;
                if ($attration == 0) return $result;
            }
        }
        return $result;
    }
    public function getNextBonus($employeeId, $employeeName, $date_last_bonus, $degree_stage_id)
    {
        // $employee = Employee::find(id: $employeeId);
        // if (!$employee) return response()->json(['message' => 'Employee not found'], 404);

        $filteredArray = [];
        $TotalDocuments = [];
        $increseDay = 0;
        $increseMonths = 0;
        $date_last_bonus = Carbon::parse($date_last_bonus)->format('Y-m-d'); // Ensure date is in Y-m-d format
        $nextDegreeStage = BonusDegreeStage::find($degree_stage_id + 1);
        //Log::alert($nextDegreeStage->title . " at " . $date_last_bonus);
        if (!$nextDegreeStage) return response()->json(['message' => 'Next degree stage not found'], 404);
        if ($date_last_bonus) {
            #region Add Ponus
            $HrDocuments = HrDocument::where('employee_id', $employeeId)
                ->whereBetween('issue_date', [$date_last_bonus, Carbon::parse($date_last_bonus)->addYear()])
                ->where("is_active", "=", true)
                ->where(function ($query) {
                    $query->where('add_days', '>', 0)
                        ->orWhere('add_months', '>', 0);
                })
                ->with('Type')
                ->orderBy('add_months', 'DESC')
                ->orderBy('add_days', 'DESC')
                ->get()
                ->take(4);
            $repeted6Month = 0; //Log::info($HrDocuments);
            foreach ($HrDocuments as $row) {
                if ($row->add_months == 6) {
                    if ($repeted6Month < 1) {
                        $repeted6Month++;
                        $filteredArray[] = $row;
                        $increseDay += $row->add_days;
                        $increseMonths += $row->add_months;
                    }
                    continue;
                }
                $filteredArray[] = $row;
                $increseDay += $row->add_days;
                $increseMonths += $row->add_months;
                if (count($filteredArray) == 3) break;
            }
            $TotalDocuments = $HrDocuments;
            #endregion
            #region Add Subtract
            $HrDocuments = HrDocument::where('employee_id', $employeeId)
                ->whereBetween('issue_date', [$date_last_bonus, Carbon::parse($date_last_bonus)->addYear()])
                ->where("is_active", "=", true)
                ->where('add_days', '<', 0)
                ->orWhere('add_months', '<', 0)
                ->with('Type')
                ->orderBy('add_months', 'DESC')
                ->orderBy('add_days', 'DESC')
                ->first();
            if ($HrDocuments) {
                $increseDay -= $HrDocuments->add_days;
                $increseMonths -= $HrDocuments->add_months;
                $filteredArray[] = $HrDocuments;
                $TotalDocuments[] = $HrDocuments;
            }
            #endregion
        }

        $result = [
            'id' => $employeeId,
            'name' => $employeeName,
            'currentDateBonus' => Carbon::parse($date_last_bonus)->format('Y-m-d'),
            'numberIncreseDayes' => $increseDay,
            'numberIncreseMonths' => $increseMonths,
            'DegreeStage' => new BonusDegreeStageMiniResource($nextDegreeStage),
            'nextDateBonus' => Carbon::parse($date_last_bonus)->addYear(1)->addDay($increseDay * -1)->addMonths($increseMonths * -1)->format('Y-m-d'),
            'Documents' => HrDocumentMiniResource::collection($filteredArray),
            'TotalDocuments' =>  HrDocumentMiniResource::collection($TotalDocuments),

        ];
        return $result;
    }
    public function get_check_bonus_employee($employeeId)
    {
        $employee = Employee::find($employeeId);
        $selectedDocs = [];
        if ($employee) {
            $increseDay = 0;
            $increseMonths = 0;
            $date_last_bonus = $employee->date_last_bonus; //return $date_last_bonus;
            $filteredArray = [];

            if ($date_last_bonus) {
                #region Add Ponus
                $HrDocuments = $employee->HrDocuments()
                    ->whereBetween('issue_date', [$date_last_bonus, Carbon::parse($date_last_bonus)->addYear()])
                    ->where("is_active", "=", true)
                    ->where('add_days', '>', 0)
                    ->orWhere('add_months', '>', 0)
                    ->with('Type')
                    ->orderBy('add_months', 'DESC')
                    ->orderBy('add_days', 'DESC')
                    ->get()
                    ->take(4);
                $repeted6Month = 0;
                foreach ($HrDocuments as $row) {
                    if ($row->add_days == 180) {
                        if ($repeted6Month < 1) {
                            $repeted6Month++;
                            $filteredArray[] = $row;
                            $increseDay += $row->add_days;
                            $increseMonths += $row->add_months;
                        }
                        continue;
                    }
                    $filteredArray[] = $row;
                    $increseDay += $row->add_days;
                    $increseMonths += $row->add_months;
                }
                $selectedDocs[] = $filteredArray;

                #endregion
                #region Add Subtract
                $HrDocuments = $employee->HrDocuments()
                    ->whereBetween('issue_date', [$date_last_bonus, Carbon::parse($date_last_bonus)->addYear()])
                    ->where("is_active", "=", true)
                    ->where('add_days', '<', 0)
                    ->orWhere('add_months', '<', 0)
                    ->with('Type')
                    ->orderBy('add_months', 'DESC')
                    ->orderBy('add_days', 'DESC')
                    ->first();
                if ($HrDocuments) {
                    $increseDay -= $HrDocuments->add_days;
                    $increseMonths += $HrDocuments->add_months;
                    $filteredArray[] = $HrDocuments;
                }
                $selectedDocs[] = $filteredArray;
                #endregion
            }

            $result = [
                'id' => $employee->id,
                'name' => $employee->name,
                'currentDateBonus' => Carbon::parse($date_last_bonus)->format('Y-m-d'),
                'numberIncreseDayes' => $increseDay + ($increseMonths * 30),
                'nextDateBonus' => Carbon::parse($date_last_bonus)->addYear(1)->addDay($increseDay * -1)->addMonths($increseMonths * -1)->format('Y-m-d'),
                'Documents' => HrDocumentResource::collection($selectedDocs)
            ];
            return $result;

            //date_next_bonus
        }
    }
    public function store(Request $request)
    {
        //Log::info($request);
        if ($request->chosePushBy == EnumTypeChoseShareDocument::None->value || $request->chosePushBy == EnumTypeChoseShareDocument::ToEmployee->value) {
            $data = $this->addHrDocument(request: $request, employeeId: $request->employee_id);
        } elseif ($request->chosePushBy == EnumTypeChoseShareDocument::ToSection->value) {
            $filter_bill[] = ['section_id', '=', $request->selectedSectionId];
            $EmployeesBySection = Employee::where($filter_bill)->get();
            foreach ($EmployeesBySection as $key => $employee) {
                $data = $this->addHrDocument(request: $request, employeeId: $employee->id);
            }
        } elseif ($request->chosePushBy == EnumTypeChoseShareDocument::ToAllEmployees->value) {
            $EmployeesBySection = Employee::get();
            foreach ($EmployeesBySection as $key => $employee) {
                $data = $this->addHrDocument(request: $request, employeeId: $employee->id);
            }
        } elseif ($request->chosePushBy == EnumTypeChoseShareDocument::ToCustom->value) {
            $EmployeesBySection = json_decode($request->SelectedEmployeesData);
            foreach ($EmployeesBySection as $key => $employee) {
                $data = $this->addHrDocument(request: $request, employeeId: $employee->id);
            }
        }
        return $this->ok(new HrDocumentResource($data));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return $this->ok(new HrDocumentResource(HrDocument::find($id)));
    }

    public function hrDocumentReportByEmployee($employeeId)
    {
        $data = HrDocument::orderBy('issue_date', 'desc')
            ->where('employee_id', '=', $employeeId)
            ->get();
        $result = '';

        $data = json_decode((HrDocumentResource::collection($data))->toJson(), true);

        foreach ($data as $key => $value) {
            $result .=
                "تسلسل الملف :#  " . $value['id'] . PHP_EOL .
                "اسم الكتاب " . $value['title'] . PHP_EOL .
                "نوع الكتاب " . $value['Type']['name'] . PHP_EOL .
                "تاريخ الكتاب " . $value['issueDate'] . PHP_EOL .
                "المرافقات " . PHP_EOL;

            foreach ($value['Files'] as $keyFile => $file) {
                $result .= "الملف  " . $keyFile . PHP_EOL;
                $result .= "الرابط  " . $file['path'] . PHP_EOL;
                $filePath = $file['path'];
            }
            $result .= "--------------------------------------------------" . PHP_EOL;
        }
        return $result;
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //Log::info($request);
        $data = HrDocument::find($id);
        $employeeId = $request->employee_id;

        $data->title = $request->title;
        $data->number = $request->number;
        $data->issue_date = $request->issue_date;
        $data->employee_id = $request->employee_id;
        $data->hr_document_type_id = $request->hr_document_type_id;
        $data->add_days = $request->add_days;
        $data->add_months = $request->add_months;
        $data->is_active = $request->is_active;
        $data->user_update_id = Auth::user()->id;
        //Log::info($data);
        $data->save();
        //$data = HrDocument::find($data->id);
        $this->update_employee_date_bonus($employeeId);
        if ($request->hasfile('FilesDocument')) {
            $document = new DocumentController();
            $document->store_multi_hr(
                request: $request,
                documentable_id: $id,
                documentable_type: HrDocument::class,
                pathFolder: $employeeId
            );
        }
        return $this->ok(new HrDocumentResource($data));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $HrDocument = HrDocument::find($id);
        $employee_id = $HrDocument->employee_id;
        $HrDocument->delete();
        $this->update_employee_date_bonus($employee_id);
        return $this->ok($HrDocument);
    }
}
