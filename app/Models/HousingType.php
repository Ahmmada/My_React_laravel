<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HousingType extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name'];

    /**
     * Get the people that have this housing type.
     */
    public function people()
    {
        return $this->hasMany(Person::class, 'housing_type_id');
    }
}
