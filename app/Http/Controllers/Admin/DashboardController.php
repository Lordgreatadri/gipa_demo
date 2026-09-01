<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\InvestorInquiry;
use App\Models\Opportunity;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'metrics' => [
                'opportunities' => Opportunity::query()->count(),
                'pending_opportunities' => Opportunity::query()->where('workflow_status', Opportunity::WORKFLOW_PENDING_APPROVAL)->count(),
                'districts' => District::query()->count(),
                'districts_under_review' => District::query()->where('workflow_status', District::STATUS_UNDER_REVIEW)->count(),
                'open_inquiries' => InvestorInquiry::query()->where('status', InvestorInquiry::STATUS_NEW)->count(),
                'sla_breaches' => Opportunity::query()->where('sla_due_at', '<', now())->where('workflow_status', Opportunity::WORKFLOW_PENDING_APPROVAL)->count()
                    + District::query()->where('sla_due_at', '<', now())->where('workflow_status', District::STATUS_UNDER_REVIEW)->count(),
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
}