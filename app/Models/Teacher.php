<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'group_id',
        'position',
        'phone',
        'address',
        'birth_date',
        'notes',
        'hourly_rate',
    ];
    
    
    
    public function group()
{
    return $this->belongsTo(Group::class);
}
    
    public function attendances()
{
    return $this->belongsToMany(\App\Models\TeacherAttendance::class, 'teacher_attendance_teacher')
                ->withPivot('arrival_time', 'departure_time')
                ->withTimestamps();
}
}