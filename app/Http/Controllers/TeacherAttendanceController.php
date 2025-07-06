<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\Group;
use App\Models\TeacherAttendance;
use App\Models\SalarySetting;
use Illuminate\Validation\Rule;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Inertia\Inertia; 
use Inertia\Response;
use Illuminate\Support\Facades\Validator; 
use Illuminate\Support\Facades\Log;

use Illuminate\Http\Request;

class TeacherAttendanceController extends Controller
{




public function index()
{
    $attendances = TeacherAttendance::with(['group:id,name', 'teachers:id'])        ->whereIn('group_id', auth()->user()->groups->pluck('id'))
        ->orderBy('attendance_date', 'desc')
        ->get();

    return Inertia::render('teacher_attendance/index', [
        'attendances' => $attendances,
    ]);
}
    // ... باقي الدوال (create, store, show, edit, update, destroy)









public function create() 
{
    
    
    $userGroups = auth()->user()->groups->pluck('id');
    $groups = Group::select('id', 'name')->whereIn('id', $userGroups)->get();
    return Inertia::render('teacher_attendance/create', compact('groups'));
}
    public function store(Request $request)
    {
        // استخدام Validator يدويًا للحصول على أخطاء التحقق
        $validator = Validator::make($request->all(), [ // استخدام Facade
            'attendance_date' => [
                'required',
                'date',
                Rule::unique('teacher_attendances')->where(function ($query) use ($request) {
                    return $query->where('group_id', $request->group_id);
                })
            ],
            'group_id' => 'required|exists:groups,id',
            'teachers' => 'required|array',
            'teachers.*.arrival_time' => 'nullable|date_format:H:i',
            'teachers.*.departure_time' => [
                'nullable',
                'date_format:H:i',
                function ($attribute, $value, $fail) use ($request) {
                    // $attribute سيكون مثل 'teachers.5.departure_time'
                    // نريد استخراج '5' مباشرة
                    $parts = explode('.', $attribute);
                    $teacherId = $parts[1]; // هذا هو معرف المعلم الفعلي (مثلاً '5')

                    // التأكد من وجود المفتاح في $request->teachers قبل الوصول إليه
                    if (!isset($request->teachers[$teacherId])) {
                        $fail('بيانات المدرس غير موجودة.');
                        return;
                    }

                    $arrival = $request->teachers[$teacherId]['arrival_time'] ?? null;

                    // التحقق فقط إذا كان وقت الانصراف و وقت الحضور موجودين
                    if ($value && $arrival && strtotime($value) <= strtotime($arrival)) {
                        $fail('وقت الانصراف يجب أن يكون بعد وقت الحضور للمدرس.');
                    }
                }
            ]
        ]);

        
            // تحقق من صلاحية المجموعة
    if (!auth()->user()->groups->contains($request->group_id)) {
        return back()->withErrors(['msg' => 'ليس لديك صلاحية لتسجيل الحضور لهذه المجموعة.']);
    }

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        $attendance = TeacherAttendance::create([
            'attendance_date' => $validated['attendance_date'],
            'group_id' => $validated['group_id']
        ]);

        foreach ($validated['teachers'] as $teacher_id => $times) {
            // تأكد من أن arrival_time و departure_time ليست null قبل استخدامها
            $arrival_time = !empty($times['arrival_time']) ? $times['arrival_time'] : null;
            $departure_time = !empty($times['departure_time']) ? $times['departure_time'] : null;

            $attendance->teachers()->attach($teacher_id, [
                'arrival_time' => $arrival_time,
                'departure_time' => $departure_time
            ]);
        }

        // تسجيل سجل العمليات
        activity()
            ->causedBy(auth()->user())
            ->performedOn($attendance)
            ->withProperties([
                'التاريخ' => $validated['attendance_date'],
                'المجموعة' => $attendance->group->name ?? 'غير محدد',
                'تفاصيل المدرسين' => $validated['teachers']
            ])
            ->log('إضافة وثيقة تحضير المدرسين');

        return redirect()->route('teacher_attendance.index')->with('success', 'تم تسجيل حضور المدرسين بنجاح.');
    }

