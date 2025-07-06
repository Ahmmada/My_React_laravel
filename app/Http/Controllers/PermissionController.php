<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;



class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:عرض الأذونات|إنشاء إذن|تعديل إذن|حذف إذن', ['only' => ['index', 'show']]);
        $this->middleware('permission:إنشاء إذن', ['only' => ['create', 'store']]);
        $this->middleware('permission:تعديل إذن', ['only' => ['edit', 'update']]);
        $this->middleware('permission:حذف إذن', ['only' => ['destroy']]);
    }

    public function index(Request $request): Response
    {
        $permissions = Permission::orderBy('id', 'DESC')->paginate(100);

        return Inertia::render('permissions/index', [
            'permissions' => $permissions,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('permissions/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|unique:permissions,name',
        ]);

        $permission = Permission::create(['name' => $request->input('name')]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($permission)
            ->withProperties(['name' => $permission->name])
            ->log('إضافة إذن جديد');

        return redirect()->route('permissions.index')->with('success', 'تم إنشاء الإذن بنجاح.');
    }

    public function show($id): Response
    {
        $permission = Permission::findOrFail($id);

        return Inertia::render('permissions/show', [
            'permission' => $permission,
        ]);
    }

    public function edit($id): Response
    {
        $permission = Permission::findOrFail($id);

        return Inertia::render('permissions/edit', [
            'permission' => $permission,
        ]);
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'name' => 'required',
        ]);

        $permission = Permission::findOrFail($id);

        $oldName = $permission->name;
        $permission->name = $request->input('name');
        $permission->save();

        activity()
            ->causedBy(auth()->user())
            ->performedOn($permission)
            ->withProperties([
                'قبل التعديل' => $oldName,
                'بعد التعديل' => $permission->name,
            ])
            ->log('تعديل إذن');

        return redirect()->route('permissions.index')->with('success', 'تم تحديث الإذن بنجاح.');
    }

    public function destroy($id): RedirectResponse
    {
        $permission = Permission::findOrFail($id);
        $name = $permission->name;

        $permission->delete();

        activity()
            ->causedBy(auth()->user())
            ->withProperties(['اسم الإذن' => $name])
            ->log('حذف إذن');

        return redirect()->route('permissions.index');
    }
}