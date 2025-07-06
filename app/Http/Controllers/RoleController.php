<?php
    
namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;

use Spatie\Permission\Models\Permission;
use DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
 use Inertia\Inertia;
use Inertia\Response;


    
class RoleController extends Controller
{


        function __construct()
    {
         $this->middleware('permission:ادوار المستخدمين|اضافة دور|تعديل الدور|حذف الدور', ['only' => ['index','show']]);
         $this->middleware('permission:اضافة دور', ['only' => ['create','store']]);
         $this->middleware('permission:تعديل الدور', ['only' => ['edit','update']]);
         $this->middleware('permission:حذف الدور', ['only' => ['destroy']]);
    }


public function index(Request $request)
{
    $roles = Role::orderBy('id', 'DESC')->paginate(100);

    return Inertia::render('roles/index', [
        'roles' => $roles,
    ]);
}


    public function create(): Response
    {
        $permissions = Permission::select('id', 'name')->get();

        return Inertia::render('roles/create', [
            'permissions' => $permissions,
        ]);
    }


    

public function store(Request $request): RedirectResponse
{
    $this->validate($request, [
        'name' => 'required|unique:roles,name',
        'permission' => 'required',
    ]);

    $permissionsID = array_map(
        fn($value) => (int) $value,
        $request->input('permission')
    );

    $role = Role::create(['name' => $request->input('name')]);
    $role->syncPermissions($permissionsID);

    activity()
        ->causedBy(auth()->user())
        ->performedOn($role)
        ->withProperties([
            'name' => $role->name,
            'permissions' => Permission::whereIn('id', $permissionsID)->pluck('name'),
        ])
        ->log('إضافة دور جديد');

    return redirect()->route('roles.index')->with('success', 'تم إنشاء الدور بنجاح.');
}


public function show($id): Response
{
    $role = Role::findOrFail($id);
    $rolePermissions = Permission::join("role_has_permissions", "role_has_permissions.permission_id", "=", "permissions.id")
        ->where("role_has_permissions.role_id", $id)
        ->select('permissions.id', 'permissions.name')
        ->get();

    return Inertia::render('roles/show', [
        'role' => $role,
        'rolePermissions' => $rolePermissions,
    ]);
}

   public function edit($id): Response
    {
        $role = Role::select('id', 'name')->findOrFail($id);
        $permissions = Permission::select('id', 'name')->get();
        $rolePermissions = DB::table('role_has_permissions')
            ->where('role_id', $id)
            ->pluck('permission_id')
            ->toArray();

        return Inertia::render('roles/edit', [
            'role' => $role,
            'permissions' => $permissions,
            'rolePermissions' => $rolePermissions,
        ]);
    }


public function update(Request $request, $id): RedirectResponse
{
    $this->validate($request, [
        'name' => 'required',
        'permission' => 'required',
    ]);

    $role = Role::findOrFail($id);
    $oldData = [
        'name' => $role->name,
        'permissions' => $role->permissions->pluck('name'),
    ];

    $role->name = $request->input('name');
    $role->save();

    $permissionsID = array_map(fn($v) => (int) $v, $request->input('permission'));
    $role->syncPermissions($permissionsID);

    activity()
        ->causedBy(auth()->user())
        ->performedOn($role)
        ->withProperties([
            'قبل التعديل' => $oldData,
            'بعد التعديل' => [
                'name' => $role->name,
                'permissions' => Permission::whereIn('id', $permissionsID)->pluck('name'),
            ]
        ])
        ->log('تعديل دور');
    
    return redirect()->route('roles.index')->with('success', 'تم تحديث الدور بنجاح.');
}

public function destroy($id): RedirectResponse
{
    $role = Role::findOrFail($id);
    $roleData = [
        'name' => $role->name,
        'permissions' => $role->permissions->pluck('name'),
    ];

    $role->delete();

    activity()
        ->causedBy(auth()->user())
        ->withProperties(['بيانات الدور قبل الحذف' => $roleData])
        ->log("حذف دور");

    return redirect()->route('roles.index');
}

}


