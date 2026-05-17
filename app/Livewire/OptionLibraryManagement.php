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

    // ── Panel Actions ─────────────────────────────────────────────
    public function showCreate()
    {
        $this->resetForm();
        $this->panel = 'form';
        $this->mode = 'create';
    }

    public function showEdit($id)
    {
        $template = OptionTemplate::with('items')->findOrFail($id);
        $this->editTemplateId = $template->id;
        $this->name = $template->name;
        $this->priceMode = $template->price_mode;
        $this->isRequired = (bool)$template->is_required;
        
        $this->templateItems = collect($template->items)->map(fn($item) => [
            'id'         => $item instanceof OptionTemplateItem ? $item->id : ($item['id'] ?? null),
            'name'       => $item instanceof OptionTemplateItem ? $item->name : ($item['name'] ?? ''),
            'price'      => $item instanceof OptionTemplateItem ? ($item->price == 0 ? '' : $item->price) : ($item['price'] == 0 ? '' : $item['price']),
            'is_default' => (bool)($item instanceof OptionTemplateItem ? $item->is_default : ($item['is_default'] ?? false)),
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
