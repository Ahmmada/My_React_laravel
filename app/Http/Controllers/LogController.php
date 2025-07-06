<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Inertia\Inertia;


class LogController extends Controller
{
    
            function __construct()
    {
         $this->middleware('permission:سجل العمليات', ['only' => ['activityLogs']]);
         $this->middleware('permission:حذف سجل عمليات', ['only' => ['deleteActivityLog']]);
         $this->middleware('permission:حذف سجلات العمليات', ['only' => ['clearActivityLogs']]);
    } 

public function activityLogs()
{
    $logs = Activity::with('causer')->orderBy('id', 'desc')->get()->map(function ($log) {
        return [
            'id' => $log->id,
            'description' => $log->description,
            'causer' => $log->causer ? ['name' => $log->causer->name] : null,
            'created_at' => $log->created_at->format('Y-m-d H:i'),
            'properties' => $log->properties,
        ];
    });

    return Inertia::render('activity_logs/index', [
        'logs' => $logs,
    ]);
}

public function deleteActivityLog($id)
{
    $log = Activity::findOrFail($id);
    $log->delete();

    return redirect()->back();
    
}



public function clearActivityLogs()
{
    Activity::truncate();  // يحذف كل السجلات مرة وحدة (إعادة تصفير الجدول)
    return redirect()->back()->with('success', 'تم حذف جميع سجلات العمليات بنجاح!');
}
}

