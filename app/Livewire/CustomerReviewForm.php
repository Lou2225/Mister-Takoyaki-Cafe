<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Branch;
use App\Models\CustomerReview;
use App\Traits\HandlesValidations;
use App\Helpers\ValidationHelper;

class CustomerReviewForm extends Component
{
    use HandlesValidations;
    public $branchId;
    public $branch;
    public $title = 'How was your experience?';
    public $subtitle = 'Thank you for your feedback!';
    public $questions = [];
    public $answers = [];
    
    public $customer_name = '';
    public $contact_number = '';
    public $isSubmitted = false;

    public function mount($branch = null)
    {
        // Try to get branch from route parameter or query string
        $branch = $branch ?: request()->query('branch');

        if ($branch) {
            $this->branch = Branch::where('id', $branch)->orWhere('branch_code', $branch)->first();
            if ($this->branch) {
                $this->branchId = $this->branch->id;
            }
        }

        // Load configuration from System Settings
        $this->title = \App\Models\SystemSetting::get('review_form_title', 'How was your experience?');
        $this->subtitle = \App\Models\SystemSetting::get('review_form_subtitle', 'Thank you for your feedback!');
        
        $questionsJson = \App\Models\SystemSetting::get('review_questions', '[]');
        $this->questions = is_string($questionsJson) ? json_decode($questionsJson, true) : $questionsJson;

        // Initialize empty answers based on questions
        foreach ($this->questions as $index => $q) {
            $this->answers[$index] = $q['type'] === 'rating' ? 0 : '';
        }
    }

    public function updatedCustomerName()
    {
        $this->validateFieldLive('customer_name', ['nullable', 'string', 'max:255', 'regex:' . ValidationHelper::REGEX_NAME], ValidationHelper::commonMessages());
    }

    public function updatedContactNumber()
    {
        $this->validateFieldLive('contact_number', ['nullable', 'string', 'max:50', 'regex:' . ValidationHelper::REGEX_PHONE], ValidationHelper::commonMessages());
    }

    public function updatedAnswers($value, $key)
    {
        // answers.0 format
        $index = explode('.', $key)[1] ?? null;
        if ($index !== null && isset($this->questions[$index])) {
            $question = $this->questions[$index];
            $rules = [];
            if ($question['type'] === 'text') {
                $rules = ['string', 'max:1000', 'regex:' . ValidationHelper::REGEX_NAME_BASIC];
            }
            if (!empty($rules)) {
                 $this->validateFieldLive($key, $rules, ValidationHelper::commonMessages());
            }
        }
    }

    public function setRating($questionIndex, $value)
    {
        $this->answers[$questionIndex] = $value;
    }

    public function submit()
    {
        $rules = [
            'customer_name' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:50',
        ];

        foreach ($this->questions as $index => $question) {
            if ($question['required'] ?? false) {
                if ($question['type'] === 'rating') {
                    $rules["answers.$index"] = 'required|integer|min:1|max:5';
                } else {
                    $rules["answers.$index"] = 'required|string|max:1000';
                }
            } else {
                if ($question['type'] === 'rating') {
                    $rules["answers.$index"] = 'nullable|integer|min:1|max:5';
                } else {
                    $rules["answers.$index"] = 'nullable|string|max:1000';
                }
            }
        }

        $this->validate($rules, ValidationHelper::commonMessages());

        // Transform answers to store question text alongside the answer
        $structuredAnswers = collect($this->questions)->map(function($q, $idx) {
            return [
                'question' => $q['text'],
                'type' => $q['type'],
                'answer' => $this->answers[$idx] ?? null
            ];
        })->toArray();

        CustomerReview::create([
            'branch_id' => $this->branchId,
            'answers' => $structuredAnswers,
            'customer_name' => $this->customer_name,
            'contact_number' => $this->contact_number,
        ]);

        $this->isSubmitted = true;
    }

    public function render()
    {
        return view('livewire.customer-review-form')->layout('layouts.mobile');
    }
}
