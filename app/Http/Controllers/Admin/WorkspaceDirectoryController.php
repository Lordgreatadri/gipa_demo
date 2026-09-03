<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\InvestorInquiry;
use App\Models\InvestorOnboardingCase;
use App\Models\InvestorProfile;
use App\Models\Opportunity;
use App\Models\Region;
use App\Models\User;
use App\Support\InvestorPermissions;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class WorkspaceDirectoryController extends Controller
{
    public function opportunities(): View
    {
        $regions = Region::query()->count();
        $districts = District::query()->count();
        $opportunities = Opportunity::query()->count();
        $active = Opportunity::query()->where('workflow_status', Opportunity::WORKFLOW_ACTIVE)->count();

        return $this->page('opportunities', [
            'regions' => $regions,
            'districts' => $districts,
            'opportunities' => $opportunities,
            'active' => $active,
            'opportunityCharts' => [
                'coverage' => [
                    'labels' => ['Regions', 'Districts', 'Opportunities', 'Active opportunities'],
                    'values' => [$regions, $districts, $opportunities, $active],
                ],
                'portfolio' => [
                    'labels' => ['Active', 'Other lifecycle stages'],
                    'values' => [$active, max(0, $opportunities - $active)],
                ],
            ],
        ]);
    }

    public function regions(): View
    {
        return $this->page('regions', ['regions' => Region::query()->withCount(['districts', 'districts as published_districts_count' => fn ($query) => $query->where('workflow_status', District::STATUS_PUBLISHED)])->orderBy('name')->get()]);
    }

    public function investments(Request $request): View
    {
        abort_unless($request->user()->can(InvestorPermissions::VIEW), 403);

        return $this->page('investments', [
            'investors' => InvestorProfile::query()->count(),
            'applications' => InvestorOnboardingCase::query()->count(),
            'approved' => InvestorOnboardingCase::query()->where('status', InvestorOnboardingCase::STATUS_APPROVED)->count(),
            'inquiries' => InvestorInquiry::query()->count(),
        ]);
    }

    public function inquiries(Request $request): View
    {
        abort_unless($request->user()->can(InvestorPermissions::VIEW), 403);

        return $this->page('inquiries', ['inquiries' => InvestorInquiry::query()->with(['opportunity:id,uuid,title', 'assignee:id,name'])->latest()->paginate(25)]);
    }

    public function notificationsOverview(Request $request): View
    {
        return $this->notifications($request, 'notifications');
    }

    public function notificationsIndex(Request $request): View
    {
        return $this->notifications($request, 'notification-list');
    }

    public function usersOverview(Request $request): View
    {
        return $this->users($request, 'users');
    }

    public function usersStaff(Request $request): View
    {
        return $this->users($request, 'staff');
    }

    public function usersRoles(Request $request): View
    {
        return $this->users($request, 'roles');
    }

    public function usersPermissions(Request $request): View
    {
        return $this->users($request, 'permissions');
    }

    private function notifications(Request $request, string $page): View
    {
        return $this->page($page, [
            'notifications' => $request->user()->notifications()->latest()->paginate(25),
            'unread' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    private function users(Request $request, string $page): View
    {
        abort_unless($request->user()->hasRole('Super Administrator'), 403);

        return $this->page($page, [
            'staff' => User::query()->where('account_type', User::ACCOUNT_STAFF)->with('roles')->orderBy('name')->get(),
            'roles' => Role::query()->withCount(['users', 'permissions'])->orderBy('name')->get(),
            'permissions' => Permission::query()->with('roles:id,name')->orderBy('name')->get(),
        ]);
    }

    private function page(string $page, array $data): View
    {
        return view('admin.workspace-directory', ['page' => $page] + $data);
    }
}
