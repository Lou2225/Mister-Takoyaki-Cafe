<?php

namespace App\Livewire\Sidebar;

use Livewire\Component;
use App\Models\Branch;
use App\Services\BranchContext;

class BranchSwitcher extends Component
{
    public $activeBranchId;

    public function selectBranch($id)
    {
        $this->activeBranchId = $id;
        BranchContext::setActiveBranch($id);
        
        // Signal a context change across the entire application
        $this->dispatch('branchContextUpdated');
        $this->dispatch('branch-switched');
    }

    public function render()
    {
        $user = auth()->user();
        $activeBranch = BranchContext::getActiveBranch();
        
        return view('livewire.sidebar.branch-switcher', [
            'branches' => Branch::where('status', 1)->get(),
            'activeBranch' => $activeBranch,
            'isSuperAdmin' => $user->role_id === 1
        ]);
    }
}
