<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;

class GlobalBranchSwitcher extends Component
{
    public $selectedBranchId = null;
    public $branches = [];
    public $isSuperAdmin = false;
    public $currentBranchName = '';

    protected $listeners = [
        'branchSelectionUpdated' => '$refresh', 
        'settingsUpdated' => '$refresh'
    ];

    public function mount()
    {
        $this->loadContext();
    }

    public function loadContext()
    {
        $user = Auth::user();
        if (!$user) return;

        $this->isSuperAdmin = $user->role_id === 1;
        
        // Use BranchContext as source of truth for Super Admins
        if ($this->isSuperAdmin) {
            $this->selectedBranchId = \App\Services\BranchContext::getActiveBranchId();
            $this->branches = Branch::orderBy('branch_name')->get();
        } else {
            $this->selectedBranchId = $user->branch_id;
        }

        $branch = Branch::find($this->selectedBranchId);
        $this->currentBranchName = $branch?->branch_name ?? ($this->isSuperAdmin ? 'Global Network' : 'Global Core');
    }

    public function switchBranch($id)
    {
        if (!$this->isSuperAdmin) return;

        \App\Services\BranchContext::setActiveBranch($id);
        $this->emit('branch-switched');
        $this->emit('refreshTopbar');
        
        // Redirect to same page to refresh all components with new context
        return redirect(request()->header('Referer'));
    }

    public function render()
    {
        $this->loadContext();
        
        return view('livewire.global-branch-switcher');
    }
}

