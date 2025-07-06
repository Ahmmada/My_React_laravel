<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\SocialState;
use App\Models\CardType;
use App\Models\HousingType;
use App\Models\LevelState;
use Inertia\Inertia;

use App\Models\Person;
use Illuminate\Http\Request;
use lluminate\Database\Eloquent\Builder;


class PersonReportController extends Controller
{
    

    public function setup()
    {
        $allColumns = [
            ['field' => 'id', 'label' => 'ID'],
            ['field' => 'name', 'label' => 'الاسم'],
            ['field' => 'birth_date', 'label' => 'تاريخ الميلاد'],
            ['field' => 'phone_number', 'label' => 'رقم الهاتف'],
            ['field' => 'job', 'label' => 'المهنة'],
            ['field' => 'card_type.name', 'label' => 'نوع البطاقة'],
            ['field' => 'housing_type.name', 'label' => 'نوع السكن'],
            ['field' => 'location.name', 'label' => 'الحارة'],
            ['field' => 'is_male', 'label' => 'الجنس'],
            ['field' => 'is_beneficiary', 'label' => 'مستفيد؟'],
            ['field' => 'card_number', 'label' => 'رقم البطاقة'],
            ['field' => 'housing_address', 'label' => 'عنوان السكن'],
            ['field' => 'social_state.name', 'label' => 'الحالة الاجتماعية'],
            ['field' => 'level_state.name', 'label' => 'مستوى الحالة'],
            ['field' => 'meal_count', 'label' => 'عدد الحالات'],
            ['field' => 'male_count', 'label' => 'عدد الذكور'],
            ['field' => 'female_count', 'label' => 'عدد الإناث'],
            ['field' => 'family_members', 'label' => 'عدد أفراد الأسرة'],
            ['field' => 'notes', 'label' => 'ملاحظات'],
        ];

        return Inertia::render('People/ReportSetup', [
            'allColumns'    => $allColumns,
            'locations'     => Location::select('id', 'name')->get(),
            'socialStates'  => SocialState::select('id', 'name')->get(),
            'cardTypes'     => CardType::select('id', 'name')->get(),
            'housingTypes'  => HousingType::select('id', 'name')->get(),
            'levelStates'   => LevelState::select('id', 'name')->get(),
        ]);
    }

protected function filterWithOptionalNull(\Illuminate\Database\Eloquent\Builder $query, string $column, array $values): void
{
    $query->where(function ($q) use ($column, $values) {
        if (in_array('__EMPTY__', $values)) {
            $q->orWhereNull($column);
        }

        $realValues = array_filter($values, fn($v) => $v !== '__ALL__' && $v !== '__EMPTY__');

        if (!empty($realValues)) {
            $q->orWhereIn($column, $realValues);
        }
    });
}

public function view(Request $request)
{
    // تحقق هل الطلب GET أو POST
    if ($request->isMethod('post')) {
        // عند POST، خزّن القيم في الجلسة
        session([
            'people.filters' => $request->input('filters', []),
            'people.columns' => $request->input('columns', []),
        ]);
    }

    // استخدم البيانات من الجلسة
    $filters = session('people.filters', []);
    $columns = session('people.columns', []);


$filters = session('people.filters');
$columns = session('people.columns');

if (!$filters || !$columns) {
    // ⬇️ إعداد افتراضي (تعدّله حسب ما تحب)
    $filters = [
        'is_male' => 'all',
        'is_beneficiary' => 'all',
        'location_ids' => [],
        'card_type_ids' => [],
        'housing_type_ids' => [],
        'social_state_ids' => [],
        'level_state_ids' => [],
        'has_family' => 'all',
    ];
    $columns = ['name', 'birth_date', 'phone_number'];
}
    // بناء الاستعلام كما في السابق
    $query = Person::query()
        ->with(['cardType', 'housingType', 'location', 'socialState', 'levelState', 'familyMembers'])
        ->select([
            'id', 'name', 'birth_date', 'is_male', 'is_beneficiary',
            'phone_number', 'job', 'card_type_id', 'housing_type_id',
            'location_id', 'card_number', 'housing_address',
            'social_state_id', 'level_state_id', 'meal_count',
            'male_count', 'female_count', 'notes',
        ]);

    // ✅ تطبيق الفلاتر نفسها كما فعلنا في Excel Export (ممكن استخراجها لاحقًا لدالة مشتركة)

    if (!empty($filters['is_male']) && $filters['is_male'] !== 'all') {
        $query->where('is_male', $filters['is_male'] === 'male' ? 1 : 0);
    }

    if (!empty($filters['is_beneficiary']) && $filters['is_beneficiary'] !== 'all') {
        $query->where('is_beneficiary', (int) $filters['is_beneficiary']);
    }

        if (!empty($filters['location_ids'])) {
            $query->whereIn('location_id', $filters['location_ids']);
        }



if (!empty($filters['card_type_ids'])) {
    $this->filterWithOptionalNull($query, 'card_type_id', $filters['card_type_ids']);
}

if (!empty($filters['housing_type_ids'])) {
    $this->filterWithOptionalNull($query, 'housing_type_id', $filters['housing_type_ids']);
}

if (!empty($filters['social_state_ids'])) {
    $this->filterWithOptionalNull($query, 'social_state_id', $filters['social_state_ids']);
}

if (!empty($filters['level_state_ids'])) {
    $this->filterWithOptionalNull($query, 'level_state_id', $filters['level_state_ids']);
} 

if (!empty($filters['has_family']) && $filters['has_family'] !== 'all') {
        $query->whereHas('familyMembers', function ($q) {}, $filters['has_family'] === 'yes' ? '>' : '=', 0);
    }

    $people = $query->get();

    return Inertia::render('People/ReportView', [
        'people' => $people,
        'columns' => $columns,
    ]);
}


}