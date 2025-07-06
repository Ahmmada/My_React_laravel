<?php

namespace App\Http\Controllers;
use App\Models\{Center, Level, Student, Attendance};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Collection;
use Carbon\Carbon;


class AttendanceController extends Controller
{
  
  

public function index(): Response
{
    $attendances = Attendance::with('center:id,name', 'level:id,name')
        ->whereIn('center_id', auth()->user()->centers->pluck('id'))
        ->orderBy('attendance_date', 'desc')
        ->get();

    return Inertia::render('attendances/index', [
        'attendances' => $attendances,
    ]);
}

public function create(): Response
{
    return Inertia::render('attendances/create', [
        'centers' => auth()->user()->centers()->select('centers.id', 'centers.name')->get(),
        'levels' => \App\Models\Level::select('id', 'name')->get(),
    ]);
}



public function store(Request $request)
{
    $request->validate([
        'attendance_date' => 'required|date',
        'center_id' => 'required|exists:centers,id',
        'level_id' => 'required|exists:levels,id',
    ]);

    // تحقق من صلاحية المركز
    if (!auth()->user()->centers->contains($request->center_id)) {
        return back()->withErrors(['msg' => 'ليس لديك صلاحية لتسجيل الحضور لهذا المركز.']);
    }

    // التحقق من التكرار
    $exists = Attendance::where('attendance_date', $request->attendance_date)
        ->where('center_id', $request->center_id)
        ->where('level_id', $request->level_id)
        ->exists();

    if ($exists) {
        return back()->withErrors(['msg' => 'تم تسجيل الحضور مسبقاً لهذا المستوى والمركز في نفس التاريخ.']);
    }

    $attendance = Attendance::create([
        'attendance_date' => $request->attendance_date,
        'center_id' => $request->center_id,
        'level_id' => $request->level_id,
    ]);

    // تسجيل الحضور
    $students = Student::where('center_id', $request->center_id)
        ->where('level_id', $request->level_id)
        ->get();

    foreach ($students as $student) {
        if (in_array($student->id, $request->excused ?? [])) {
            $status = 'excused';
        } elseif (in_array($student->id, $request->present ?? [])) {
            $status = 'present';
        } else {
            $status = 'absent';
        }

        $attendance->students()->attach($student->id, ['status' => $status]);
    }

    // 🧠 نشاط المستخدم
    activity()
        ->causedBy(auth()->user())
        ->performedOn($attendance)
        ->withProperties([
            'تاريخ الحضور' => $attendance->attendance_date,
            'المركز' => $attendance->center->name,
            'المستوى' => $attendance->level->name,
            'عدد الطلاب' => $students->count(),
            'تفاصيل الحضور' => [
                'حاضر' => $request->present ?? [],
                'مستأذن' => $request->excused ?? [],
                'غائب' => $students->pluck('id')->diff(
                    collect($request->present)->merge($request->excused)
                )->values()
            ],
        ])
        ->log('إضافة وثيقة حضور جديدة');

    return redirect()->route('attendances.index')->with('success', 'تم حفظ الحضور بنجاح.');
}

    public function getStudents(Request $request)
{
   $students  = Student::where('center_id', $request->center_id)
                       ->where('level_id', $request->level_id)
                       ->get();

    return response()->json($students);
}


public function checkDuplicate(Request $request)
{
    $request->validate([
        'attendance_date' => 'required|date',
        'center_id' => 'required|integer',
        'level_id' => 'required|integer',
    ]);

    $exists = Attendance::where('attendance_date', $request->attendance_date)
        ->where('center_id', $request->center_id)
        ->where('level_id', $request->level_id)
        ->exists();

    return response()->json(['exists' => $exists]);
}


public function show(Attendance $attendance): \Inertia\Response
{
    if (!auth()->user()->centers->contains($attendance->center_id)) {
        abort(403, 'ليس لديك صلاحية لعرض هذه الوثيقة.');
    }

    $attendance->load('students', 'center', 'level');

    return Inertia::render('attendances/show', [
        'attendance' => $attendance,
    ]);
}

public function edit(Attendance $attendance): Response
{
    // التحقق من الصلاحية
    if (!auth()->user()->centers->contains($attendance->center_id)) {
        abort(403, 'ليس لديك صلاحية لتعديل هذه الوثيقة.');
    }

    $attendance->load('students', 'center', 'level');

    return Inertia::render('attendances/edit', [
        'attendance' => [
            'id' => $attendance->id,
            'attendance_date' => $attendance->attendance_date,
            'center' => [
                'id' => $attendance->center->id,
                'name' => $attendance->center->name,
            ],
            'level' => [
                'id' => $attendance->level->id,
                'name' => $attendance->level->name,
            ],
            'students' => $attendance->students->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'pivot' => [
                        'status' => $student->pivot->status,
                    ]
                ];
            }),
        ]
    ]);
}

