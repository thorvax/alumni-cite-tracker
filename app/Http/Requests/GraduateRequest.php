<?php

namespace App\Http\Requests;

use App\Models\Graduate;
use App\Support\Import\StudentSheetValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GraduateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $graduate = $this->route('graduate');

        return [
            'full_name' => ['required', 'string', 'max:150'],
            'birth_date' => ['required', 'date', 'before:today'],
            'gender' => ['required', 'in:Male,Female'],
            'civil_status' => ['required', 'in:Single,Married,Widowed,Separated,Divorced'],
            'email' => ['nullable', 'email', 'max:150'],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'current_address' => ['nullable', 'string', 'max:255'],
            'region' => ['required', 'string', 'max:150'],
            'province' => ['required', 'string', 'max:150'],
            'city' => ['required', 'string', 'max:150'],

            // School records. A Student ID gives the graduate a personal survey link,
            // and activation checks Student ID + Last Name, so Last Name is needed with it.
            'student_id' => [
                'nullable',
                'regex:'.StudentSheetValidator::ID_PATTERN,
                Rule::unique('graduates', 'student_id')->ignore($graduate instanceof Graduate ? $graduate->id : null),
            ],
            'last_name' => ['nullable', 'required_with:student_id', 'string', 'max:100'],
            'course' => ['nullable', Rule::in(array_keys(Graduate::COURSE_CODES))],
            'year_graduated' => ['nullable', 'integer', 'between:'.StudentSheetValidator::FIRST_YEAR.','.date('Y')],
            'employment_status' => ['nullable', Rule::in(Graduate::STATUSES)],
            'send_invite' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'student_id.regex' => 'Student ID Number must be 8 digits, like 20244012.',
            'student_id.unique' => 'Another graduate already has this Student ID Number.',
            'last_name.required_with' => 'Last Name is needed with a Student ID Number (the graduate uses both to activate their account).',
            'year_graduated.between' => 'Year Graduated must be from '.StudentSheetValidator::FIRST_YEAR.' to '.date('Y').'.',
        ];
    }
}
