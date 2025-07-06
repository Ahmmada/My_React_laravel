<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;



class TeacherAttendance extends Model
{
    use HasFactory;

    protected $fillable = ['attendance_date', 'group_id'];
    
    protected $dates = ['attendance_date']; // <-- هذا السطر المهم

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'teacher_attendance_teacher')
                    ->withPivot('arrival_time', 'departure_time')
                    ->withTimestamps();
    }
}