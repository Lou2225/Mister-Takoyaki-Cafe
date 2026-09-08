<?php

namespace Tests\Feature;

use App\Models\User;
use Livewire\Livewire;
use Tests\TestCase;

class BusinessIntelligenceAccessTest extends TestCase
{
    /** @test */
    public function a_cashier_cannot_access_business_intelligence(): void
    {
        $user = new User(['role_id' => 3]);

        $this->actingAs($user)
            ->get('/reports')
            ->assertForbidden();
    }

    /** @test */
    public function a_cashier_cannot_mount_the_business_intelligence_component_directly(): void
    {
        $user = new User(['role_id' => 3]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\BusinessIntelligence::class)
            ->assertStatus(403);
    }
}
