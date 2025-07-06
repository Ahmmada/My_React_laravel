<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = ['attendance_date', 'center_id', 'level_id'];

    public function students()
    {
        return $this->belongsToMany(Student::class, 'attendance_student')
                    ->withPivot('status')
                    ->withTimestamps();
    }

    public function center() {
        return $this->belongsTo(Center::class);
    }

    public function level() {
        return $this->belongsTo(Level::class);
    }
}