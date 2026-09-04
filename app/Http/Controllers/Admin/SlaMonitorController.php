<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\InvestorOnboardingCase;
use App\Models\Opportunity;
use App\Support\AuditPermissions;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class SlaMonitorController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can(AuditPermissions::SLA_VIEW), 403);

        $now = now();
        $atRiskWindow = $now->copy()->addDay();

        $domains = [
            $this->buildDomain(
                key: 'opportunities',
                label: 'Opportunity approvals',
                now: $now,
                atRiskWindow: $atRiskWindow,
                records: Opportunity::query()
                    ->where('workflow_status', Opportunity::WORKFLOW_PENDING_APPROVAL)
                    ->with(['district:id,name', 'reviewer:id,name'])
                    ->orderByRaw('sla_due_at is null, sla_due_at asc')
                    ->limit(200)
                    ->get(['id', 'uuid', 'title', 'district_id', 'reviewer_id', 'sla_due_at']),
                map: fn (Opportunity $item) => [
                    'title' => $item->title,
                    'subtitle' => $item->district?->name,
                    'owner' => $item->reviewer?->name ?? 'Unassigned',
                    'sla_due_at' => $item->sla_due_at,
                    'url' => route('staff.opportunities.show', $item),
                ],
            ),
            $this->buildDomain(
                key: 'districts',
                label: 'District publication',
                now: $now,
                atRiskWindow: $atRiskWindow,
                records: District::query()
                    ->where('workflow_status', District::STATUS_UNDER_REVIEW)
                    ->with(['region:id,name', 'reviewer:id,name'])
                    ->orderByRaw('sla_due_at is null, sla_due_at asc')
                    ->limit(200)
                    ->get(['id', 'uuid', 'name', 'region_id', 'reviewer_id', 'sla_due_at']),
                map: fn (District $item) => [
                    'title' => $item->name,
                    'subtitle' => $item->region?->name,
                    'owner' => $item->reviewer?->name ?? 'Unassigned',
                    'sla_due_at' => $item->sla_due_at,
                    'url' => route('staff.districts.show', $item),
                ],
            ),
            $this->buildDomain(
                key: 'onboarding',
                label: 'Investor onboarding',
                now: $now,
                atRiskWindow: $atRiskWindow,
                records: InvestorOnboardingCase::query()
                    ->whereIn('status', [InvestorOnboardingCase::STATUS_SUBMITTED, InvestorOnboardingCase::STATUS_UNDER_REVIEW])
                    ->with(['assignee:id,name'])
                    ->orderByRaw('sla_due_at is null, sla_due_at asc')
                    ->limit(200)
                    ->get(['id', 'uuid', 'reference', 'assigned_to', 'sla_due_at']),
                map: fn (InvestorOnboardingCase $item) => [
                    'title' => $item->reference,
                    'subtitle' => 'KYC review',
                    'owner' => $item->assignee?->name ?? 'Unassigned',
                    'sla_due_at' => $item->sla_due_at,
                    'url' => route('staff.investors.show', $item),
                ],
            ),
        ];

        $summary = [
            'breached' => array_sum(array_column(array_column($domains, 'metrics'), 'breached')),
            'at_risk' => array_sum(array_column(array_column($domains, 'metrics'), 'at_risk')),
            'in_review' => array_sum(array_column(array_column($domains, 'metrics'), 'in_review')),
            'on_track' => array_sum(array_column(array_column($domains, 'metrics'), 'on_track')),
        ];

        return view('admin.sla.index', [
            'domains' => $domains,
            'summary' => $summary,
        ]);
    }

    /**
     * Classify a domain's in-review records into breached, at-risk and on-track
     * buckets against the SLA deadline for the monitoring dashboard.
     */
    private function buildDomain(string $key, string $label, Carbon $now, Carbon $atRiskWindow, Collection $records, callable $map): array
    {
        $items = $records->map($map);

        $breaches = $items
            ->filter(fn (array $item) => $item['sla_due_at'] !== null && $item['sla_due_at']->lt($now))
            ->values();

        $atRisk = $items
            ->filter(fn (array $item) => $item['sla_due_at'] !== null
                && $item['sla_due_at']->gte($now)
                && $item['sla_due_at']->lte($atRiskWindow))
            ->values();

        return [
            'key' => $key,
            'label' => $label,
            'metrics' => [
                'in_review' => $items->count(),
                'breached' => $breaches->count(),
                'at_risk' => $atRisk->count(),
                'on_track' => max($items->count() - $breaches->count() - $atRisk->count(), 0),
            ],
            'breaches' => $breaches,
            'at_risk' => $atRisk,
        ];
    }
}
