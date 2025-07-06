<?php

namespace App\Http\Controllers;

use App\Models\Center;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CenterController extends Controller
{
    public function index()
    {
        return Inertia::render('centers/index', [
            'centers' => Center::all(),
        ]);
    }

    // لم نعد بحاجة لـ create/edit لأن المودالات أصبحت ضمن صفحة index

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:centers,name',
        ]);

        $center = Center::create($request->only('name'));

        activity()
            ->causedBy(auth()->user())
            ->performedOn($center)
            ->withProperties(['الاسم' => $center->name])
            ->log('إضافة مركز جديد');

        return redirect()->route('centers.index')->with('success', 'تم إضافة المركز بنجاح.');
    }

    public function update(Request $request, Center $center)
    {
        $request->validate([
            'name' => 'required|string|unique:centers,name,' . $center->id,
        ]);

        $oldName = $center->name;
        $center->update($request->only('name'));

        activity()
            ->causedBy(auth()->user())
            ->performedOn($center)
            ->withProperties([
                'الاسم قبل التعديل' => $oldName,
                'الاسم بعد التعديل' => $center->name,
            ])
            ->log('تعديل اسم مركز');

        return redirect()->route('centers.index')->with('success', 'تم تعديل المركز بنجاح.');
    }

    public function destroy(Center $center)
    {
        $centerData = $center->toArray();

        $center->delete();

        activity()
            ->causedBy(auth()->user())
            ->withProperties(['بيانات المركز قبل الحذف' => $centerData])
            ->log('حذف مركز');

        return redirect()->route('centers.index');
    }
    
public function restore($id)
{
    $center = Center::withTrashed()->findOrFail($id);

    

    $center->restore();

    activity()
        ->causedBy(auth()->user())
        ->performedOn($center)
        ->log('استرجاع مركز');

    return redirect()->route('centers.index')->with('success', 'تم استرجاع المركز بنجاح.');
}

}