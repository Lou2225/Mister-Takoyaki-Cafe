<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\BranchContext;
use Livewire\Attributes\On;


class BranchContextLabel extends Component
{
    public string $iconColor;

    #[On('branch-switched')]
    #[On('branchContextUpdated')]
    #[On('settingsUpdated')]
    #[On('context-updated')]
    public function refreshLabel(): void
    {
        //
    }

    public function mount(string $iconColor)
    {
        $this->iconColor = $iconColor;
    }

    public function getContextProperty(): array
    {
        $user = auth()->user();
        $branch = \App\Models\Branch::find(BranchContext::getActiveBranchId() ?: $user?->branch_id);

        return [
            'label' => $user?->isSuperAdmin() ? 'Global Context' : 'Assigned Branch',
            'value' => $branch?->branch_name ?? ($user?->isSuperAdmin() ? 'General Headquarters' : 'No Branch Assigned'),
        ];
    }

    public function render()
    {
        return view('livewire.branch-context-label');
    }
}