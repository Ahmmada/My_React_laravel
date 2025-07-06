<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    use HasFactory;

   
    protected $fillable = [
        'name',
        'is_male',
        'is_beneficiary',
        'birth_date',
        'card_type_id',
        'card_number',
        'phone_number',
        'job',
        'housing_type_id',
        'housing_address',
        'location_id',
        'social_state_id',
        'level_state_id',
        'meal_count',
        'male_count',
        'female_count',
        'notes',
    ];


    public function cardType()
    {
        return $this->belongsTo(CardType::class, 'card_type_id');
    }


    public function housingType()
    {
        return $this->belongsTo(HousingType::class, 'housing_type_id');
    }


    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    public function socialState()
    {
        return $this->belongsTo(SocialState::class, 'social_state_id');
    }

    public function levelState()
    {
        return $this->belongsTo(LevelState::class, 'level_state_id');
    }

    public function familyMembers()
    {
        return $this->hasMany(FamilyMember::class, 'person_id');
    }
    
    public static function getRelationTableName($relationName)
    {
        $relation = (new static)->$relationName();
        return $relation->getRelated()->getTable();
    }
    
}
