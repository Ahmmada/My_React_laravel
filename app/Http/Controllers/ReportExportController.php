<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Person;
use Mpdf\Mpdf;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;

class ReportExportController extends Controller
{
protected function filterWithOptionalNull($query, string $column, array $values): void
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
    
    public function exportPDF(Request $request)
    {
        $columns = $request->input('columns', []);
        $filters = json_decode($request->input('filters', '{}'), true);

        $query = Person::query()
            ->with(['cardType', 'housingType', 'location', 'socialState', 'levelState', 'familyMembers'])
            ->select([
                'id', 'name', 'birth_date', 'is_male', 'is_beneficiary',
                'phone_number', 'job', 'card_type_id', 'housing_type_id',
                'location_id', 'card_number', 'housing_address',
                'social_state_id', 'level_state_id', 'meal_count',
                'male_count', 'female_count', 'notes',
            ]);

        // 🔎 فلاتر كما في التصدير السابق
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
        $user = Auth::user()?->name ?? 'مستخدم مجهول';
        $people = $query->get();

        $html = View::make('exports.people-pdf', [
            'people' => $people,
            'columns' => $columns,
            'createdBy' => $user,
        ])->render();

        $mpdf = new Mpdf([
    'tempDir' => storage_path('app/mpdf-temp'),
    'mode' => 'utf-8',
    'format' => 'A4',
    'directionality' => 'rtl',
    'default_font' => 'xbriyaz',
    'margin_top' => 28,      // مساحة الرأس
    'margin_bottom' => 20,   // مساحة التذييل
    'margin_left' => 10,
    'margin_right' => 10,
        ]);

        $mpdf->WriteHTML($html);

$filename = 'تقرير_المبادرة_' . now()->format('Y_m_d_H_i') . '.pdf';

return response($mpdf->Output($filename, \Mpdf\Output\Destination::INLINE))
    ->header('Content-Type', 'application/pdf');
    }
}

