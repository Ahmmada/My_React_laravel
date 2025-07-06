<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_male' => $this->is_male,
            'is_beneficiary' => $this->is_beneficiary,
            'birth_date' => $this->birth_date,
            'card_type' => $this->cardType?->name,
            'card_number' => $this->card_number,
            'phone_number' => $this->phone_number,
            'job' => $this->job,
            'housing_type' => $this->housingType?->name,
            'housing_address' => $this->housing_address,
            'center' => $this->center?->name,
            'social_state' => $this->socialState?->name,
            'level_state' => $this->levelState?->name,
            'meal_count' => $this->meal_count,
            'male_count' => $this->male_count,
            'female_count' => $this->female_count,
            'family_members_count' => $this->family_members_count,
            'notes' => $this->notes,
        ];
    }
}