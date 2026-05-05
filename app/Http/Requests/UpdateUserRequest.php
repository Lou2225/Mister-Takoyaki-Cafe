<?php

namespace App\Http\Requests;

use App\Helpers\ValidationHelper;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form Request for updating existing users.
 * Enforces strict validation and auto-sanitizes input.
 */
class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only Super Admin (role_id=1) and Admin (role_id=2) can update users
        $user = User::findOrFail($this->route('user'));
        return auth()->check() && in_array(auth()->user()->role_id, [1, 2]);
    }

    /**
     * Prepare the data for validation.
     * Auto-sanitize and normalize inputs before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'firstName' => ValidationHelper::sanitizeInput($this->firstName, 'name'),
            'middleName' => ValidationHelper::sanitizeInput($this->middleName, 'name'),
            'lastName' => ValidationHelper::sanitizeInput($this->lastName, 'name'),
            'email' => ValidationHelper::sanitizeInput($this->email, 'email'),
            'phone' => ValidationHelper::sanitizeInput($this->phone, 'phone'),
            'position' => ValidationHelper::sanitizeInput($this->position, 'alphanumeric'),
            'employeeId' => ValidationHelper::sanitizeInput($this->employeeId, 'alphanumeric'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $user = User::findOrFail($this->route('user'));

        return [
            'employeeId' => [
                'nullable',
                'string',
                'max:50',
                'regex:' . ValidationHelper::REGEX_ID,
                Rule::unique('users', 'employee_id')->ignore($user->id),
            ],
            'firstName' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:' . ValidationHelper::REGEX_NAME,
                'not_regex:/^[\s\-\.]/',     // Can't start with space/hyphen/dot
                'not_regex:/[\s\-\.]$/',     // Can't end with space/hyphen/dot
                'not_regex:/\s{2,}/',        // No consecutive spaces
                'not_regex:/(.)\1{3,}/',     // No more than 3 repeating chars
            ],
            'middleName' => [
                'nullable',
                'string',
                'min:2',
                'max:100',
                'regex:' . ValidationHelper::REGEX_NAME,
                'not_regex:/^[\s\-\.]/',
                'not_regex:/[\s\-\.]$/',
            ],
            'lastName' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:' . ValidationHelper::REGEX_NAME,
                'not_regex:/^[\s\-\.]/',
                'not_regex:/[\s\-\.]$/',
                'not_regex:/\s{2,}/',
                'not_regex:/(.)\1{3,}/',
            ],
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => [
                'nullable',
                'string',
                'regex:' . ValidationHelper::REGEX_PHONE,
                'not_regex:/(.)\1{4,}/',  // No 5+ repeating digits
            ],
            'formRoleId' => [
                'required',
                'integer',
                Rule::in([1, 2, 3]),  // Super Admin, Admin, Staff
            ],
            'formBranchId' => [
                'nullable',
                'integer',
                'exists:branches,id',
            ],
            'position' => [
                'required_if:formRoleId,3',  // Required only for Staff
                'nullable',
                'string',
                'max:100',
                Rule::in(['Cashier', 'Delivery Rider']),  // Strict enumeration
            ],
            'dateHired' => [
                'nullable',
                'date_format:Y-m-d',
                'before_or_equal:today',
            ],
            'formIsActive' => [
                'required',
                'boolean',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge(
            ValidationHelper::commonMessages(),
            ValidationHelper::nameMessages('firstName', 'lastName'),
            ValidationHelper::phoneMessages(),
            ValidationHelper::strictMessages(),
            [
                'firstName.not_regex' => 'First name cannot start or end with spaces, hyphens, or dots.',
                'lastName.not_regex' => 'Last name cannot start or end with spaces, hyphens, or dots.',
                'phone.not_regex' => 'Phone number contains too many repeating digits.',
                'position.required_if' => 'Position is required for staff members.',
                'position.in' => 'The selected position is invalid.',
                'formRoleId.in' => 'The selected role is not available.',
                'employeeId.unique' => 'This employee ID is already in use.',
                'employeeId.regex' => 'Employee ID format is invalid.',
            ]
        );
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'employeeId' => 'employee ID',
            'firstName' => 'first name',
            'middleName' => 'middle name',
            'lastName' => 'last name',
            'email' => 'email address',
            'phone' => 'phone number',
            'formRoleId' => 'role',
            'formBranchId' => 'branch',
            'position' => 'position',
            'dateHired' => 'date hired',
            'formIsActive' => 'account status',
        ];
    }

    /**
     * Handle a failed validation attempt.
     * Can be customized to log validation failures.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        // Log failed validations with context for security monitoring
        \Illuminate\Support\Facades\Log::warning('User update validation failed', [
            'user_id' => auth()->id(),
            'target_user_id' => $this->route('user'),
            'email_attempt' => $this->email,
            'errors' => $validator->errors()->messages(),
            'ip' => $this->ip(),
            'user_agent' => $this->userAgent(),
            'timestamp' => now(),
        ]);

        parent::failedValidation($validator);
    }
}
