<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilyMember extends Model
{
    use HasFactory;


    protected $fillable = [
        'person_id',
        'birth_date',
        'is_male',
    ];


    protected $casts = [
        'is_male' => 'boolean',
    ];


    public function person()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }
}
