<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
        use HasFactory, SoftDeletes;
        
    protected $fillable = [
        'name', 'birth_date', 'address', 'phone', 'notes', 'center_id', 'level_id'
    ];

    public function center() {
        return $this->belongsTo(Center::class);
    }

    public function level() {
        return $this->belongsTo(Level::class);
    }
    
    public function attendances()
{
    return $this->belongsToMany(Attendance::class, 'attendance_student')
                ->withPivot('status')
                ->withTimestamps();
}
}

