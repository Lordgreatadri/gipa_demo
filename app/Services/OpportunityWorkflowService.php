<?php

namespace App\Services;

use App\Models\Opportunity;
use App\Models\User;
use App\Notifications\WorkflowTransitionNotification;
use App\Support\WorkflowPermissions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class OpportunityWorkflowService
{
    public function submit(Opportunity $opportunity, User $actor, User $reviewer): Opportunity
    {
        return $this->transition($opportunity, $actor, 'submit', Opportunity::WORKFLOW_DRAFT, Opportunity::WORKFLOW_PENDING_APPROVAL, WorkflowPermissions::OPPORTUNITY_SUBMIT, null, $reviewer, true);
    }

    public function reassign(Opportunity $opportunity, User $actor, User $reviewer, ?string $reason = null): Opportunity
    {
        return $this->transition($opportunity, $actor, 'reassign', Opportunity::WORKFLOW_PENDING_APPROVAL, Opportunity::WORKFLOW_PENDING_APPROVAL, WorkflowPermissions::OPPORTUNITY_REASSIGN, $reason, $reviewer, true);
    }

    public function approve(Opportunity $opportunity, User $actor, ?string $reason = null): Opportunity
    {
        return $this->transition($opportunity, $actor, 'approve', Opportunity::WORKFLOW_PENDING_APPROVAL, Opportunity::WORKFLOW_APPROVED, WorkflowPermissions::OPPORTUNITY_REVIEW, $reason);
    }

    public function reject(Opportunity $opportunity, User $actor, string $reason): Opportunity
    {
        return $this->transition($opportunity, $actor, 'reject', Opportunity::WORKFLOW_PENDING_APPROVAL, Opportunity::WORKFLOW_DRAFT, WorkflowPermissions::OPPORTUNITY_REVIEW, $reason);
    }

    public function activate(Opportunity $opportunity, User $actor): Opportunity
    {
        return $this->transition($opportunity, $actor, 'activate', Opportunity::WORKFLOW_APPROVED, Opportunity::WORKFLOW_ACTIVE, WorkflowPermissions::OPPORTUNITY_LIFECYCLE);
    }

    public function complete(Opportunity $opportunity, User $actor): Opportunity
    {
        return $this->transition($opportunity, $actor, 'complete', Opportunity::WORKFLOW_ACTIVE, Opportunity::WORKFLOW_COMPLETED, WorkflowPermissions::OPPORTUNITY_LIFECYCLE);
    }

    public function cancel(Opportunity $opportunity, User $actor, string $reason): Opportunity
    {
        if (! in_array($opportunity->workflow_status, [Opportunity::WORKFLOW_APPROVED, Opportunity::WORKFLOW_ACTIVE], true)) {
            throw ValidationException::withMessages(['workflow' => 'Only approved or active opportunities may be cancelled.']);
        }

        return $this->transition($opportunity, $actor, 'cancel', $opportunity->workflow_status, Opportunity::WORKFLOW_CANCELLED, WorkflowPermissions::OPPORTUNITY_LIFECYCLE, $reason);
    }

    private function transition(
        Opportunity $opportunity,
        User $actor,
        string $action,
        string $expectedStatus,
        string $nextStatus,
        string $permission,
        ?string $reason = null,
        ?User $reviewer = null,
        bool $setSla = false,
    ): Opportunity {
        Gate::forUser($actor)->authorize($permission);

        [$opportunity, $recipient] = DB::transaction(function () use ($opportunity, $actor, $action, $expectedStatus, $nextStatus, $reason, $reviewer, $setSla): array {
            $opportunity = Opportunity::query()->lockForUpdate()->findOrFail($opportunity->id);
            $this->assertStatus($opportunity, $expectedStatus);
            $before = $opportunity->only(['workflow_status', 'reviewer_id', 'sla_due_at', 'submitted_at', 'approved_at', 'published_at', 'version']);

            $opportunity->forceFill([
                'workflow_status' => $nextStatus,
                'reviewer_id' => $reviewer?->id ?? ($nextStatus === Opportunity::WORKFLOW_DRAFT ? null : $opportunity->reviewer_id),
                'sla_due_at' => $setSla ? now()->addHours(config('iomp.workflow.opportunity_review_hours')) : null,
                'decision_reason' => $reason,
                'submitted_at' => $action === 'submit' ? now() : $opportunity->submitted_at,
                'approved_at' => $action === 'approve' ? now() : $opportunity->approved_at,
                'published_at' => $action === 'activate' ? now() : $opportunity->published_at,
                'updated_by' => $actor->id,
                'version' => $opportunity->version + 1,
            ])->save();

            $event = $opportunity->workflowEvents()->create([
                'actor_id' => $actor->id,
                'assigned_to' => $reviewer?->id,
                'action' => $action,
                'from_status' => $expectedStatus,
                'to_status' => $nextStatus,
                'reason' => $reason,
            ]);

            $this->audit($opportunity, $actor, $action, $before, $opportunity->only(array_keys($before)), $event);

            return [$opportunity->fresh(), $reviewer ?? $opportunity->creator];
        });

        $this->notify($recipient, $actor, $opportunity, $action, $reason);

        return $opportunity;
    }

    private function assertStatus(Opportunity $opportunity, string $expected): void
    {
        if ($opportunity->workflow_status !== $expected) {
            throw ValidationException::withMessages(['workflow' => "Opportunity must be {$expected} for this action."]);
        }
    }

    private function audit(Opportunity $opportunity, User $actor, string $action, array $before, array $after, Model $event): void
    {
        activity('workflow')
            ->causedBy($actor)
            ->performedOn($opportunity)
            ->event($action)
            ->withProperties(['before' => $before, 'after' => $after, 'workflow_event_uuid' => $event->uuid])
            ->log("Opportunity {$action}");
    }

    private function notify(?User $recipient, User $actor, Opportunity $opportunity, string $action, ?string $reason): void
    {
        if ($recipient && ! $recipient->is($actor)) {
            Notification::send($recipient, new WorkflowTransitionNotification('opportunity', $opportunity->title, $action, $opportunity->workflow_status, $reason));
        }
    }
}
