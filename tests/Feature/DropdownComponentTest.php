<?php

namespace Tests\Feature;

use Tests\TestCase;

class DropdownComponentTest extends TestCase
{
    /** @test */
    public function it_forwards_root_attributes_and_stops_trigger_click_bubbling()
    {
        $view = $this->blade(<<<'BLADE'
            <x-dropdown wire:key="category-actions-demo" class="custom-class">
                <x-slot name="trigger">
                    <button type="button">Actions</button>
                </x-slot>

                <x-slot name="content">
                    Menu
                </x-slot>
            </x-dropdown>
        BLADE);

        $view->assertSee('wire:key="category-actions-demo"', false);
        $view->assertSee('@click.stop="dropdownOpen = !dropdownOpen"', false);
        $view->assertSee('custom-class', false);
    }
}
