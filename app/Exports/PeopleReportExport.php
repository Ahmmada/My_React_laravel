<?php

namespace App\Exports;

use App\Models\Person;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromView;

class PeopleReportExport implements FromView
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
    
    protected array $filters;
    protected array $columns;
    
    public function headings(): array
{
    return array_map(fn ($field) => $this->getFieldLabel($field), $this->columns);
}
    public function __construct(array $filters, array $columns)
    {
        $this->filters = $filters;
        $this->columns = $columns;
    }

    public function view(): View
    {
        $query = Person::query()
            ->with(['cardType', 'housingType', 'location', 'socialState', 'levelState', 'familyMembers'])
            ->select([
                'id', 'name', 'birth_date', 'is_male', 'is_beneficiary',
                'phone_number', 'job', 'card_type_id', 'housing_type_id',
                'location_id', 'card_number', 'housing_address',
                'social_state_id', 'level_state_id', 'meal_count',
                'male_count', 'female_count', 'notes',
            ]);

        $f = $this->filters;

        // فلاتر
        if (!empty($f['is_male']) && $f['is_male'] !== 'all') {
            $query->where('is_male', $f['is_male'] === 'male' ? 1 : 0);
        }

        if (!empty($f['location_ids'])) {
            $query->whereIn('location_id', $f['location_ids']);
        }


if (!empty($this->filters['card_type_ids'])) {
    $this->filterWithOptionalNull($query, 'card_type_id', $this->filters['card_type_ids']);
}

if (!empty($this->filters['housing_type_ids'])) {
    $this->filterWithOptionalNull($query, 'housing_type_id', $this->filters['housing_type_ids']);
}

if (!empty($this->filters['social_state_ids'])) {
    $this->filterWithOptionalNull($query, 'social_state_id', $this->filters['social_state_ids']);
}

if (!empty($this->filters['level_state_ids'])) {
    $this->filterWithOptionalNull($query, 'level_state_id', $this->filters['level_state_ids']);
}

        if (!empty($f['has_family']) && $f['has_family'] !== 'all') {
            $query->whereHas('familyMembers', function ($q) {}, $f['has_family'] === 'yes' ? '>' : '=', 0);
        }

        $people = $query->get();

        return view('exports.people-report', [
            'people' => $people,
            'columns' => $this->columns,
        ]);
    }
}

