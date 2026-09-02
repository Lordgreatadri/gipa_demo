<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EnterpriseType;
use App\Models\Sector;
use App\Models\SubSector;
use App\Support\WorkflowPermissions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OpportunityReferenceDataController extends Controller
{
    public function index(Request $request, ?string $section = null): View
    {
        $this->authorizeManagement($request);
        abort_unless(in_array($section, [null, 'sectors', 'sub-sectors', 'enterprise-types'], true), 404);

        return view('admin.reference-data.index', [
            'section' => $section,
            'sectors' => Sector::query()
                ->select('id', 'uuid', 'code', 'name', 'description', 'is_active', 'sort_order')
                ->withCount(['subSectors', 'opportunities'])
                ->orderBy('sort_order')->orderBy('name')->get(),
            'subSectors' => SubSector::query()
                ->select('id', 'uuid', 'sector_id', 'code', 'name', 'description', 'is_active', 'sort_order')
                ->with('sector:id,uuid,name')
                ->withCount('opportunities')
                ->orderBy('sort_order')->orderBy('name')->get(),
            'enterpriseTypes' => EnterpriseType::query()
                ->select('id', 'uuid', 'code', 'name', 'description', 'is_active')
                ->withCount('opportunities')
                ->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $this->authorizeManagement($request);
        $model = $this->modelFor($type);
        $model->newQuery()->create($this->validatedData($request, $type));

        return back()->with('status', $this->labelFor($type).' created.');
    }

    public function update(Request $request, string $type, string $record): RedirectResponse
    {
        $this->authorizeManagement($request);
        $model = $this->findRecord($type, $record);
        $model->update($this->validatedData($request, $type, $model));

        return back()->with('status', $this->labelFor($type).' updated.');
    }

    public function destroy(Request $request, string $type, string $record): RedirectResponse
    {
        $this->authorizeManagement($request);
        $model = $this->findRecord($type, $record);

        $inUse = match ($type) {
            'sector' => $model->subSectors()->exists() || $model->opportunities()->exists(),
            'sub-sector', 'enterprise-type' => $model->opportunities()->exists(),
        };

        if ($inUse) {
            return back()->withErrors(['reference_data' => $this->labelFor($type).' is in use and cannot be deleted. Deactivate it instead.']);
        }

        $model->delete();

        return back()->with('status', $this->labelFor($type).' deleted.');
    }

    private function validatedData(Request $request, string $type, ?Model $record = null): array
    {
        $table = $this->modelFor($type)->getTable();
        $rules = [
            'code' => ['required', 'string', 'max:32', Rule::unique($table, 'code')->ignore($record)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
        ];

        if ($type === 'sector') {
            $rules['name'][] = Rule::unique($table, 'name')->ignore($record);
            $rules['sort_order'] = ['nullable', 'integer', 'min:0', 'max:65535'];
        }

        if ($type === 'sub-sector') {
            $sectorId = Sector::query()->where('uuid', $request->input('sector'))->value('id');
            $rules['sector'] = ['required', 'uuid', Rule::exists('sectors', 'uuid')];
            $rules['name'][] = Rule::unique($table, 'name')
                ->where(fn ($query) => $query->where('sector_id', $sectorId))
                ->ignore($record);
            $rules['sort_order'] = ['nullable', 'integer', 'min:0', 'max:65535'];
        }

        if ($type === 'enterprise-type') {
            $rules['name'][] = Rule::unique($table, 'name')->ignore($record);
        }

        $data = $request->validate($rules);
        $data['is_active'] = $request->boolean('is_active');

        if (array_key_exists('sort_order', $rules)) {
            $data['sort_order'] = $data['sort_order'] ?? 0;
        }

        if ($type === 'sub-sector') {
            $data['sector_id'] = Sector::query()->where('uuid', $data['sector'])->valueOrFail('id');
            unset($data['sector']);
        }

        return $data;
    }

    private function findRecord(string $type, string $uuid): Model
    {
        return $this->modelFor($type)->newQuery()->where('uuid', $uuid)->firstOrFail();
    }

    private function modelFor(string $type): Model
    {
        return match ($type) {
            'sector' => new Sector,
            'sub-sector' => new SubSector,
            'enterprise-type' => new EnterpriseType,
            default => abort(404),
        };
    }

    private function labelFor(string $type): string
    {
        return match ($type) {
            'sector' => 'Sector',
            'sub-sector' => 'Sub-sector',
            'enterprise-type' => 'Enterprise type',
            default => abort(404),
        };
    }

    private function authorizeManagement(Request $request): void
    {
        abort_unless($request->user()->can(WorkflowPermissions::OPPORTUNITY_SUBMIT), 403);
    }
}
