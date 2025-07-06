<table>
    <thead>
        <tr>
      @foreach ($columns as $field)
        <th>{{ getFieldLabel($field) }}</th>
      @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach ($people as $person)
            <tr>
                @foreach ($columns as $field)
                    <td>
@php
    switch ($field) {
        case 'card_type.name':
            $value = $person->cardType?->name ?? '';
            break;
        case 'housing_type.name':
            $value = $person->housingType?->name ?? '';
            break;
        case 'location.name':
            $value = $person->location?->name ?? '';
            break;
        case 'social_state.name':
            $value = $person->socialState?->name ?? '';
            break;
        case 'level_state.name':
            $value = $person->levelState?->name ?? '';
            break;
        case 'is_male':
            $value = $person->is_male ? 'ذكر' : 'أنثى';
            break;
        case 'is_beneficiary':
            $value = $person->is_beneficiary ? 'مستفيد' : 'غير مستفيد';
            break;
        case 'family_members':
            $value = $person->familyMembers->count() > 0
                ? $person->familyMembers->count().' أفراد'
                : 'لا يوجد';
            break;
        default:
            $value = data_get($person, $field);
    }
@endphp
                        {{ $value }}
                    </td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>