<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Center;
use App\Models\Level;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StudentController extends Controller
{
    

public function index()
{
    $students = Student::with(['center', 'level'])
        ->whereIn('center_id', auth()->user()->centers->pluck('id'))
        ->get();

    return Inertia::render('students/index', [
        'students' => $students,
    ]);
}







public function create()
{
    $centers = auth()->user()->centers;
    $levels = Level::all();

    return Inertia::render('students/create', [
        'centers' => $centers,
        'levels' => $levels,
    ]);
}
   

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'center_id' => 'required|exists:centers,id',
            'level_id' => 'required|exists:levels,id',
            'notes' => 'nullable|string'
        ]);

        // التأكد أن المستخدم مخول لإضافة طالب لهذا المركز
        if (!auth()->user()->centers->contains($request->center_id)) {
            return redirect()->back()->withErrors(['msg' => 'ليس لديك صلاحية لإضافة طالب لهذا المركز.'])->withInput();
        }

        Student::create($request->all());

activity()
    ->causedBy(auth()->user())
    ->performedOn(new Student)
    ->withProperties(['data' => $request->all()])
    ->log('إضافة طالب جديد');

        return redirect()->route('students.index')->with('success', 'تم تسجيل الطالب بنجاح.');
    }


public function edit(Student $student)
{
    if (!auth()->user()->centers->contains($student->center_id)) {
        abort(403, 'ليس لديك صلاحية لتعديل بيانات هذا الطالب.');
    }

    $centers = auth()->user()->centers;
    $levels = Level::all();

    return Inertia::render('students/edit', [
        'student' => $student,
        'centers' => $centers,
        'levels' => $levels,
    ]);
}

    public function update(Request $request, Student $student)
    {
        // حماية التحديث حسب صلاحية المركز
        if (!auth()->user()->centers->contains($student->center_id)) {
            abort(403, 'ليس لديك صلاحية لتعديل بيانات هذا الطالب.');
        }

        $request->validate([
            'name' => 'required|string',
            'birth_date' => 'nullable|date',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'center_id' => 'required|exists:centers,id',
            'level_id' => 'required|exists:levels,id',
            'notes' => 'nullable|string'
        ]);
        
        $oldData = $student->getOriginal();  

        $student->update($request->all());

activity()
    ->causedBy(auth()->user())
    ->performedOn($student)
    ->withProperties([
        'قبل التعديل' => $oldData,
        'بعد التعديل' => $request->all()
    ])
    ->log('تعديل بيانات طالب');
    
        return redirect()->route('students.index')->with('success', 'تم تحديث بيانات الطالب بنجاح.');
    }
    
public function destroy(Student $student)
{
    if (!auth()->user()->centers->contains($student->center_id)) {
        abort(403, 'ليس لديك صلاحية لحذف هذا الطالب.');
    }

    $studentData = $student->toArray();
    $student->delete();

    activity()
        ->causedBy(auth()->user())
        ->withProperties([
            'بيانات الطالب قبل الحذف' => $studentData
        ])
        ->log("حذف الطالب: {$studentData['name']}");

return redirect()->back();
}


public function restore($id)
{
    $student = Student::withTrashed()->findOrFail($id);

    if (!auth()->user()->centers->contains($student->center_id)) {
        abort(403, 'ليس لديك صلاحية لاسترجاع هذا الطالب.');
    }

    $student->restore();

    activity()
        ->causedBy(auth()->user())
        ->performedOn($student)
        ->log('استرجاع طالب بعد الحذف');

    return back()->with('success', 'تم استرجاع الطالب بنجاح.');
}

    
}