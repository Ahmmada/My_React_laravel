<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeacherAttendanceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'attendance_date' => [
                'required',
                'date',
                Rule::unique('teacher_attendances')
                    ->where('group_id', $this->group_id)
            ],
            'group_id' => [
                'required',
                Rule::exists('groups', 'id')->whereNull('deleted_at')
            ],
            'teachers' => 'required|array|min:1',
            'teachers.*.arrival_time' => 'required|date_format:H:i',
            'teachers.*.departure_time' => [
                'required',
                'date_format:H:i',
                'after:teachers.*.arrival_time'
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'teachers.*.departure_time.after' => __('Departure time must be after arrival time')
        ];
    }
}