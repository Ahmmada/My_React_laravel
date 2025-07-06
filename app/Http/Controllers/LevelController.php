<?php

namespace App\Http\Controllers;

use App\Models\Level;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LevelController extends Controller
{
    public function index()
    {
        return Inertia::render('levels/index', [
            'levels' => Level::all(),
        ]);
    }

    // لم نعد بحاجة لـ create/edit لأن المودالات أصبحت ضمن صفحة index

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:levels,name',
        ]);

        $level = Level::create($request->only('name'));

        activity()
            ->causedBy(auth()->user())
            ->performedOn($level)
            ->withProperties(['الاسم' => $level->name])
            ->log('إضافة مستوى جديد');

        return redirect()->route('levels.index')->with('success', 'تم إضافة المستوى بنجاح.');
    }

    public function update(Request $request, Level $level)
    {
        $request->validate([
            'name' => 'required|string|unique:levels,name,' . $level->id,
        ]);

        $oldName = $level->name;
        $level->update($request->only('name'));

        activity()
            ->causedBy(auth()->user())
            ->performedOn($level)
            ->withProperties([
                'الاسم قبل التعديل' => $oldName,
                'الاسم بعد التعديل' => $level->name,
            ])
            ->log('تعديل اسم مستوى');

        return redirect()->route('levels.index')->with('success', 'تم تعديل المستوى بنجاح.');
    }

    public function destroy(Level $level)
    {
        $levelData = $level->toArray();

        $level->delete();

        activity()
            ->causedBy(auth()->user())
            ->withProperties(['بيانات المستوى قبل الحذف' => $levelData])
            ->log('حذف مستوى');

        return redirect()->route('levels.index');
    }
    
public function restore($id)
{
    $level = Level::withTrashed()->findOrFail($id);

    

    $level->restore();

    activity()
        ->causedBy(auth()->user())
        ->performedOn($level)
        ->log('استرجاع المستوى');

    return redirect()->route('levels.index')->with('success', 'تم استرجاع المستوى بنجاح.');
}

}


