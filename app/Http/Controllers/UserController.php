<?php
    
namespace App\Http\Controllers;
    
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Center;
use App\Models\Group;
use App\Models\Location;
use Spatie\Permission\Models\Role;
use DB;
use Hash;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia; 
use Inertia\Response;


class UserController extends Controller
{
    
    
    
        function __construct()
    {
         $this->middleware('permission:شاشة المستخدمين|اضافة مستخدم|تعديل المستخدم|حذف المستخدم', ['only' => ['index','show']]);
         $this->middleware('permission:اضافة مستخدم', ['only' => ['create','store']]);
         $this->middleware('permission:تعديل المستخدم', ['only' => ['edit','update']]);
         $this->middleware('permission:حذف المستخدم', ['only' => ['destroy']]);
    }  



    public function index(Request $request)
    {
        return Inertia::render('users/index', [
            'users' => User::with('roles')->where('id', '!=', 1)->latest()->get()
        ]);
    }

    public function create()
    {
        return Inertia::render('users/create', [
            'roles' => Role::pluck('name', 'name'),
            'centers' => Center::all(),
            'groups' => Group::all(),
            'locations' => Location::all(),
        ]);
   
}


public function store(Request $request): RedirectResponse
{
    $this->validate($request, [
        'name' => 'required|unique:users,name',
        'password' => 'required|same:confirm-password',
        'roles' => 'required',
        'centers' => 'array',
        'groups' => 'array',
        'locations' => 'array',
    ]);

    $input = $request->all();
    $input['password'] = Hash::make($input['password']);

    $user = User::create($input);

    // تعيين الدور
    $user->assignRole($request->input('roles'));

    // تعيين المراكز
    $user->centers()->sync($request->centers);

    // تعيين المجموعات
    $user->groups()->sync($request->groups);

    // تعيين الحارات المستهدفة
    $user->locations()->sync($request->locations);

    // سجل النشاط
    activity()
        ->causedBy(auth()->user())
        ->performedOn($user)
        ->withProperties([
            'البيانات' => $request->except(['confirm-password'])
        ])
        ->log('إضافة مستخدم جديد');

    return redirect()->route('users.index')->with('success','تم إضافة المستخدم بنجاح.');
}



    public function edit($id)
    {
        $user = User::with('roles', 'centers', 'groups', 'locations', )->findOrFail($id);

        return Inertia::render('users/edit', [
            'user' => $user,
            'roles' => Role::pluck('name', 'name'),
            'userRole' => $user->roles->pluck('name'),
            'centers' => Center::all(),
            'userCenters' => $user->centers->pluck('id'),
            'groups' => Group::all(),
            'userGroups' => $user->groups->pluck('id'),
            'locations' => Location::all(),
            'userLocations' => $user->locations->pluck('id'),
        ]);
    }


public function update(Request $request, $id): RedirectResponse
{
    $this->validate($request, [
        'name' => 'required|unique:users,name,'.$id,
        'password' => 'same:confirm-password',
        'roles' => 'required',
        'centers' => 'array',
        'groups' => 'array',
        'locations' => 'array',
    ]);

    $input = $request->all();
    $user = User::findOrFail($id);

    $oldData = $user->toArray();  // حفظ البيانات قبل التعديل

    if(!empty($input['password'])) {
        $input['password'] = Hash::make($input['password']);
    } else {
        $input = Arr::except($input, ['password']);
    }

    $user->update($input);

    // تحديث الأدوار
    DB::table('model_has_roles')->where('model_id', $id)->delete();
    $user->assignRole($request->input('roles'));

    // تحديث المراكز
    $user->centers()->sync($request->centers);
 
    // تحديث المجموعات
    $user->groups()->sync($request->groups);

    // تحديث الحارات المستهدفة
    $user->locations()->sync($request->locations);

    // سجل النشاط
    activity()
        ->causedBy(auth()->user())
        ->performedOn($user)
        ->withProperties([
            'قبل التعديل' => Arr::except($oldData, ['password']),     
            'بعد التعديل' => $request->except([ 'password','confirm-password'])
        ])
        ->log('تعديل بيانات مستخدم');

    return redirect()->route('users.index')->with('success','تم تعديل المستخدم بنجاح.');
}


 public function show($id)
    {
        $user = User::with('roles', 'centers')->findOrFail($id);

        return Inertia::render('users/show', [
            'user' => $user
        ]);
    }
    
    public function destroy($id): RedirectResponse
{
    $user = User::findOrFail($id);
    $userData = $user->toArray();  // بيانات المستخدم قبل الحذف

    $user->delete();

    activity()
        ->causedBy(auth()->user())
        ->withProperties([
            'بيانات المستخدم قبل الحذف' => Arr::except($userData, ['password'])
        ])
        ->log("حذف المستخدم: {$userData['name']}");

    return redirect()->route('users.index');
}
    
    
 
 
    public function editPassword(): Response
    {
        return Inertia::render('users/edit-password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $messages = [
            'current_password.required' => 'حقل كلمة المرور الحالية مطلوب.',
            'new_password.required' => 'حقل كلمة المرور الجديدة مطلوب.',
            'new_password.min' => 'يجب أن تتكون كلمة المرور الجديدة من 3 أحرف على الأقل.',
            'new_password.confirmed' => 'تأكيد كلمة المرور الجديدة لا يتطابق.',
        ];

        $request->validate([
            'current_password' => ['required'],
            'new_password' => ['required', 'min:3', 'confirmed'],
        ], $messages); // تمرير رسائل الأخطاء المخصصة هنا

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'كلمة المرور الحالية غير صحيحة!']);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        // 📝 تسجيل النشاط
        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties([
                'عملية' => 'تغيير كلمة المرور',
                'ملاحظة' => 'تم تغيير كلمة المرور بنجاح لهذا المستخدم.',
            ])
            ->log('تم تغيير كلمة المرور');

        // ✅ إعادة التوجيه باستخدام flash message
        return redirect()->route('profile.password.edit')->with('success', 'تم تغيير كلمة المرور بنجاح!');
    }
}


