<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name'];

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function people()
    {
        return $this->hasMany(Person::class, 'location_id');
    }
 
    
    public function users()
{
    return $this->belongsToMany(User::class, 'location_user');
}
}
