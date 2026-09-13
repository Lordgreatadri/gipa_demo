<?php

namespace App\Http\Controllers\PublicPortal;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactEnquiryRequest;
use App\Services\ContactEnquiryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function __construct(private readonly ContactEnquiryService $enquiries) {}

    public function create(): View
    {
        return view('public.contact.create');
    }

    public function store(StoreContactEnquiryRequest $request): RedirectResponse
    {
        $enquiry = $this->enquiries->submit(
            $request->safe()->only(['name', 'email', 'phone_number', 'available_time_for_call', 'subject', 'message']),
            $request->ip(),
        );

        return redirect()
            ->route('contact.create')
            ->with('contact_reference', $enquiry->reference)
            ->with('status', 'Thank you. Your enquiry has been received under reference '.$enquiry->reference.'. We have emailed you a confirmation and our team will be in touch shortly.');
    }
}
