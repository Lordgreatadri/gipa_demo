<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DistrictRecordRequest;
use App\Http\Requests\Admin\WorkflowTransitionRequest;
use App\Models\District;
use App\Models\Region;
use App\Models\User;
use App\Services\DistrictWorkflowService;
use App\Support\WorkflowPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DistrictWorkflowController extends Controller
{
    public function index(): View
    {
        return $this->workspace(false, true);
    }

    public function overview(): View
    {
        return $this->workspace(true, false);
    }

    private function workspace(bool $showOverview, bool $showList): View
    {
        $metrics = District::query()->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN workflow_status = ? THEN 1 ELSE 0 END) as published', [District::STATUS_PUBLISHED])
            ->selectRaw('SUM(CASE WHEN workflow_status = ? THEN 1 ELSE 0 END) as under_review', [District::STATUS_UNDER_REVIEW])
            ->selectRaw('COALESCE(SUM(population), 0) as population')
            ->selectRaw('AVG(readiness_score) as readiness')
            ->selectRaw('AVG(infrastructure_quality_score) as infrastructure')
            ->first();
        $statusCounts = District::query()->selectRaw('workflow_status, COUNT(*) as total')->groupBy('workflow_status')->pluck('total', 'workflow_status');
        $readinessScores = District::query()->whereNotNull('readiness_score')->pluck('readiness_score');
        $readinessBands = collect([
            'Early stage (0-39)' => [0, 40],
            'Developing (40-59)' => [40, 60],
            'Investment ready (60-79)' => [60, 80],
            'High readiness (80-100)' => [80, 101],
        ])->map(fn (array $range) => $readinessScores->filter(fn ($score) => $score >= $range[0] && $score < $range[1])->count());
        $regionPerformance = District::query()
            ->join('regions', 'regions.id', '=', 'districts.region_id')
            ->selectRaw('regions.name, AVG(districts.readiness_score) as readiness, COALESCE(SUM(districts.population), 0) as population')
            ->groupBy('regions.id', 'regions.name')
            ->orderByDesc('readiness')
            ->get();

        return view('admin.districts.index', [
            'showOverview' => $showOverview,
            'showList' => $showList,
            'metrics' => $metrics->toArray(),
            'charts' => [
                'status' => [
                    'labels' => ['Draft', 'Under review', 'Published'],
                    'values' => [(int) ($statusCounts[District::STATUS_DRAFT] ?? 0), (int) ($statusCounts[District::STATUS_UNDER_REVIEW] ?? 0), (int) ($statusCounts[District::STATUS_PUBLISHED] ?? 0)],
                ],
                'readiness' => ['labels' => $readinessBands->keys()->all(), 'values' => $readinessBands->values()->all()],
                'regions' => [
                    'labels' => $regionPerformance->pluck('name')->all(),
                    'readiness' => $regionPerformance->map(fn ($region) => round((float) $region->readiness, 1))->all(),
                    'population' => $regionPerformance->map(fn ($region) => round((float) $region->population / 1000000, 2))->all(),
                ],
            ],
            'districts' => District::query()
                ->select('id', 'uuid', 'name', 'code', 'region_id', 'reviewer_id', 'workflow_status', 'sla_due_at', 'updated_at')
                ->with(['region:id,name', 'reviewer:id,name'])
                ->when(request('status'), fn ($query, $status) => $query->where('workflow_status', $status))
                ->orderBy('name')
                ->paginate(30)
                ->withQueryString(),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->can(WorkflowPermissions::DISTRICT_SUBMIT), 403);

        return view('admin.districts.form', [
            'district' => new District,
            'regions' => Region::query()->select('id', 'uuid', 'name')->orderBy('name')->get(),
        ]);
    }

    public function store(DistrictRecordRequest $request): RedirectResponse
    {
        $district = District::create($this->districtData($request) + [
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return to_route('staff.districts.show', $district)->with('status', 'District draft created.');
    }

    public function show(District $district): View
    {
        $district->load([
            'region', 'reviewer', 'creator',
            'workflowEvents' => fn ($query) => $query->with(['actor:id,name', 'assignee:id,name'])->latest('occurred_at'),
        ])->loadCount('opportunities');

        return view('admin.districts.show', [
            'district' => $district,
            'reviewers' => $this->reviewers(),
        ]);
    }

    public function edit(Request $request, District $district): View
    {
        abort_unless($request->user()->can(WorkflowPermissions::DISTRICT_SUBMIT), 403);
        abort_unless($district->workflow_status === District::STATUS_DRAFT, 403);

        return view('admin.districts.form', [
            'district' => $district,
            'regions' => Region::query()->select('id', 'uuid', 'name')->orderBy('name')->get(),
        ]);
    }

    public function update(DistrictRecordRequest $request, District $district): RedirectResponse
    {
        $district->update($this->districtData($request) + ['updated_by' => $request->user()->id]);

        return to_route('staff.districts.show', $district)->with('status', 'District draft updated.');
    }

    public function destroy(Request $request, District $district): RedirectResponse
    {
        abort_unless($request->user()->can(WorkflowPermissions::DISTRICT_SUBMIT), 403);
        abort_unless($district->workflow_status === District::STATUS_DRAFT, 403);

        if ($district->opportunities()->exists()) {
            return back()->withErrors(['district' => 'This district cannot be deleted while opportunities reference it.']);
        }

        $district->delete();

        return to_route('staff.districts.index')->with('status', 'District draft deleted.');
    }

    public function transition(
        WorkflowTransitionRequest $request,
        District $district,
        string $action,
        DistrictWorkflowService $workflow,
    ): RedirectResponse {
        $reviewer = $request->validated('reviewer')
            ? User::query()->where('uuid', $request->validated('reviewer'))->firstOrFail()
            : null;
        $reason = $request->validated('reason');

        $district = match ($action) {
            'submit' => $workflow->submit($district, $request->user(), $reviewer),
            'reassign' => $workflow->reassign($district, $request->user(), $reviewer, $reason),
            'reject' => $workflow->reject($district, $request->user(), $reason),
            'publish' => $workflow->publish($district, $request->user(), $reason),
            default => throw ValidationException::withMessages(['workflow' => 'Unknown workflow action.']),
        };

        return to_route('staff.districts.show', $district)->with('status', 'Workflow action completed.');
    }

    private function reviewers()
    {
        return User::query()
            ->select('id', 'uuid', 'name')
            ->where('account_type', User::ACCOUNT_STAFF)
            ->where('status', User::STATUS_ACTIVE)
            ->permission(['districts.review', 'districts.reassign'])
            ->orderBy('name')
            ->get();
    }

    private function districtData(DistrictRecordRequest $request): array
    {
        $data = $request->safe()->except('region');
        $data['region_id'] = Region::query()->where('uuid', $request->validated('region'))->valueOrFail('id');

        return $data;
    }
}
