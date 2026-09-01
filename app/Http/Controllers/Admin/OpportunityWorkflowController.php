<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OpportunityRecordRequest;
use App\Http\Requests\Admin\WorkflowTransitionRequest;
use App\Models\District;
use App\Models\EnterpriseType;
use App\Models\Opportunity;
use App\Models\Sector;
use App\Models\SubSector;
use App\Models\User;
use App\Services\OpportunityWorkflowService;
use App\Support\WorkflowPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OpportunityWorkflowController extends Controller
{
    public function index(): View
    {
        $metrics = Opportunity::query()
            ->leftJoin('opportunity_financials', 'opportunity_financials.opportunity_id', '=', 'opportunities.id')
            ->selectRaw('COUNT(DISTINCT opportunities.id) as total')
            ->selectRaw("COALESCE(SUM(CASE WHEN opportunity_financials.currency = 'GHS' THEN opportunity_financials.amount ELSE 0 END), 0) as pipeline_value")
            ->selectRaw('COUNT(DISTINCT CASE WHEN opportunities.workflow_status = ? THEN opportunities.id END) as pending', [Opportunity::WORKFLOW_PENDING_APPROVAL])
            ->selectRaw('COUNT(DISTINCT CASE WHEN opportunities.workflow_status = ? THEN opportunities.id END) as active', [Opportunity::WORKFLOW_ACTIVE])
            ->selectRaw('COUNT(DISTINCT opportunities.district_id) as districts')
            ->selectRaw('COUNT(DISTINCT CASE WHEN opportunities.workflow_status = ? AND opportunities.sla_due_at < ? THEN opportunities.id END) as overdue', [Opportunity::WORKFLOW_PENDING_APPROVAL, now()])
            ->first();

        return view('admin.opportunities.index', [
            'metrics' => $metrics->toArray(),
            'opportunities' => Opportunity::query()
                ->select('id', 'uuid', 'title', 'district_id', 'sector_id', 'reviewer_id', 'workflow_status', 'sla_due_at', 'updated_at')
                ->with(['district:id,name', 'sector:id,name', 'reviewer:id,name'])
                ->when(request('status'), fn ($query, $status) => $query->where('workflow_status', $status))
                ->latest('updated_at')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->can(WorkflowPermissions::OPPORTUNITY_SUBMIT), 403);

        return view('admin.opportunities.form', $this->formData(new Opportunity));
    }

    public function store(OpportunityRecordRequest $request): RedirectResponse
    {
        $opportunity = Opportunity::create($this->opportunityData($request) + [
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return to_route('staff.opportunities.show', $opportunity)->with('status', 'Opportunity draft created.');
    }

    public function show(Opportunity $opportunity): View
    {
        $opportunity->load([
            'district.region', 'sector', 'subSector', 'enterpriseType', 'financial.investmentStructure',
            'contacts', 'reviewer', 'creator', 'media',
            'workflowEvents' => fn ($query) => $query->with(['actor:id,name', 'assignee:id,name'])->latest('occurred_at'),
        ]);

        return view('admin.opportunities.show', [
            'opportunity' => $opportunity,
            'reviewers' => $this->reviewers(),
        ]);
    }

    public function edit(Request $request, Opportunity $opportunity): View
    {
        abort_unless($request->user()->can(WorkflowPermissions::OPPORTUNITY_SUBMIT), 403);
        abort_unless($opportunity->workflow_status === Opportunity::WORKFLOW_DRAFT, 403);

        return view('admin.opportunities.form', $this->formData($opportunity));
    }

    public function update(OpportunityRecordRequest $request, Opportunity $opportunity): RedirectResponse
    {
        $opportunity->update($this->opportunityData($request) + ['updated_by' => $request->user()->id]);

        return to_route('staff.opportunities.show', $opportunity)->with('status', 'Opportunity draft updated.');
    }

    public function destroy(Request $request, Opportunity $opportunity): RedirectResponse
    {
        abort_unless($request->user()->can(WorkflowPermissions::OPPORTUNITY_SUBMIT), 403);
        abort_unless($opportunity->workflow_status === Opportunity::WORKFLOW_DRAFT, 403);

        $opportunity->delete();

        return to_route('staff.opportunities.index')->with('status', 'Opportunity draft deleted.');
    }

    public function transition(
        WorkflowTransitionRequest $request,
        Opportunity $opportunity,
        string $action,
        OpportunityWorkflowService $workflow,
    ): RedirectResponse {
        $reviewer = $request->validated('reviewer')
            ? User::query()->where('uuid', $request->validated('reviewer'))->firstOrFail()
            : null;
        $reason = $request->validated('reason');

        $opportunity = match ($action) {
            'submit' => $workflow->submit($opportunity, $request->user(), $reviewer),
            'reassign' => $workflow->reassign($opportunity, $request->user(), $reviewer, $reason),
            'approve' => $workflow->approve($opportunity, $request->user(), $reason),
            'reject' => $workflow->reject($opportunity, $request->user(), $reason),
            'activate' => $workflow->activate($opportunity, $request->user()),
            'complete' => $workflow->complete($opportunity, $request->user()),
            'cancel' => $workflow->cancel($opportunity, $request->user(), $reason),
            default => throw ValidationException::withMessages(['workflow' => 'Unknown workflow action.']),
        };

        return to_route('staff.opportunities.show', $opportunity)->with('status', 'Workflow action completed.');
    }

    private function reviewers()
    {
        return User::query()
            ->select('id', 'uuid', 'name')
            ->where('account_type', User::ACCOUNT_STAFF)
            ->where('status', User::STATUS_ACTIVE)
            ->permission(['opportunities.review', 'opportunities.reassign'])
            ->orderBy('name')
            ->get();
    }

    private function formData(Opportunity $opportunity): array
    {
        return [
            'opportunity' => $opportunity,
            'districts' => District::query()->select('id', 'uuid', 'name')->orderBy('name')->get(),
            'sectors' => Sector::query()->select('id', 'uuid', 'name')->where('is_active', true)->orderBy('name')->get(),
            'subSectors' => SubSector::query()
                ->select('sub_sectors.id', 'sub_sectors.uuid', 'sub_sectors.name', 'sectors.uuid as sector_uuid')
                ->join('sectors', 'sectors.id', '=', 'sub_sectors.sector_id')
                ->where('sub_sectors.is_active', true)
                ->orderBy('sub_sectors.name')
                ->get(),
            'enterpriseTypes' => EnterpriseType::query()->select('id', 'uuid', 'name')->where('is_active', true)->orderBy('name')->get(),
        ];
    }

    private function opportunityData(OpportunityRecordRequest $request): array
    {
        $data = $request->safe()->except(['district', 'sector', 'sub_sector', 'enterprise_type']);
        $data['district_id'] = District::query()->where('uuid', $request->validated('district'))->valueOrFail('id');
        $data['sector_id'] = Sector::query()->where('uuid', $request->validated('sector'))->valueOrFail('id');
        $data['sub_sector_id'] = $request->validated('sub_sector')
            ? SubSector::query()->where('uuid', $request->validated('sub_sector'))->valueOrFail('id')
            : null;
        $data['enterprise_type_id'] = EnterpriseType::query()->where('uuid', $request->validated('enterprise_type'))->valueOrFail('id');

        return $data;
    }
}
