<?php

namespace App\Http\Controllers\Auth;

use DB;
use Auth;
use Session;
use Carbon\Carbon;
use Brian2694\Toastr\Facades\Toastr;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException; 


class LoginController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * عرض صفحة تسجيل الدخول (Inertia).
     */
    public function login()
    {
        return Inertia::render('auth/login');
    }

    /**
     * التحقق من بيانات الدخول.
     */
    public function authenticate(Request $request)
    {
        $request->validate([
            'name'     => 'required|string',
            'password' => 'required|string',
        ]);

        try {
            $username = $request->name;
            $password = $request->password;

            $dt         = Carbon::now();
            $todayDate  = $dt->toDayDateTimeString();
            
            if (Auth::attempt(['name'=> $username,'password'=> $password])) {
                /** get session */
                $user = Auth::User();
                Session::put('name', $user->name);
                Session::put('email', $user->email);
                Session::put('user_id', $user->user_id);
                Session::put('join_date', $user->join_date);
                Session::put('last_login', $user->join_date);
                Session::put('phone_number', $user->phone_number);
                Session::put('status', $user->status);
                Session::put('role_name', $user->role_name);
                Session::put('avatar', $user->avatar);
                Session::put('position', $user->position);
                Session::put('department', $user->department);

                $updateLastLogin = ['last_login' => $todayDate,];
                User::where('name',$username)->update($updateLastLogin);
                         return redirect()->intended('dashboard')->with('success', 'تم تسجيل الدخول بنجاح!');
            } else {
                return redirect()->back()->with('error', 'بيانات الدخول غير صحيحة');
            }
        } catch (QueryException $e) {
            // يتم التقاط هذا الاستثناء إذا كان هناك خطأ في الاتصال بقاعدة البيانات
            \Log::error('Database connection error: ' . $e->getMessage()); // استخدام Log::error لتسجيل أخطاء DB

            return redirect()->back()->with('error', 'فشل الاتصال بقاعدة البيانات. الرجاء المحاولة لاحقاً.');
        } catch (\Exception $e) {
            // يتم التقاط أي استثناءات أخرى غير QueryException
            \Log::error('Login error: ' . $e->getMessage()); // استخدام Log::error لتسجيل الأخطاء العامة
            DB::rollback(); // تأكد من أنك بدأت معاملة إذا كنت تستخدم rollback

            return redirect()->back()->with('error', 'فشل تسجيل الدخول. الرجاء المحاولة مرة أخرى.');
        }
    }
    /**
     * تسجيل الخروج من الجلسة.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->flush();

        return redirect()->route('login')->with('success', 'تم تسجيل الخروج بنجاح!');
    }
}