public function update(Request $request, Attendance $attendance)
{
    if (!auth()->user()->centers->contains($attendance->center_id)) {
        abort(403, 'ليس لديك صلاحية لتعديل هذه الوثيقة.');
    }

    $request->validate([
        'present' => 'array',
        'excused' => 'array',
    ]);

    $oldData = $attendance->students->mapWithKeys(function ($student) {
        return [$student->id => ['name' => $student->name, 'status' => $student->pivot->status]];
    });

    $students = $attendance->students;

    foreach ($students as $student) {
        if (in_array($student->id, $request->excused ?? [])) {
            $status = 'excused';
        } elseif (in_array($student->id, $request->present ?? [])) {
            $status = 'present';
        } else {
            $status = 'absent';
        }

        $attendance->students()->updateExistingPivot($student->id, ['status' => $status]);
    }

    activity()
        ->causedBy(auth()->user())
        ->performedOn($attendance)
        ->withProperties([
            'تاريخ الحضور' => $attendance->attendance_date,
            'المركز' => $attendance->center->name,
            'المستوى' => $attendance->level->name,
            'قبل التعديل' => $oldData,
            'بعد التعديل' => [
                'حاضر' => $request->present ?? [],
                'مستأذن' => $request->excused ?? [],
                'غائب' => $students->pluck('id')->diff(
                    collect($request->present)->merge($request->excused)
                )->values()
            ],
        ])
        ->log('تعديل وثيقة حضور الطلاب');

    return redirect()->route('attendances.index')->with('success', 'تم تحديث الحضور بنجاح.');
}

