<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StaffDistrictAssignmentRequest;
use App\Models\District;
use App\Models\StaffDistrictAssignment;
use App\Models\User;
use App\Support\CertificatePermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StaffDistrictAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdministrator($request);

        return view('admin.certificates.assignments', [
            'officers' => User::query()
                ->where('account_type', User::ACCOUNT_STAFF)
                ->where('status', User::STATUS_ACTIVE)
                ->permission(CertificatePermissions::VERIFY)
                ->orderBy('name')
                ->get(['id', 'uuid', 'name']),
            'districts' => District::query()->orderBy('name')->get(['id', 'uuid', 'name']),
            'assignments' => StaffDistrictAssignment::query()
                ->select('id', 'uuid', 'user_id', 'district_id', 'assigned_by', 'starts_at', 'ends_at', 'is_primary', 'created_at')
                ->with(['user:id,name', 'district:id,name', 'assigner:id,name'])
                ->latest('starts_at')
                ->cursorPaginate(30),
        ]);
    }

    public function store(StaffDistrictAssignmentRequest $request): RedirectResponse
    {
        $officer = User::query()->where('uuid', $request->validated('officer'))->firstOrFail();
        $district = District::query()->where('uuid', $request->validated('district'))->firstOrFail();
        $startsAt = now()->parse($request->validated('starts_at'));

        DB::transaction(function () use ($request, $officer, $district, $startsAt): void {
            $overlap = StaffDistrictAssignment::query()
                ->where('user_id', $officer->id)
                ->where('district_id', $district->id)
                ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', $startsAt))
                ->exists();
            if ($overlap) {
                throw ValidationException::withMessages(['district' => 'This officer already has an overlapping assignment for the district.']);
            }

            if ($request->boolean('is_primary')) {
                StaffDistrictAssignment::query()->active()->where('user_id', $officer->id)->update(['is_primary' => false]);
            }
            $assignment = StaffDistrictAssignment::create([
                'user_id' => $officer->id,
                'district_id' => $district->id,
                'assigned_by' => $request->user()->id,
                'starts_at' => $startsAt,
                'is_primary' => $request->boolean('is_primary'),
            ]);
            activity('access')
                ->causedBy($request->user())
                ->performedOn($assignment)
                ->event('district_assigned')
                ->withProperties(['officer_id' => $officer->id, 'district_id' => $district->id, 'starts_at' => $startsAt->toIso8601String()])
                ->log('Staff district assignment created');
        });

        return to_route('staff.certificate-assignments.index')->with('status', 'District assignment created.');
    }

    public function end(Request $request, StaffDistrictAssignment $assignment): RedirectResponse
    {
        $this->authorizeAdministrator($request);
        if (! $assignment->starts_at->lte(now()) || ($assignment->ends_at && $assignment->ends_at->lte(now()))) {
            throw ValidationException::withMessages(['assignment' => 'Only an active assignment may be ended.']);
        }

        $assignment->forceFill(['ends_at' => now(), 'is_primary' => false])->save();
        activity('access')
            ->causedBy($request->user())
            ->performedOn($assignment)
            ->event('district_assignment_ended')
            ->withProperties(['officer_id' => $assignment->user_id, 'district_id' => $assignment->district_id, 'ends_at' => $assignment->ends_at->toIso8601String()])
            ->log('Staff district assignment ended');

        return to_route('staff.certificate-assignments.index')->with('status', 'District assignment ended.');
    }

    private function authorizeAdministrator(Request $request): void
    {
        abort_unless($request->user()->hasRole('Super Administrator'), 403);
    }
}
