<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LevelState extends Model
{
    use HasFactory;


    protected $fillable = ['name'];


    public function people()
    {
        return $this->hasMany(Person::class,        'level_state_id');
    }
}
