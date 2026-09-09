<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\BranchContext;
use Livewire\Attributes\On;


class BranchContextLabel extends Component
{
    public string $iconColor = 'text-indigo-500';
    public string $label = '';
    public string $value = '';

    public function mount(string $iconColor = 'text-indigo-500')
    {
        $this->iconColor = $iconColor;
        $this->syncContext();
    }

    public function syncContext(): void
    {
        $user = auth()->user();
        $branch = \App\Models\Branch::find(BranchContext::getActiveBranchId() ?: $user?->branch_id);

        $this->label = $user?->isSuperAdmin() ? 'Global Context' : 'Assigned Branch';
        $this->value = $branch?->branch_name ?? ($user?->isSuperAdmin() ? 'General Headquarters' : 'No Branch Assigned');
    }

    public function render()
    {
        return view('livewire.branch-context-label');
    }
}