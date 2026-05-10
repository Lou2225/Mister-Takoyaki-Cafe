<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\CustomerReview;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;

class CustomerReviewManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedBranchId = '';
    public $viewingReview = null;
    public $perPage = 5;

    public function mount()
    {
        $user = auth()->user();
        if (!$user || !in_array($user->role_id, [1, 2])) {
            abort(403, 'Unauthorized access to Customer Reviews.');
        }

        // Use global context for Super Admin, assigned branch for others
        if ($user->role_id === 1) {
            $this->selectedBranchId = \App\Services\BranchContext::getActiveBranchId();
        } else {
            $this->selectedBranchId = $user->branch_id;
        }
    }


    protected $queryString = [
        'search' => ['except' => ''],
        'selectedBranchId' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedBranchId()
    {
        $this->resetPage();
    }

    public function viewReview($id)
    {
        $user = auth()->user();
        $query = CustomerReview::with('branch');
        
        if ($user->role_id !== 1) {
            $query->where('branch_id', $user->branch_id);
        }

        $this->viewingReview = $query->findOrFail($id);
        $this->dispatch('open-modal', 'view-review-detail');
    }

    public function closeReview()
    {
        $this->viewingReview = null;
        $this->dispatch('close-modal', 'view-review-detail');
    }

    public function render()
    {
        $user = auth()->user();
        $query = CustomerReview::with('branch');

        // Force branch restriction for non-super admins
        if ($user->role_id !== 1) {
            $this->selectedBranchId = $user->branch_id;
        }

        if ($this->selectedBranchId) {
            $query->where('branch_id', $this->selectedBranchId);
        }

        if ($this->search) {
            $query->where(function($q) {
                $q->where('customer_name', 'like', '%' . $this->search . '%')
                  ->orWhere('contact_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('branch', function($bq) {
                      $bq->where('branch_name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        $reviews = $query->latest()->paginate($this->perPage);

        // Calculate Stats (Scoped to branch if not super admin)
        $statsQuery = CustomerReview::query();
        if ($user->role_id !== 1) {
            $statsQuery->where('branch_id', $user->branch_id);
        }

        $totalReviews = (clone $statsQuery)->count();
        $branchReviews = $this->selectedBranchId ? (clone $statsQuery)->where('branch_id', $this->selectedBranchId)->count() : $totalReviews;
        
        // Calculate Average Rating (Scoped)
        $allReviews = (clone $statsQuery)->get();
        $totalRatingSum = 0;
        $ratingCount = 0;

        foreach ($allReviews as $review) {
            if (is_array($review->answers)) {
                foreach ($review->answers as $a) {
                    if (isset($a['type']) && $a['type'] === 'rating' && isset($a['answer'])) {
                        $totalRatingSum += (float)$a['answer'];
                        $ratingCount++;
                    }
                }
            }
        }

        $averageRating = $ratingCount > 0 ? round($totalRatingSum / $ratingCount, 1) : 0;
        $latestReview = (clone $statsQuery)->latest()->first();

        $branches = Branch::orderBy('branch_name')->get();

        return view('livewire.customer-review-management', [
            'reviews' => $reviews,
            'branches' => $branches,
            'stats' => [
                'total' => $totalReviews,
                'average' => $averageRating,
                'branch_count' => $branchReviews,
                'latest' => $latestReview ? $latestReview->created_at->diffForHumans() : 'No reviews yet'
            ]
        ])->layout('layouts.app');
    }
}
