<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalarySetting extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'default_hourly_rate',
            ];
}
