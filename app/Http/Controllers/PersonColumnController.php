<?php 

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\CardType;
use App\Models\Location;
use App\Models\HousingType;
use App\Models\SocialState;
use App\Models\LevelState;
use App\Models\FamilyMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // لاستخدام المعاملات
 use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Validation\Rule;


class PersonColumnController extends Controller
{
    public function editColumns()
    {
        $allColumns = [
            ['field' => 'name', 'label' => 'الاسم'],
            ['field' => 'birth_date', 'label' => 'تاريخ الميلاد'],
            ['field' => 'phone_number', 'label' => 'رقم الهاتف'],
            ['field' => 'job', 'label' => 'المهنة'],
            ['field' => 'card_type.name', 'label' => 'نوع البطاقة'],
            ['field' => 'housing_type.name', 'label' => 'نوع السكن'],
            ['field' => 'location.name', 'label' => 'الحارة'],
            ['field' => 'is_male', 'label' => 'ذكر؟'],
            ['field' => 'is_beneficiary', 'label' => 'مستفيد؟'],
            ['field' => 'card_number', 'label' => 'رقم البطاقة'],
            ['field' => 'housing_address', 'label' => 'عنوان السكن'],
            ['field' => 'social_state.name', 'label' => 'الحالة الاجتماعية'],
            ['field' => 'level_state.name', 'label' => 'مستوى الحالة'],
            ['field' => 'meal_count', 'label' => 'عدد الحالات'],
            ['field' => 'male_count', 'label' => 'عدد الذكور'],
            ['field' => 'female_count', 'label' => 'عدد الإناث'],
            ['field' => 'family_members', 'label' => 'عدد افراد الاسرة'],
            ['field' => 'notes', 'label' => 'ملاحظات'],
        ];

        return Inertia::render('People/SelectColumns', [
            'allColumns' => $allColumns
        ]);
    }
}