<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\OptionTemplate;
use App\Models\OptionTemplateItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OptionLibraryManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $view = 'table'; // table | board
    public $panel = 'list'; // list | form
    public $mode = 'list'; // list | create | edit
    public $priceModeFilter = ''; // '' | additive | fixed
    public $perPage = 5;

    // Form State
    public $editTemplateId = null;
    public $name = '';
    public $priceMode = 'additive';
        public $isRequired = false;
    public $noRecipeRequired = false;
    public $templateItems = [];

    // Deletion State
    public $deleteTargetId = null;
    public $deleteTargetName = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'view' => ['except' => 'table'],
        'perPage' => ['except' => 5],
    ];

    protected $listeners = ['refresh' => '$refresh'];

    public function mount()
    {
        if (!auth()->user() || !in_array(auth()->user()->role_id, [1, 2])) {
            abort(403);
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

        public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function updatedNoRecipeRequired($value)
    {
        if ($value) {
            foreach ($this->templateItems as $i => $item) {
                $this->templateItems[$i]['ingredients'] = [];
            }
        }
    }

    public function addItemIngredient(int $itemIndex)
    {
        $ingredientPath = "templateItems.{$itemIndex}.new_ingredient_id";
        $quantityPath = "templateItems.{$itemIndex}.new_ingredient_qty";

        $this->validate([
            $ingredientPath => 'required|exists:ingredients,id',
            $quantityPath => 'required|numeric|min:0.01',
        ], ["{$quantityPath}.min" => 'Quantity must be at least 0.01.']);

        $item = $this->templateItems[$itemIndex] ?? null;
        $ing = \App\Models\Ingredient::find($item['new_ingredient_id'] ?? null);
        if (!$ing || !isset($this->templateItems[$itemIndex])) return;

        foreach (($this->templateItems[$itemIndex]['ingredients'] ?? []) as $ri) {
            if ($ri['id'] == $ing->id) {
                $this->dispatch('notify', type: 'error', message: 'Ingredient already added to this option.');
                return;
            }
        }

        $this->templateItems[$itemIndex]['ingredients'][] = [
            'id'       => $ing->id,
            'name'     => $ing->name,
            'unit'     => $ing->unit,
            'quantity' => (float) ($item['new_ingredient_qty'] ?? 0),
            'cost'     => $ing->cost,
        ];

        $this->templateItems[$itemIndex]['new_ingredient_id'] = '';
        $this->templateItems[$itemIndex]['new_ingredient_qty'] = '';
    }

    public function removeItemIngredient(int $itemIndex, int $ingredientIndex)
    {
        unset($this->templateItems[$itemIndex]['ingredients'][$ingredientIndex]);
        $this->templateItems[$itemIndex]['ingredients'] = array_values($this->templateItems[$itemIndex]['ingredients'] ?? []);
    }

    // ── Panel Actions ─────────────────────────────────────────────
    public function showCreate()
    {
        $this->resetForm();
        $this->panel = 'form';
        $this->mode = 'create';
    }

    public function showEdit($id)
    {
        $template = OptionTemplate::with('items.ingredients.ingredient')->findOrFail($id);
        $this->editTemplateId = $template->id;
        $this->name = $template->name;
        $this->priceMode = $template->price_mode;
                $this->isRequired = (bool)$template->is_required;
        $this->noRecipeRequired = (bool)$template->no_recipe_required;
        
        $this->templateItems = collect($template->items)->map(fn($item) => [
            'id'         => $item instanceof OptionTemplateItem ? $item->id : ($item['id'] ?? null),
            'name'       => $item instanceof OptionTemplateItem ? $item->name : ($item['name'] ?? ''),
            'price'      => $item instanceof OptionTemplateItem ? ($item->price == 0 ? '' : $item->price) : ($item['price'] == 0 ? '' : $item['price']),
            'is_default' => (bool)($item instanceof OptionTemplateItem ? $item->is_default : ($item['is_default'] ?? false)),
            'new_ingredient_id' => '',
            'new_ingredient_qty' => '',
            'ingredients' => $item instanceof OptionTemplateItem
                ? $item->ingredients->map(fn($ri) => [
                    'id' => $ri->ingredient_id, 'name' => $ri->ingredient->name,
                    'unit' => $ri->ingredient->unit, 'quantity' => (float)$ri->quantity, 'cost' => $ri->ingredient->cost,
                  ])->toArray()
                : [],
        ])->values()->toArray();

        $this->panel = 'form';
        $this->mode = 'edit';
    }

    public function backToList()
    {
        $this->panel = 'list';
        $this->mode = 'list';
        $this->resetForm();
    }

    // ── Form Actions ──────────────────────────────────────────────
    public function addItem()
    {
        $this->templateItems[] = [
            'id' => null,
            'name' => '',
            'price' => '',
            'is_default' => false,
            'new_ingredient_id' => '',
            'new_ingredient_qty' => '',
            'ingredients' => [],
        ];
    }

    public function removeItem($index)
    {
        unset($this->templateItems[$index]);
        $this->templateItems = array_values($this->templateItems);
    }

    public function toggleItemDefault($index)
    {
        $currentValue = $this->templateItems[$index]['is_default'] ?? false;
        
        foreach ($this->templateItems as $i => $item) {
            $this->templateItems[$i]['is_default'] = false;
        }

        if (!$currentValue) {
            $this->templateItems[$index]['is_default'] = true;
        }
    }

    public function saveTemplate()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'priceMode' => 'required|in:fixed,additive',
            'templateItems.*.name' => 'required|string|max:100',
            'templateItems.*.price' => 'nullable|numeric|min:0',
        ]);

        // Duplicate check
        $exists = OptionTemplate::where('name', $this->name)
            ->when($this->editTemplateId, fn($q) => $q->where('id', '!=', $this->editTemplateId))
            ->exists();

        if ($exists) {
            $this->dispatch('notify', type: 'error', message: "A template named '{$this->name}' already exists in the library.");
            return;
        }

        try {
            DB::transaction(function () {
                $template = $this->editTemplateId 
                    ? OptionTemplate::findOrFail($this->editTemplateId)
                    : new OptionTemplate();

                                $template->name = $this->name;
                $template->price_mode = $this->priceMode;
                $template->is_required = $this->isRequired;
                $template->no_recipe_required = $this->noRecipeRequired;
                $template->save();

                $keepIds = [];
                foreach ($this->templateItems as $itemData) {
                    $item = !empty($itemData['id']) ? $template->items()->find($itemData['id']) : null;
                    $payload = [
                        'name' => $itemData['name'],
                        'price' => (float)($itemData['price'] ?: 0),
                        'is_default' => (bool)($itemData['is_default'] ?? false),
                    ];

                    $item = $item ? tap($item, fn($i) => $i->update($payload)) : $template->items()->create($payload);
                    $keepIds[] = $item->id;

                                        // Rebuild this item's ingredient recipe from scratch —
                    // same pattern as Recipe rebuild in MenuManagement.
                    $item->ingredients()->delete();
                    if (!$this->noRecipeRequired) {
                        foreach (($itemData['ingredients'] ?? []) as $ri) {
                            $item->ingredients()->create([
                                'ingredient_id' => $ri['id'],
                                'quantity'      => $ri['quantity'],
                            ]);
                        }
                    }
                }
                $template->items()->whereNotIn('id', $keepIds)->delete();
            });

            $this->dispatch('notify', type: 'success', message: 'Template saved to library.');
            $this->backToList();
        } catch (\Exception $e) {
            Log::error('OptionLibraryManagement.saveTemplate failed: ' . $e->getMessage());
            $this->dispatch('notify', type: 'error', message: 'Failed to save template.');
        }
    }

    public function confirmDeleteTemplate($id)
    {
        $template = OptionTemplate::findOrFail($id);
        $this->deleteTargetId = $template->id;
        $this->deleteTargetName = $template->name;
        $this->dispatch('open-modal', name: 'delete-template');
    }

    public function deleteTemplate()
    {
        if (!$this->deleteTargetId) return;

        $template = OptionTemplate::findOrFail($this->deleteTargetId);
        $name = $template->name;
        $template->delete();

        $this->deleteTargetId = null;
        $this->deleteTargetName = '';
        $this->dispatch('notify', type: 'success', message: "Template '{$name}' removed from library.");
        $this->dispatch('close-modal', name: 'delete-template');
        $this->backToList();
    }

    private function resetForm()
    {
        $this->editTemplateId = null;
        $this->name = '';
        $this->priceMode = 'additive';
                $this->isRequired = false;
        $this->noRecipeRequired = false;
        $this->templateItems = [];
        $this->resetValidation();
    }

    public function render()
    {
        $allTemplates = OptionTemplate::query()
            ->with('items')
            ->orderBy('name', 'asc')
            ->get();

        return view('livewire.option-library-management', [
            'allTemplates' => $allTemplates
        ])->layout('layouts.app');
    }
}
