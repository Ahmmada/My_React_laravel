<?php

namespace App\Imports;

use App\Models\FamilyMember;
use App\Models\Person;
use App\Models\CardType;
use App\Models\HousingType;
use App\Models\SocialState;
use App\Models\LevelState;
use App\Models\Center;
use Maatwebsite\Excel\Concerns\ToModel;

class PeopleImport implements ToModel
{
    public function model(array $row)
    {
        $cardType = CardType::where('name', $row[4])->first();
        $housingType = HousingType::where('name', $row[8])->first();
        $center = Center::where('name', $row[10])->first();
        $socialState = SocialState::where('name', $row[11])->first();
        $levelState = LevelState::where('name', $row[12])->first();
        // إنشاء الشخص أولاً
        $person = new Person([
            'name' => $row[0],
            'is_male' => $row[1],
            'is_beneficiary' => $row[2],
            'birth_date' => $row[3],
            'card_type_id' => $cardType?->id,
            'card_number' => $row[5],
            'phone_number' => $row[6],
            'job' => $row[7],
            'housing_type_id' => $housingType?->id,
            'housing_address' => $row[9],
            'center_id' => $center?->id,
            'social_state_id' => $socialState?->id,
            'level_state_id' => $levelState?->id,
            'male_count' => $row[13],
            'female_count' => $row[14],
            'meal_count' => $row[15],
            'notes' => $row[16],
        ]);

        $person->save(); // حفظ الشخص للحصول على ID

        // عدد أفراد الأسرة
        $familyCount = (int) $row[17];

        // تبدأ أعمدة أفراد الأسرة من العمود 16 فصاعدًا
        $startIndex = 18;
        for ($i = 0; $i < $familyCount; $i++) {
            $birthDate = $row[$startIndex + ($i * 2)];
            $isMale = $row[$startIndex + ($i * 2) + 1];

            if ($birthDate) {
                FamilyMember::create([
                    'person_id' => $person->id,
                    'birth_date' => $birthDate,
                    'is_male' => $isMale,
                ]);
            }
        }

        return $person;
    }
}