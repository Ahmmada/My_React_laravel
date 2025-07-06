<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name'];

    public function teachers()
    {
        return $this->hasMany(Teacher::class);
    }
 
 public function teacherAttendances()
{
    return $this->hasMany(TeacherAttendance::class);
}
 
public function users()
{
    return $this->belongsToMany(User::class, 'group_user');
}

    
    
}

