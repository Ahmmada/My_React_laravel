<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\Group;
use App\Models\SalarySetting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;


class TeacherController extends Controller
{
    
public function index()
{
    $teachers = Teacher::with('group')->whereIn('group_id', auth()->user()->groups->pluck('id'))->get();

    // جلب أجر الساعة الافتراضي من جدول salary_settings
    $defaultHourlyRate = SalarySetting::first()?->default_hourly_rate ?? 0;

    return Inertia::render('teachers/index', [
        'teachers' => fn () => $teachers,
        'defaultHourlyRate' => fn () => $defaultHourlyRate,
    ]);
}


public function create(): Response
{

    $userGroups = auth()->user()->groups->pluck('id');
    $groups = Group::select('id', 'name')->whereIn('id', $userGroups)->get();

    return Inertia::render('teachers/create', [
        'groups' => $groups,
    ]);
}




    public function store(Request $request)
    {
        
        $request->validate([
            'name' => 'required|string',
            'group_id' => 'nullable|exists:groups,id',
            'position' => 'nullable|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'hourly_rate' => 'nullable|numeric',
        ]);


        if (!auth()->user()->groups->contains($request->group_id)) {
            return redirect()->back()->withErrors(['msg' => 'ليس لديك صلاحية الإضافة لهذه المجموعة.'])->withInput();
        }
        
        $teacher = Teacher::create($request->all());

        // سجل العمليات
        activity()
            ->causedBy(auth()->user())
            ->performedOn($teacher)
            ->withProperties(['البيانات' => $request->all()])
            ->log('إضافة مدرس جديد');

        return redirect()->route('teachers.index')->with('success', 'تم إضافة المدرس بنجاح.');
    }

public function edit(Teacher $teacher): Response
{
    if (!auth()->user()->groups->contains($teacher->group_id)) {
        abort(403, 'ليس لديك صلاحية .');
    }
    $userGroups = auth()->user()->groups->pluck('id');
    $groups = Group::select('id', 'name')->whereIn('id', $userGroups)->get();

    return Inertia::render('teachers/edit', [
        'teacher' => $teacher,
        'groups' => $groups,
    ]);
}

    public function update(Request $request, Teacher $teacher)
    {
        $request->validate([
            'name' => 'required|string',
            'group_id' => 'nullable|exists:groups,id',
            'position' => 'nullable|string',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'hourly_rate' => 'nullable|numeric',
        ]);
        
        if (!auth()->user()->groups->contains($teacher->group_id)) {
        abort(403, 'ليس لديك صلاحية .');
    }

        $oldData = $teacher->toArray();  // بيانات قبل التعديل

        $teacher->update($request->all());

        // سجل العمليات
        activity()
            ->causedBy(auth()->user())
            ->performedOn($teacher)
            ->withProperties([
                'قبل التعديل' => $oldData,
                'بعد التعديل' => $request->all()
            ])
            ->log('تعديل بيانات مدرس');

        return redirect()->route('teachers.index')->with('success', 'تم تعديل بيانات المدرس بنجاح.');
    }

    public function destroy(Teacher $teacher)
    {
        
        if (!auth()->user()->groups->contains($teacher->group_id)) {
        abort(403, 'ليس لديك صلاحية .');
    }
    
        
        $teacherData = $teacher->toArray();  // بيانات قبل الحذف

        $teacher->delete();

        // سجل العمليات
        activity()
            ->causedBy(auth()->user())
            ->withProperties(['بيانات المدرس قبل الحذف' => $teacherData])
            ->log('حذف مدرس');

        return redirect()->route('teachers.index')->with('success', 'تم حذف المدرس بنجاح.');
    }
}