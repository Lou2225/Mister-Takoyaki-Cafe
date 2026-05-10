<?php

namespace App\Livewire\Sidebar;

use Livewire\Component;
use App\Services\BranchContext;

class StatsWidget extends Component
{
    public $salesToDay = "₱12,450.00";
    public $ordersCount = 24;

    public function render()
    {
        $branch = BranchContext::getActiveBranch();
        
        return view('livewire.sidebar.stats-widget', [
            'branchName' => $branch ? $branch->branch_name : 'N/A'
        ]);
    }
}
