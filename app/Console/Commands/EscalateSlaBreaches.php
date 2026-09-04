<?php

namespace App\Console\Commands;

use App\Models\District;
use App\Models\InvestorOnboardingCase;
use App\Models\Opportunity;
use App\Notifications\SlaBreachEscalation;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Finds in-review work items past their SLA deadline and escalates each breach
 * once to the assigned reviewer. The sla_escalated_at column guards against
 * re-notifying on subsequent scheduler runs for the same breach.
 */
class EscalateSlaBreaches extends Command
{
    protected $signature = 'sla:escalate';

    protected $description = 'Notify assigned reviewers when work items breach their SLA deadline';

    public function handle(): int
    {
        $now = now();
        $escalated = 0;

        $escalated += $this->escalate(
            Opportunity::query()
                ->where('workflow_status', Opportunity::WORKFLOW_PENDING_APPROVAL)
                ->whereNotNull('reviewer_id')
                ->whereNotNull('sla_due_at')
                ->where('sla_due_at', '<', $now)
                ->where(fn ($query) => $query
                    ->whereNull('sla_escalated_at')
                    ->orWhereColumn('sla_due_at', '>', 'sla_escalated_at'))
                ->with('reviewer')
                ->lazyById(),
            'opportunity approval',
            fn (Opportunity $item) => $item->reviewer,
            fn (Opportunity $item) => $item->title,
            fn (Opportunity $item) => route('staff.opportunities.show', $item),
            $now,
        );

        $escalated += $this->escalate(
            District::query()
                ->where('workflow_status', District::STATUS_UNDER_REVIEW)
                ->whereNotNull('reviewer_id')
                ->whereNotNull('sla_due_at')
                ->where('sla_due_at', '<', $now)
                ->where(fn ($query) => $query
                    ->whereNull('sla_escalated_at')
                    ->orWhereColumn('sla_due_at', '>', 'sla_escalated_at'))
                ->with('reviewer')
                ->lazyById(),
            'district publication',
            fn (District $item) => $item->reviewer,
            fn (District $item) => $item->name,
            fn (District $item) => route('staff.districts.show', $item),
            $now,
        );

        $escalated += $this->escalate(
            InvestorOnboardingCase::query()
                ->whereIn('status', [InvestorOnboardingCase::STATUS_SUBMITTED, InvestorOnboardingCase::STATUS_UNDER_REVIEW])
                ->whereNotNull('assigned_to')
                ->whereNotNull('sla_due_at')
                ->where('sla_due_at', '<', $now)
                ->where(fn ($query) => $query
                    ->whereNull('sla_escalated_at')
                    ->orWhereColumn('sla_due_at', '>', 'sla_escalated_at'))
                ->with('assignee')
                ->lazyById(),
            'investor onboarding',
            fn (InvestorOnboardingCase $item) => $item->assignee,
            fn (InvestorOnboardingCase $item) => $item->reference,
            fn (InvestorOnboardingCase $item) => route('staff.investors.show', $item),
            $now,
        );

        $this->info("Escalated {$escalated} SLA breach(es).");

        return self::SUCCESS;
    }

    /**
     * @param  iterable<int, Model>  $items
     */
    private function escalate(iterable $items, string $domain, callable $owner, callable $title, callable $url, Carbon $now): int
    {
        $count = 0;

        foreach ($items as $item) {
            $reviewer = $owner($item);

            if ($reviewer === null) {
                continue;
            }

            $reviewer->notify(new SlaBreachEscalation(
                domain: $domain,
                title: $title($item),
                dueAt: $item->sla_due_at,
                url: $url($item),
            ));

            $item->forceFill(['sla_escalated_at' => $now])->save();
            $count++;
        }

        return $count;
    }
}