    public function getTeachers(Request $request)
    {
        // هذا المسار سيستقبل طلب Inertia (POST) ويعيد JSON
        $request->validate(['group_id' => 'required|exists:groups,id']);

        $teachers = Teacher::where('group_id', $request->group_id)
            ->select('id', 'name')
            ->get()
            ->map(function ($teacher) {
                return [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                    // يمكن إضافة حقول افتراضية لوقت الحضور/الانصراف إذا لزم الأمر
                    'arrival_time' => null,
                    'departure_time' => null,
                ];
            });

        return response()->json([
            'data' => $teachers // المفتاح الأساسي يجب أن يكون "data"
        ]);
    }







public function show(TeacherAttendance $teacherAttendance): \Inertia\Response
{
    
            if (!auth()->user()->groups->contains($teacherAttendance->group_id)) {
        abort(403, 'ليس لديك صلاحية لعرض هذه الوثيقة.');
    }
    
    $teacherAttendance->load(['teachers:id,name', 'group:id,name']);

    $attendance_date = Carbon::parse($teacherAttendance->attendance_date);

    // تجهيز المدرسين
    $teachers = $teacherAttendance->teachers->map(function ($teacher) {
        $arrival = $teacher->pivot->arrival_time
            ? Carbon::parse("1970-01-01 " . $teacher->pivot->arrival_time)
            : null;

        $departure = $teacher->pivot->departure_time
            ? Carbon::parse("1970-01-01 " . $teacher->pivot->departure_time)
            : null;

        $hours = $arrival && $departure
            ? $departure->diff($arrival)->format('%H:%I')
            : '-';

        return [
            'id' => $teacher->id,
            'name' => $teacher->name,
            'arrival_time' => $teacher->pivot->arrival_time,
            'arrival_time_ar' => $teacher->pivot->arrival_time
                ? Carbon::parse($teacher->pivot->arrival_time)->translatedFormat('H:i')
                : '-',
            'departure_time' => $teacher->pivot->departure_time,
            'departure_time_ar' => $teacher->pivot->departure_time
                ? Carbon::parse($teacher->pivot->departure_time)->translatedFormat('H:i')
                : '-',
            'hours' => $hours,
        ];
    });

    return Inertia::render('teacher_attendance/show', [
        'teacherAttendance' => [
            'id' => $teacherAttendance->id,
            'attendance_date' => $attendance_date->toDateString(),
            'attendance_day' => $attendance_date->translatedFormat('l'),
            'group' => $teacherAttendance->group,
            'teachers' => $teachers,
        ]
    ]);
}

public function edit(TeacherAttendance $teacherAttendance): \Inertia\Response
{
    
    // التحقق من الصلاحية
    if (!auth()->user()->groups->contains($teacherAttendance->group_id)) {
        abort(403, 'ليس لديك صلاحية لتعديل هذه الوثيقة.');
    }
    
    $teacherAttendance->load(['group:id,name', 'teachers:id,name']);
    return Inertia::render('teacher_attendance/edit', [
        'teacherAttendance' => $teacherAttendance,
    ]);
}

public function update(Request $request, TeacherAttendance $teacherAttendance)
{
    
    if (!auth()->user()->groups->contains($teacherAttendance->group_id)) {
        abort(403, 'ليس لديك صلاحية لتعديل هذه الوثيقة.');
    }
    
    
    $request->validate([
        'attendance_date' => 'required|date',
        'teachers' => 'required|array'
    ]);

    $oldData = [
        'التاريخ القديم' => $teacherAttendance->attendance_date,
        'المدرسين قبل' => $teacherAttendance->teachers->map(function ($t) {
            return [
                'اسم المدرس' => $t->name,
                'وقت الحضور' => $t->pivot->arrival_time,
                'وقت الانصراف' => $t->pivot->departure_time
            ];
        })->toArray()
    ];

    $teacherAttendance->update([
        'attendance_date' => $request->attendance_date
    ]);

    // حذف العلاقات القديمة
    $teacherAttendance->teachers()->detach();

    // إعادة الحفظ
    foreach ($request->teachers as $teacher_id => $times) {
        $teacherAttendance->teachers()->attach($teacher_id, [
            'arrival_time' => $times['arrival_time'],
            'departure_time' => $times['departure_time']
        ]);
    }

    $newData = [
        'التاريخ الجديد' => $teacherAttendance->attendance_date,
        'المدرسين الجدد' => $request->teachers
    ];

    // سجل العمليات
    activity()
        ->causedBy(auth()->user())
        ->performedOn($teacherAttendance)
        ->withProperties([
            'قبل التعديل' => $oldData,
            'بعد التعديل' => $newData
        ])
        ->log('تعديل وثيقة تحضير المدرسين');

    return redirect()->route('teacher_attendance.index')->with('success', 'تم تحديث الحضور بنجاح.');
}


public function destroy(TeacherAttendance $teacherAttendance)
{
    
     
    // تحقق من صلاحية المجموعة قبل الحذف
    if (!auth()->user()->groups->contains($teacherAttendance->group_id)) {
        abort(403, 'ليس لديك صلاحية لحذف هذه الوثيقة.');
    }
    
    
$deletedData = [
        'التاريخ' => $teacherAttendance->attendance_date,
        'المجموعة' => $teacherAttendance->group->name ?? 'غير محدد',
        'تفاصيل المدرسين' => $teacherAttendance->teachers->map(function ($t) {
            return [
                'اسم المدرس' => $t->name,
                'وقت الحضور' => $t->pivot->arrival_time,
                'وقت الانصراف' => $t->pivot->departure_time
            ];
        })->toArray()
    ];

    $teacherAttendance->delete();

    // سجل العمليات
    activity()
        ->causedBy(auth()->user())
        ->withProperties($deletedData)
        ->log('حذف وثيقة تحضير المدرسين');

    return redirect()->route('teacher_attendance.index');
}




public function reportForm(): \Inertia\Response
{
    
    $userGroups = auth()->user()->groups->pluck('id');
    $groups = Group::select('id', 'name')->whereIn('id', $userGroups)->get();

    return Inertia::render('teacher_attendance/report-page', [
        'groups' => $groups,
        'dates' => [],
        'attendanceData' => [],
        'from_date' => null,
        'to_date' => null,
        'group' => null,
    ]);
}


public function generateReport(Request $request): \Inertia\Response
{
    $validated = $request->validate([
        'from_date' => 'required|date',
        'to_date' => 'required|date|after_or_equal:from_date',
    ]);

    $groups = Group::with(['teachers', 'teacherAttendances' => function ($query) use ($request) {
        $query->whereBetween('attendance_date', [
            Carbon::parse($request->from_date)->startOfDay(),
            Carbon::parse($request->to_date)->endOfDay()
        ]);
    }, 'teacherAttendances.teachers'])->whereIn('id', auth()->user()->groups->pluck('id')) //  <-- أضف هذا السطر هنا
        ->get();
        
    $datesWithData = $groups->flatMap(function ($group) {
        return $group->teacherAttendances->pluck('attendance_date');
    })->unique()->sort()->values()->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'));

    $attendanceData = [];
    
    $defaultRate = SalarySetting::first()?->default_hourly_rate ?? 0;

    foreach ($groups as $group) {
        foreach ($group->teachers as $teacher) {
            $teacherData = [
                'name' => $teacher->name,
                'group' => $group->name,
            'hourly_rate' => $teacher->hourly_rate, // 👈 أضف هذا
            'records' => [],
            'total_minutes' => 0,
            'total_payment' => 0, // 👈
            ];

            foreach ($datesWithData as $date) {
                $attendance = $group->teacherAttendances->where('attendance_date', $date)->first();

                $record = "—";

                if ($attendance) {
                    $pivot = $attendance->teachers->find($teacher->id)?->pivot;
                if ($pivot && $pivot->arrival_time && $pivot->departure_time) {
                    $start = Carbon::parse($pivot->departure_time);
                    $end = Carbon::parse($pivot->arrival_time);
                    $diffMinutes = abs($end->diffInMinutes($start));

                    $teacherData['total_minutes'] += $diffMinutes;
                    $record = sprintf('%d:%02d', floor($diffMinutes / 60), $diffMinutes % 60);
                    



                    // 👇 حساب الأجرة
$rate = $teacher->hourly_rate ?? $defaultRate;
if ($rate) {
    $hours = $diffMinutes / 60;
    $teacherData['total_payment'] += round($hours * $rate, 2);
}


                }
                }

                $teacherData['records'][$date] = $record;
            }

            $attendanceData[] = $teacherData;
        }
    }

    return Inertia::render('teacher_attendance/report-page', [
        'dates' => $datesWithData,
        'attendanceData' => $attendanceData,
        'from_date' => $request->from_date,
        'to_date' => $request->to_date,
        'groups' => [], // لم نعد بحاجة للقائمة
    ]);
}
}

