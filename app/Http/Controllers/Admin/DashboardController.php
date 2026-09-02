<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\InvestorInquiry;
use App\Models\InvestorOnboardingCase;
use App\Models\Opportunity;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $opportunityMetrics = Opportunity::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN workflow_status = ? THEN 1 ELSE 0 END) as pending', [Opportunity::WORKFLOW_PENDING_APPROVAL])
            ->selectRaw('SUM(CASE WHEN workflow_status IN (?, ?, ?) THEN 1 ELSE 0 END) as investable', [Opportunity::WORKFLOW_APPROVED, Opportunity::WORKFLOW_ACTIVE, Opportunity::WORKFLOW_COMPLETED])
            ->selectRaw('SUM(CASE WHEN workflow_status = ? AND sla_due_at < ? THEN 1 ELSE 0 END) as overdue', [Opportunity::WORKFLOW_PENDING_APPROVAL, now()])
            ->first();
        $districtMetrics = District::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN workflow_status = ? THEN 1 ELSE 0 END) as reviewing', [District::STATUS_UNDER_REVIEW])
            ->selectRaw('SUM(CASE WHEN workflow_status = ? AND sla_due_at < ? THEN 1 ELSE 0 END) as overdue', [District::STATUS_UNDER_REVIEW, now()])
            ->first();
        $onboardingMetrics = InvestorOnboardingCase::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END) as reviewing', [InvestorOnboardingCase::STATUS_SUBMITTED, InvestorOnboardingCase::STATUS_UNDER_REVIEW])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as approved', [InvestorOnboardingCase::STATUS_APPROVED])
            ->selectRaw('SUM(CASE WHEN status IN (?, ?) AND sla_due_at < ? THEN 1 ELSE 0 END) as overdue', [InvestorOnboardingCase::STATUS_SUBMITTED, InvestorOnboardingCase::STATUS_UNDER_REVIEW, now()])
            ->first();
        $opportunityStatus = Opportunity::query()->selectRaw('workflow_status, COUNT(*) as total')->groupBy('workflow_status')->pluck('total', 'workflow_status');
        $onboardingStatus = InvestorOnboardingCase::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $monthlyOnboarding = InvestorOnboardingCase::query()
            ->select('submitted_at')
            ->whereNotNull('submitted_at')
            ->where('submitted_at', '>=', now()->startOfMonth()->subMonths(5))
            ->get()
            ->groupBy(fn (InvestorOnboardingCase $case) => $case->submitted_at->format('Y-m'));
        $monthKeys = collect(range(5, 0))->map(fn (int $months) => now()->startOfMonth()->subMonths($months));

        return view('admin.dashboard', [
            'metrics' => [
                'opportunities' => $opportunityMetrics->total,
                'investable_opportunities' => $opportunityMetrics->investable,
                'pending_opportunities' => $opportunityMetrics->pending,
                'districts' => $districtMetrics->total,
                'districts_under_review' => $districtMetrics->reviewing,
                'onboarding_cases' => $onboardingMetrics->total,
                'onboarding_review' => $onboardingMetrics->reviewing,
                'verified_investors' => $onboardingMetrics->approved,
                'open_inquiries' => InvestorInquiry::query()->where('status', InvestorInquiry::STATUS_NEW)->count(),
                'sla_breaches' => $opportunityMetrics->overdue + $districtMetrics->overdue + $onboardingMetrics->overdue,
            ],
            'charts' => [
                'opportunity_status' => $this->statusChart($opportunityStatus, [
                    Opportunity::WORKFLOW_DRAFT, Opportunity::WORKFLOW_PENDING_APPROVAL, Opportunity::WORKFLOW_APPROVED,
                    Opportunity::WORKFLOW_ACTIVE, Opportunity::WORKFLOW_COMPLETED, Opportunity::WORKFLOW_CANCELLED,
                ]),
                'onboarding_status' => $this->statusChart($onboardingStatus, [
                    InvestorOnboardingCase::STATUS_DRAFT, InvestorOnboardingCase::STATUS_SUBMITTED, InvestorOnboardingCase::STATUS_UNDER_REVIEW,
                    InvestorOnboardingCase::STATUS_ACTION_REQUIRED, InvestorOnboardingCase::STATUS_APPROVED, InvestorOnboardingCase::STATUS_REJECTED,
                ]),
                'onboarding_months' => [
                    'labels' => $monthKeys->map->format('M Y')->all(),
                    'values' => $monthKeys->map(fn ($month) => $monthlyOnboarding->get($month->format('Y-m'), collect())->count())->all(),
                ],
            ],
            'reviewQueue' => Opportunity::query()
                ->select('id', 'uuid', 'title', 'district_id', 'reviewer_id', 'workflow_status', 'sla_due_at', 'updated_at')
                ->with(['district:id,name', 'reviewer:id,name'])
                ->where('workflow_status', Opportunity::WORKFLOW_PENDING_APPROVAL)
                ->orderByRaw('sla_due_at IS NULL, sla_due_at')
                ->limit(8)
                ->get(),
        ]);
    }

    private function statusChart($counts, array $statuses): array
    {
        return [
            'labels' => collect($statuses)->map(fn (string $status) => str($status)->replace('_', ' ')->title()->toString())->all(),
            'values' => collect($statuses)->map(fn (string $status) => (int) ($counts[$status] ?? 0))->all(),
        ];
    }
}