public function destroy(Attendance $attendance)
{
    // تحقق من صلاحية المركز قبل الحذف
    if (!auth()->user()->centers->contains($attendance->center_id)) {
        abort(403, 'ليس لديك صلاحية لحذف هذه الوثيقة.');
    }

    // جلب بيانات الوثيقة والطلاب قبل الحذف
    $attendanceData = [
        'التاريخ' => $attendance->attendance_date,
        'المركز' => $attendance->center->name ?? 'غير محدد',
        'المستوى' => $attendance->level->name ?? 'غير محدد',
        'تفاصيل الطلاب' => $attendance->students->map(function ($t) {
            return [
                'اسم الطالب' => $t->name,
                'الحالة' => $t->pivot->status,
            ];
                    })->toArray()
       ];

    // حذف الوثيقة
    $attendance->delete();

    // تسجيل عملية الحذف في سجل النشاط
    activity()
        ->causedBy(auth()->user())
        ->withProperties(['بيانات الوثيقة قبل الحذف' => $attendanceData])
        ->log('حذف وثيقة حضور الطلاب');

    return redirect()->route('attendances.index')->with('success', 'تم حذف وثيقة الحضور بنجاح.');
}


    public function reportForm(): Response
    {
        return Inertia::render('attendances/report-form', [
            'centers' => auth()->user()->centers()->select('centers.id', 'centers.name')->get(),
            'levels' => Level::select('id', 'name')->get(),
        ]);
    }

    public function generateReport(Request $request): Response
    {
        $validated = $request->validate([
            'center_id' => 'required|exists:centers,id',
            'level_id' => 'required|exists:levels,id',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        // تحقق من صلاحية المستخدم
        if (!auth()->user()->centers->contains($validated['center_id'])) {
            return redirect()->back()->withErrors([
                'msg' => 'ليس لديك صلاحية للوصول لهذا المركز.'
            ])->withInput();
        }

        $center = Center::select('id', 'name')->findOrFail($validated['center_id']);
        $level = Level::select('id', 'name')->findOrFail($validated['level_id']);

        $students = Student::where('center_id', $validated['center_id'])
            ->where('level_id', $validated['level_id'])
            ->get();

        // توليد التواريخ
        $dates = collect();
        $current = \Carbon\Carbon::parse($validated['from_date']);
        $end = \Carbon\Carbon::parse($validated['to_date']);
        while ($current <= $end) {
            $dates->push($current->format('Y-m-d'));
            $current->addDay();
        }

        $attendanceData = [];

        foreach ($students as $student) {
            $presentCount = 0;
            $absentCount = 0;
            $excusedCount = 0;

            $attendanceData[$student->name] = [
                'records' => [],
                'present' => 0,
                'absent' => 0,
                'excused' => 0,
            ];

            foreach ($dates as $date) {
                $attendance = Attendance::where('attendance_date', $date)
                    ->where('center_id', $validated['center_id'])
                    ->where('level_id', $validated['level_id'])
                    ->whereHas('students', fn($q) => $q->where('student_id', $student->id))
                    ->first();

                if ($attendance) {
                    $status = $attendance->students->find($student->id)->pivot->status;
                    $symbol = match ($status) {
                        'present' => '✔️',
                        'excused' => '⏳',
                        'absent' => '❌',
                        default => '-',
                    };

                    $attendanceData[$student->name]['records'][$date] = $symbol;

                    if ($status === 'present') $presentCount++;
                    elseif ($status === 'excused') $excusedCount++;
                    else $absentCount++;
                } else {
                    $attendanceData[$student->name]['records'][$date] = '-';
                }
            }

            $attendanceData[$student->name]['present'] = $presentCount;
            $attendanceData[$student->name]['absent'] = $absentCount;
            $attendanceData[$student->name]['excused'] = $excusedCount;
        }

        return Inertia::render('attendances/report-table', [
            'attendanceData' => $attendanceData,
            'dates' => $dates,
            'center' => $center,
            'level' => $level,
            'request' => $validated,
        ]);
    }



    public function reportAllForm(): \Inertia\Response
    {
        // تمرير بيانات فارغة عند تحميل الصفحة لأول مرة
        return Inertia::render('attendances/report-all-page', [
            'attendanceData' => [],
            'dates' => [],
            'from_date' => null,
            'to_date' => null,
        ]);
    }

    public function generateAllReport(Request $request): \Inertia\Response
    {
        $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        $students = Student::with('center', 'level')
            ->whereIn('center_id', auth()->user()->centers->pluck('id'))
            ->get();

        $dates = collect();
        $current = Carbon::parse($request->from_date);
        $end = Carbon::parse($request->to_date);
        while ($current <= $end) {
            $dates->push($current->format('Y-m-d'));
            $current->addDay();
        }

        $attendanceData = [];

        foreach ($students as $student) {
            $data = [
                'name' => $student->name,
                'center' => $student->center->name ?? 'غير محدد',
                'level' => $student->level->name ?? 'غير محدد',
                'present' => 0,
                'absent' => 0,
                'excused' => 0,
                'records' => [],
            ];

            foreach ($dates as $date) {
                $attendance = Attendance::where('attendance_date', $date)
                    ->where('center_id', $student->center_id)
                    ->where('level_id', $student->level_id)
                    ->whereHas('students', fn($q) => $q->where('student_id', $student->id))
                    ->first();

                if ($attendance) {
                    $status = $attendance->students->find($student->id)->pivot->status;

                    $symbol = match($status) {
                        'present' => '✔️',
                        'excused' => '⏳',
                        'absent' => '❌',
                        default => '-'
                    };

                    $data['records'][$date] = $symbol;

                    if ($status === 'present') $data['present']++;
                    elseif ($status === 'excused') $data['excused']++;
                    else $data['absent']++;
                } else {
                    $data['records'][$date] = '-';
                }
            }

            $attendanceData[] = $data;
        }

        // سيتم تمرير البيانات إلى report-all-page الآن
        return Inertia::render('attendances/report-all-page', [
            'attendanceData' => $attendanceData,
            'dates' => $dates->toArray(), // تحويل الـ collection إلى array
            'from_date' => $request->from_date,
            'to_date' => $request->to_date,
        ]);
    }

}



