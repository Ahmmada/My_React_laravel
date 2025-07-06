<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AttendanceController,
    TeacherAttendanceController,
    LogController,
    SalarySettingController,
    StudentController,
    CenterController,
    LocationController,
    LevelController,
    GroupController,
    RoleController,
    UserController,
    PermissionController,
    TeacherController,
    Auth\LoginController,
    PersonController,
    PersonColumnController,
    PersonReportController,
    ReportExportController,
};
use Inertia\Inertia;
use App\Exports\PeopleReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

// صفحة البداية
Route::get('/', fn() => Inertia::render('Home'))->name('home');

// تسجيل الدخول والخروج
Route::controller(LoginController::class)->group(function () {
    Route::get('/login', 'login')->name('login');
    Route::post('/login', 'authenticate');
    Route::post('/logout', 'logout')->name('logout');
});

// مجموعة المسارات المحمية بالتحقق من المصادقة
Route::middleware(['auth', 'verified'])->group(function () {

    // لوحة التحكم
    Route::get('/dashboard', fn() => Inertia::render('dashboard'))->name('dashboard');

    // الطلاب
    Route::resource('students', StudentController::class);
    Route::post('/students/{student}/restore', [StudentController::class, 'restore'])->name('students.restore');

    // المستخدمين والصلاحيات
    Route::resources([
        'roles' => RoleController::class,
        'users' => UserController::class,
        'permissions' => PermissionController::class,
    ]);

 
// صفحة نموذج البحث للأشخاص
Route::get('/people/search', [\App\Http\Controllers\PersonController::class, 'searchForm'])->name('people.search.form');

// نقطة نهاية لجلب نتائج البحث المفلترة والمصنفة مع ترقيم الصفحات
Route::get('/people/search/results', [\App\Http\Controllers\PersonController::class, 'getSearchResults'])->name('people.search.results');

    

    // مراكز - مستويات - مجموعات
    Route::resources([
        'centers' => CenterController::class,
        'levels' => LevelController::class,
        'groups' => GroupController::class,
        'locations' => LocationController::class,
    ]);
Route::resource('people', PersonController::class)->except(['show']);
Route::get('/people/{person}/show', [PersonController::class, 'show'])->name('people.show');

    Route::post('/centers/{center}/restore', [CenterController::class, 'restore'])->name('centers.restore');
    Route::post('/levels/{level}/restore', [LevelController::class, 'restore'])->name('levels.restore');
    Route::post('/groups/{group}/restore', [GroupController::class, 'restore'])->name('groups.restore');
    Route::post('/locations/{location}/restore', [LocationController::class, 'restore'])->name('locations.restore');

    // إعدادات الرواتب
    Route::put('/salary-settings/update', [SalarySettingController::class, 'update']);

    // المعلمين
    Route::resource('teachers', TeacherController::class);

    // الحضور - الطلاب
    Route::resource('attendances', AttendanceController::class);
    Route::post('/attendances/get-students', [AttendanceController::class, 'getStudents'])->name('attendances.getStudents');
    Route::post('/attendances/check-duplicate', [AttendanceController::class, 'checkDuplicate'])->name('attendances.checkDuplicate');
    Route::get('/attendance-report', [AttendanceController::class, 'reportForm'])->name('attendances.reportForm');
    Route::post('/attendance-report', [AttendanceController::class, 'generateReport'])->name('attendances.generateReport');
    Route::get('/attendance-report/all', [AttendanceController::class, 'reportAllForm'])->name('attendances.reportAllForm');
    Route::post('/attendance-report/all', [AttendanceController::class, 'generateAllReport'])->name('attendances.generateAllReport');

    // الحضور - المعلمين
    Route::resource('teacher_attendance', TeacherAttendanceController::class);
    Route::get('/teacher_attendance/report/form', [TeacherAttendanceController::class, 'reportForm'])->name('teacher_attendance.report.form');
    Route::get('/teacher_report/report', [TeacherAttendanceController::class, 'generateReport'])->name('teacher_attendance.generateReport');
    Route::post('/teacher_attendance/get-teachers', [TeacherAttendanceController::class, 'getTeachers'])->name('teacher_attendance.getTeachers');

    // تعديل كلمة المرور
    Route::get('/profile/password', [UserController::class, 'editPassword'])->name('profile.password.edit');
    Route::post('/profile/password', [UserController::class, 'updatePassword'])->name('profile.password.update');

    // سجلات النشاط
    Route::get('/activity-logs', [LogController::class, 'activityLogs'])->name('activity.logs');
    Route::delete('/activity-logs/clear', [LogController::class, 'clearActivityLogs'])->name('activityLogs.clear');
    Route::delete('/activity-logs/{id}', [LogController::class, 'deleteActivityLog'])->name('activityLogs.delete');
    
  Route::get('/columns', [PersonColumnController::class, 'editColumns'])->name('people.columns.edit');
  
Route::post('/people/import', [\App\Http\Controllers\PersonController::class, 'import'])->name('people.import');


Route::get('/people/report/setup', [PersonReportController::class, 'setup'])->name('people.report.setup');


 // يدعم GET و POST بنفس الميثود
Route::match(['get', 'post'], '/people/report', [PersonReportController::class, 'view'])
    ->name('people.report.view');
 
Route::post('/people/export/excel', function (Request $request) {
    $filtersJson = $request->input('filters', '{}');
    $filters = json_decode($filtersJson, true); // ✅ هنا التحويل

    $columns = $request->input('columns', []);

$filename = 'تقرير_الأشخاص_' . now()->format('Y_m_d_H_i') . '.xlsx';
return Excel::download(new PeopleReportExport($filters, $columns), $filename);
})->name('people.report.export.excel');
 

 
 

Route::post('/people/export/pdf', [ReportExportController::class, 'exportPDF'])
    ->name('people.report.export.pdf');
    
});

