<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LocationController extends Controller
{
    public function index()
    {
        return Inertia::render('locations/index', [
            'locations' => Location::all(),
        ]);
    }

    // لم نعد بحاجة لـ create/edit لأن المودالات أصبحت ضمن صفحة index

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:locations,name',
        ]);

        $location = Location::create($request->only('name'));

        activity()
            ->causedBy(auth()->user())
            ->performedOn($location)
            ->withProperties(['الاسم' => $location->name])
            ->log('إضافة مركز جديد');

        return redirect()->route('locations.index')->with('success', 'تم إضافة المركز بنجاح.');
    }

    public function update(Request $request, Location $location)
    {
        $request->validate([
            'name' => 'required|string|unique:locations,name,' . $location->id,
        ]);

        $oldName = $location->name;
        $location->update($request->only('name'));

        activity()
            ->causedBy(auth()->user())
            ->performedOn($location)
            ->withProperties([
                'الاسم قبل التعديل' => $oldName,
                'الاسم بعد التعديل' => $location->name,
            ])
            ->log('تعديل اسم مركز');

        return redirect()->route('locations.index')->with('success', 'تم تعديل المركز بنجاح.');
    }

    public function destroy(Location $location)
    {
        $locationData = $location->toArray();

        $location->delete();

        activity()
            ->causedBy(auth()->user())
            ->withProperties(['بيانات المركز قبل الحذف' => $locationData])
            ->log('حذف مركز');

        return redirect()->route('locations.index');
    }
    
public function restore($id)
{
    $location = Location::withTrashed()->findOrFail($id);

    

    $location->restore();

    activity()
        ->causedBy(auth()->user())
        ->performedOn($location)
        ->log('استرجاع مركز');

    return redirect()->route('locations.index')->with('success', 'تم استرجاع المركز بنجاح.');
}

}