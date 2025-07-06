<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GroupController extends Controller
{
    public function index()
    {
        return Inertia::render('groups/index', [
            'groups' => Group::all(),
        ]);
    }

    // لم نعد بحاجة لـ create/edit لأن المودالات أصبحت ضمن صفحة index

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:groups,name',
        ]);

        $group = Group::create($request->only('name'));

        activity()
            ->causedBy(auth()->user())
            ->performedOn($group)
            ->withProperties(['الاسم' => $group->name])
            ->log('إضافة مجموعة جديد');

        return redirect()->route('groups.index')->with('success', 'تم إضافة المجموعة بنجاح.');
    }

    public function update(Request $request, Group $group)
    {
        $request->validate([
            'name' => 'required|string|unique:groups,name,' . $group->id,
        ]);

        $oldName = $group->name;
        $group->update($request->only('name'));

        activity()
            ->causedBy(auth()->user())
            ->performedOn($group)
            ->withProperties([
                'الاسم قبل التعديل' => $oldName,
                'الاسم بعد التعديل' => $group->name,
            ])
            ->log('تعديل اسم مجموعة');

        return redirect()->route('groups.index')->with('success', 'تم تعديل المجموعة بنجاح.');
    }

    public function destroy(Group $group)
    {
        $groupData = $group->toArray();

        $group->delete();

        activity()
            ->causedBy(auth()->user())
            ->withProperties(['بيانات المجموعة قبل الحذف' => $groupData])
            ->log('حذف مجموعة');

        return redirect()->route('groups.index');
    }
    
public function restore($id)
{
    $group = Group::withTrashed()->findOrFail($id);

    

    $group->restore();

    activity()
        ->causedBy(auth()->user())
        ->performedOn($group)
        ->log('استرجاع المجموعة');

    return redirect()->route('groups.index')->with('success', 'تم استرجاع المجموعة بنجاح.');
}

}
