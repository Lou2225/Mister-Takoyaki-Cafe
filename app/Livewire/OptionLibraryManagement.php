<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\OptionTemplate;
use App\Models\OptionTemplateItem;
use App\Models\Ingredient;
use App\Helpers\StockHelper;
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
    public $maxSelect = 1;
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
        $this->templateItems = [
            [
                'id' => null,
                'name' => '',
                'price' => '',
                'is_default' => false,
                'new_ingredient_id' => '',
                'new_ingredient_qty' => '',
                'ingredients' => [],
            ]
        ];
        $this->panel = 'form';
        $this->mode = 'create';
    }

    public function showEdit($id)
    {
        $template = OptionTemplate::with(['items.ingredients.ingredient'])->findOrFail($id);
        $this->editTemplateId = $template->id;
        $this->name = $template->name;
        $this->priceMode = $template->price_mode;
        $this->maxSelect = $template->max_select;
        $this->isRequired = (bool)$template->is_required;
        $this->noRecipeRequired = (bool)$template->no_recipe_required;
        
        $this->templateItems = collect($template->items)->map(fn($item) => [
            'id'         => $item->id,
            'name'       => $item->name,
            'price'      => (float)$item->price == 0 ? '' : (float)$item->price,
            'is_default' => (bool)$item->is_default,
            'new_ingredient_id' => '',
            'new_ingredient_qty' => '',
            'ingredients' => $item->ingredients->map(fn($ri) => [
                'id'       => (int)$ri->ingredient_id,
                'name'     => $ri->ingredient ? (string)$ri->ingredient->name : 'Unknown',
                'unit'     => $ri->ingredient ? (string)StockHelper::getAbbreviation($ri->ingredient->unit) : '',
                'quantity' => (float)$ri->quantity,
                'cost'     => (float)($ri->ingredient?->cost ?? 0),
            ])->values()->toArray(),
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
            'is_default' => count($this->templateItems) === 0,
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


    public function saveTemplate(array $payload = [])
    {
        if (isset($payload['id'])) $this->editTemplateId = $payload['id'] ?: null;
        if (isset($payload['name'])) $this->name = trim($payload['name']);
        if (isset($payload['priceMode'])) $this->priceMode = $payload['priceMode'];
        if (isset($payload['maxSelect'])) $this->maxSelect = $payload['maxSelect'];
        if (isset($payload['isRequired'])) $this->isRequired = (bool) $payload['isRequired'];
        if (isset($payload['noRecipeRequired'])) $this->noRecipeRequired = (bool) $payload['noRecipeRequired'];
        if (isset($payload['templateItems'])) $this->templateItems = $payload['templateItems'];

        foreach ($this->templateItems as $i => $it) {
            $this->templateItems[$i]['price'] = (!isset($it['price']) || $it['price'] === '' || $it['price'] === null)
                ? 0 : (float) $it['price'];
            if (!isset($it['ingredients']) || !is_array($it['ingredients'])) {
                $this->templateItems[$i]['ingredients'] = [];
            }
        }

        // Use Validator manually instead of $this->validate() — the latter throws
        // ValidationException, which Livewire intercepts before our JS ever sees
        // a return value. We need a plain array back so $wire.saveTemplate()
        // can resolve with {success:false, errors:{...}}.
        $validator = \Illuminate\Support\Facades\Validator::make(
            [
                'name' => $this->name,
                'priceMode' => $this->priceMode,
                'maxSelect' => $this->maxSelect,
                'templateItems' => $this->templateItems,
            ],
            [
                'name' => 'required|string|max:255',
                'priceMode' => 'required|in:fixed,additive',
                'maxSelect' => 'nullable|integer|min:1',
                'templateItems' => 'required|array|min:1',
                'templateItems.*.name' => 'required|string|max:100',
                'templateItems.*.price' => 'nullable|numeric|min:0',
            ],
            [
                'name.required' => 'Template name is required.',
                'maxSelect.min' => 'Maximum selections must be at least 1.',
                'templateItems.required' => 'At least one variation option is required.',
                'templateItems.min' => 'At least one variation option is required.',
                'templateItems.*.name.required' => 'Every option must have a name.',
                'templateItems.*.price.numeric' => 'Option price must be a valid number.',
                'templateItems.*.price.min' => 'Option price cannot be negative.',
            ]
        );

        if ($validator->fails()) {
            return ['success' => false, 'errors' => $validator->errors()->toArray()];
        }

        $exists = OptionTemplate::where('name', $this->name)
            ->when($this->editTemplateId, fn($q) => $q->where('id', '!=', $this->editTemplateId))
            ->exists();

        if ($exists) {
            return ['success' => false, 'errors' => ['name' => "A template named '{$this->name}' already exists in the library."]];
        }

        try {
            $template = DB::transaction(function () {
                $template = $this->editTemplateId
                    ? OptionTemplate::findOrFail($this->editTemplateId)
                    : new OptionTemplate();

                $template->name = $this->name;
                $template->price_mode = $this->priceMode;
                $template->max_select = $this->maxSelect ? (int)$this->maxSelect : null;
                $template->is_required = $this->isRequired;
                $template->no_recipe_required = $this->noRecipeRequired;
                $template->save();

                $keepIds = [];
                foreach ($this->templateItems as $itemData) {
                    $item = !empty($itemData['id']) ? $template->items()->find($itemData['id']) : null;
                    $itemPayload = [
                        'name' => $itemData['name'],
                        'price' => (float)($itemData['price'] ?: 0),
                        'is_default' => (bool)($itemData['is_default'] ?? false),
                    ];

                    $item = $item ? tap($item, fn($i) => $i->update($itemPayload)) : $template->items()->create($itemPayload);
                    $keepIds[] = $item->id;

                    $item->ingredients()->delete();
                    if (!$this->noRecipeRequired && !empty($itemData['ingredients'])) {
                        foreach ($itemData['ingredients'] as $ri) {
                            if (!empty($ri['id']) && !empty($ri['quantity']) && (float)$ri['quantity'] > 0) {
                                $item->ingredients()->create([
                                    'ingredient_id' => $ri['id'],
                                    'quantity'      => (float)$ri['quantity'],
                                ]);
                            }
                        }
                    }
                }
                $template->items()->whereNotIn('id', $keepIds)->delete();

                return $template;
            });

            $template->load('items.ingredients.ingredient');

            return [
                'success' => true,
                'template' => [
                    'id' => (int) $template->id,
                    'name' => (string) $template->name,
                    'price_mode' => (string) $template->price_mode,
                    'max_select' => $template->max_select !== null ? (int) $template->max_select : null,
                    'is_required' => (bool) $template->is_required,
                    'no_recipe_required' => (bool) $template->no_recipe_required,
                    'items_count' => $template->items->count(),
                    'items' => $template->items->map(fn($i) => [
                        'id' => (int) $i->id,
                        'name' => (string) $i->name,
                        'price' => (float) $i->price == 0 ? '' : (float) $i->price,
                        'is_default' => (bool) $i->is_default,
                        'ingredients' => $i->ingredients->map(fn($ri) => [
                            'id' => (int) $ri->ingredient_id,
                            'name' => $ri->ingredient ? (string) $ri->ingredient->name : 'Unknown',
                            'unit' => $ri->ingredient ? (string) StockHelper::getAbbreviation($ri->ingredient->unit) : '',
                            'quantity' => (float) $ri->quantity,
                            'cost' => (float) ($ri->ingredient?->cost ?? 0),
                        ])->values(),
                    ])->values(),
                ],
            ];
        } catch (\Exception $e) {
            Log::error('OptionLibraryManagement.saveTemplate failed: ' . $e->getMessage());
            return ['success' => false, 'errors' => ['general' => 'Failed to save template: ' . $e->getMessage()]];
        }
    }

    public function confirmDeleteTemplate($id)
    {
        $template = OptionTemplate::findOrFail($id);
        $this->deleteTargetId = $template->id;
        $this->deleteTargetName = $template->name;
        $this->dispatch('open-modal', name: 'delete-template');
    }

    public function deleteTemplate($id = null)
    {
        // Alpine passes the id as an argument now — don't rely on the
        // server-side $this->deleteTargetId, which the JS never sets.
        $id = $id ?? $this->deleteTargetId;

        if (!$id) {
            return ['success' => false, 'errors' => ['general' => 'No template selected for deletion.']];
        }

        try {
            $template = OptionTemplate::findOrFail($id);
            $name = $template->name;
            $template->delete();

            return ['success' => true, 'name' => $name];
        } catch (\Exception $e) {
            Log::error('OptionLibraryManagement.deleteTemplate failed: ' . $e->getMessage());
            return ['success' => false, 'errors' => ['general' => 'Failed to delete template.']];
        }
    }

    private function resetForm()
    {
        $this->editTemplateId = null;
        $this->name = '';
        $this->priceMode = 'additive';
        $this->maxSelect = 1;
        $this->isRequired = false;
        $this->noRecipeRequired = false;
        $this->templateItems = [];
        $this->resetValidation();
    }

    public function render()
    {
        $allTemplates = OptionTemplate::query()
            ->with(['items.ingredients.ingredient'])
            ->orderBy('name', 'asc')
            ->get();

        $allIngredients = Ingredient::orderBy('name', 'asc')->get()->map(fn($i) => [
            'id'   => (int) $i->id,
            'name' => (string) $i->name,
            'unit' => (string) StockHelper::getAbbreviation($i->unit),
            'cost' => (float) ($i->cost ?? 0),
        ])->values()->toArray();

        return view('livewire.option-library-management', [
            'allTemplates' => $allTemplates,
            'allIngredients' => $allIngredients,
        ])->layout('layouts.app');
    }
}
