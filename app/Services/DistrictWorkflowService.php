<?php

namespace App\Services;

use App\Models\District;
use App\Models\User;
use App\Notifications\WorkflowTransitionNotification;
use App\Support\WorkflowPermissions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class DistrictWorkflowService
{
    public function submit(District $district, User $actor, User $reviewer): District
    {
        return $this->transition($district, $actor, 'submit', District::STATUS_DRAFT, District::STATUS_UNDER_REVIEW, WorkflowPermissions::DISTRICT_SUBMIT, null, $reviewer, true);
    }

    public function reassign(District $district, User $actor, User $reviewer, ?string $reason = null): District
    {
        return $this->transition($district, $actor, 'reassign', District::STATUS_UNDER_REVIEW, District::STATUS_UNDER_REVIEW, WorkflowPermissions::DISTRICT_REASSIGN, $reason, $reviewer, true);
    }

    public function reject(District $district, User $actor, string $reason): District
    {
        return $this->transition($district, $actor, 'reject', District::STATUS_UNDER_REVIEW, District::STATUS_DRAFT, WorkflowPermissions::DISTRICT_REVIEW, $reason);
    }

    public function publish(District $district, User $actor, ?string $reason = null): District
    {
        return $this->transition($district, $actor, 'publish', District::STATUS_UNDER_REVIEW, District::STATUS_PUBLISHED, WorkflowPermissions::DISTRICT_REVIEW, $reason);
    }

    private function transition(
        District $district,
        User $actor,
        string $action,
        string $expectedStatus,
        string $nextStatus,
        string $permission,
        ?string $reason = null,
        ?User $reviewer = null,
        bool $setSla = false,
    ): District {
        Gate::forUser($actor)->authorize($permission);

        [$district, $recipient] = DB::transaction(function () use ($district, $actor, $action, $expectedStatus, $nextStatus, $reason, $reviewer, $setSla): array {
            $district = District::query()->lockForUpdate()->findOrFail($district->id);
            $this->assertStatus($district, $expectedStatus);
            $before = $district->only(['workflow_status', 'reviewer_id', 'sla_due_at', 'published_at', 'version']);

            $district->forceFill([
                'workflow_status' => $nextStatus,
                'reviewer_id' => $reviewer?->id ?? ($nextStatus === District::STATUS_DRAFT ? null : $district->reviewer_id),
                'sla_due_at' => $setSla ? now()->addHours(config('iomp.workflow.district_review_hours')) : null,
                'review_reason' => $reason,
                'published_at' => $nextStatus === District::STATUS_PUBLISHED ? now() : null,
                'updated_by' => $actor->id,
                'version' => $district->version + 1,
            ])->save();

            $event = $district->workflowEvents()->create([
                'actor_id' => $actor->id,
                'assigned_to' => $reviewer?->id,
                'action' => $action,
                'from_status' => $expectedStatus,
                'to_status' => $nextStatus,
                'reason' => $reason,
            ]);

            $this->audit($district, $actor, $action, $before, $district->only(array_keys($before)), $event);

            return [$district->fresh(), $reviewer ?? $district->creator];
        });

        $this->notify($recipient, $actor, $district, $action, $reason);

        return $district;
    }

    private function assertStatus(District $district, string $expected): void
    {
        if ($district->workflow_status !== $expected) {
            throw ValidationException::withMessages(['workflow' => "District must be {$expected} for this action."]);
        }
    }

    private function audit(District $district, User $actor, string $action, array $before, array $after, Model $event): void
    {
        activity('workflow')
            ->causedBy($actor)
            ->performedOn($district)
            ->event($action)
            ->withProperties(['before' => $before, 'after' => $after, 'workflow_event_uuid' => $event->uuid])
            ->log("District {$action}");
    }

    private function notify(?User $recipient, User $actor, District $district, string $action, ?string $reason): void
    {
        if ($recipient && ! $recipient->is($actor)) {
            Notification::send($recipient, new WorkflowTransitionNotification('district', $district->name, $action, $district->workflow_status, $reason));
        }
    }
}