<?php

if (!function_exists('getFieldLabel')) {
    function getFieldLabel($field): string
    {
        return [
            'id' => 'ID',
            'name' => 'الاسم',
            'birth_date' => 'تاريخ الميلاد',
            'phone_number' => 'رقم الهاتف',
            'job' => 'المهنة',
            'card_type.name' => 'نوع البطاقة',
            'housing_type.name' => 'نوع السكن',
            'location.name' => 'الحارة',
            'is_male' => 'الجنس',
            'is_beneficiary' => 'مستفيد؟',
            'card_number' => 'رقم البطاقة',
            'housing_address' => 'عنوان السكن',
            'social_state.name' => 'الحالة الاجتماعية',
            'level_state.name' => 'مستوى الحالة',
            'meal_count' => 'عدد الحالات',
            'male_count' => 'عدد الذكور',
            'female_count' => 'عدد الإناث',
            'family_members' => 'عدد أفراد الأسرة',
            'notes' => 'ملاحظات',
        ][$field] ?? $field;
    }
}