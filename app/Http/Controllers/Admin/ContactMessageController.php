<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactUs;
use App\Models\User;
use App\Services\ContactEnquiryService;
use App\Support\SupportPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Staff inbox for enquiries submitted through the public Contact us page.
 */
class ContactMessageController extends Controller
{
    public function __construct(private readonly ContactEnquiryService $enquiries) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->can(SupportPermissions::CONTACT_VIEW), 403);

        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(ContactUs::STATUSES))],
            'search' => ['nullable', 'string', 'max:120'],
        ]);

        $messages = ContactUs::query()
            ->with(['assignee', 'responder'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($inner) use ($search): void {
                $inner->where('reference', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('subject', 'like', '%'.$search.'%');
            }))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.contact.index', [
            'messages' => $messages,
            'filters' => $filters,
            'statuses' => ContactUs::STATUSES,
            'stats' => [
                'new' => ContactUs::where('status', ContactUs::STATUS_NEW)->count(),
                'outstanding' => ContactUs::outstanding()->count(),
                'responded' => ContactUs::where('status', ContactUs::STATUS_RESPONDED)->count(),
                'total' => ContactUs::count(),
            ],
            'canManage' => $request->user()->can(SupportPermissions::CONTACT_MANAGE),
        ]);
    }

    public function show(Request $request, ContactUs $message): View
    {
        abort_unless($request->user()->can(SupportPermissions::CONTACT_VIEW), 403);

        $canManage = $request->user()->can(SupportPermissions::CONTACT_MANAGE);

        if ($canManage) {
            $this->enquiries->markRead($message, $request->user());
        }

        return view('admin.contact.show', [
            'enquiry' => $message->fresh(['assignee', 'responder']),
            'assignees' => User::query()
                ->where('account_type', User::ACCOUNT_STAFF)
                ->orderBy('name')
                ->get(['id', 'uuid', 'name']),
            'canManage' => $canManage,
        ]);
    }

    public function update(Request $request, ContactUs $message): RedirectResponse
    {
        abort_unless($request->user()->can(SupportPermissions::CONTACT_MANAGE), 403);

        $validated = $request->validate([
            'action' => ['required', Rule::in(['respond', 'close', 'reopen', 'assign'])],
            'admin_note' => ['nullable', 'string', 'max:2000'],
            'assigned_to' => ['nullable', 'uuid', 'exists:users,uuid'],
        ]);

        match ($validated['action']) {
            'respond' => $this->enquiries->markResponded($message, $request->user(), $validated['admin_note'] ?? null),
            'close' => $this->enquiries->close($message, $validated['admin_note'] ?? null),
            'reopen' => $this->enquiries->markRead($message->forceFill(['status' => ContactUs::STATUS_NEW]), $request->user()),
            'assign' => $this->enquiries->assign(
                $message,
                $validated['assigned_to'] ?? null ? User::where('uuid', $validated['assigned_to'])->first() : null,
            ),
        };

        return back()->with('status', 'Enquiry '.$message->reference.' updated.');
    }

    public function destroy(Request $request, ContactUs $message): RedirectResponse
    {
        abort_unless($request->user()->can(SupportPermissions::CONTACT_MANAGE), 403);

        $message->delete();

        return redirect()
            ->route('staff.contact-messages.index')
            ->with('status', 'Enquiry archived.');
    }
}
