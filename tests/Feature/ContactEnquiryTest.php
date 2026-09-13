<?php

namespace Tests\Feature;

use App\Models\ContactUs;
use App\Models\SmsMessage;
use App\Models\SmsProviderConfig;
use App\Models\User;
use App\Notifications\ContactEnquiryAcknowledged;
use App\Notifications\ContactEnquiryReceived;
use App\Support\SupportPermissions;
use Database\Seeders\WorkflowPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ContactEnquiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WorkflowPermissionSeeder::class);
    }

    /**
     * @return array<string, string>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ama Mensah',
            'email' => 'ama@example.com',
            'phone_number' => '0241234567',
            'available_time_for_call' => 'Weekday mornings',
            'subject' => 'Agro-processing opportunity in Bono East',
            'message' => 'We would like more detail on the shea processing opportunity listed for Techiman.',
            'consent' => '1',
        ], $overrides);
    }

    private function supportStaff(): User
    {
        $user = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);
        $user->givePermissionTo(SupportPermissions::ALL);

        return $user;
    }

    public function test_the_public_contact_page_is_reachable_without_authentication(): void
    {
        $this->get(route('contact.create'))
            ->assertOk()
            ->assertSee('Contact us');
    }

    public function test_an_enquiry_is_stored_and_both_parties_are_notified(): void
    {
        Notification::fake();
        $staff = $this->supportStaff();

        $this->post(route('contact.store'), $this->payload())
            ->assertRedirect(route('contact.create'))
            ->assertSessionHas('contact_reference');

        $enquiry = ContactUs::firstOrFail();
        $this->assertSame('Ama Mensah', $enquiry->name);
        $this->assertSame(ContactUs::STATUS_NEW, $enquiry->status);
        $this->assertStringStartsWith('CON-', $enquiry->reference);
        $this->assertNotNull($enquiry->ip_address);
        $this->assertTrue($enquiry->email_sent);
        $this->assertTrue($enquiry->sms_sent);

        Notification::assertSentTo($enquiry, ContactEnquiryAcknowledged::class);
        Notification::assertSentTo($staff, ContactEnquiryReceived::class);
    }

    public function test_the_acknowledgement_reaches_the_sender_by_email_and_sms(): void
    {
        $this->supportStaff();

        SmsProviderConfig::create([
            'provider' => 'frog',
            'sender_id' => 'GIPA',
            'is_active' => true,
            'credentials' => ['username' => 'gipa-ops', 'api_key' => 'frog-key'],
        ]);

        Http::fake(['*frogapi*' => Http::response(['status' => 'ACCEPTD', 'message' => 'ok'])]);

        $this->post(route('contact.store'), $this->payload())->assertRedirect();

        $sms = SmsMessage::firstOrFail();
        $this->assertSame('contact_acknowledgement', $sms->context);
        // `recipient` holds the exact string handed to the gateway (Frog wants
        // bare digits); the original submission is preserved in `recipient_raw`.
        $this->assertSame('233241234567', $sms->recipient);
        $this->assertSame('0241234567', $sms->recipient_raw);
        $this->assertStringContainsString('GIPA', $sms->body);
        $this->assertSame(SmsMessage::STATUS_SENT, $sms->status);
    }

    public function test_an_enquiry_without_a_phone_number_is_acknowledged_by_email_only(): void
    {
        $this->supportStaff();
        Http::fake();

        $this->post(route('contact.store'), $this->payload(['phone_number' => '']))->assertRedirect();

        $enquiry = ContactUs::firstOrFail();
        $this->assertTrue($enquiry->email_sent);
        $this->assertFalse($enquiry->sms_sent);
        $this->assertNull($enquiry->sms_sent_at);
        $this->assertSame(0, SmsMessage::count());
    }

    public function test_submission_is_validated(): void
    {
        $this->post(route('contact.store'), $this->payload([
            'email' => 'not-an-email',
            'message' => 'too short',
            'phone_number' => '123',
        ]))->assertSessionHasErrors(['email', 'message', 'phone_number']);

        $this->assertSame(0, ContactUs::count());
    }

    public function test_consent_is_required(): void
    {
        $payload = $this->payload();
        unset($payload['consent']);

        $this->post(route('contact.store'), $payload)->assertSessionHasErrors('consent');
        $this->assertSame(0, ContactUs::count());
    }

    public function test_the_honeypot_field_blocks_bot_submissions(): void
    {
        $this->post(route('contact.store'), $this->payload(['website' => 'http://spam.example']))
            ->assertSessionHasErrors('website');

        $this->assertSame(0, ContactUs::count());
    }

    public function test_staff_without_support_permission_cannot_open_the_inbox(): void
    {
        $staff = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);

        $this->actingAs($staff)->get(route('staff.contact-messages.index'))->assertForbidden();
    }

    public function test_the_inbox_lists_enquiries_and_can_filter_by_status(): void
    {
        $staff = $this->supportStaff();

        ContactUs::create(['reference' => 'CON-1', 'name' => 'Kojo', 'email' => 'kojo@example.com', 'subject' => 'Tourism site', 'message' => 'Details please.']);
        ContactUs::create(['reference' => 'CON-2', 'name' => 'Adjoa', 'email' => 'adjoa@example.com', 'subject' => 'Mining licence', 'message' => 'Details please.', 'status' => ContactUs::STATUS_CLOSED]);

        $this->actingAs($staff)->get(route('staff.contact-messages.index'))
            ->assertOk()
            ->assertSee('Tourism site')
            ->assertSee('Mining licence');

        $this->actingAs($staff)->get(route('staff.contact-messages.index', ['status' => ContactUs::STATUS_CLOSED]))
            ->assertOk()
            ->assertSee('Mining licence')
            ->assertDontSee('Tourism site');
    }

    public function test_opening_an_enquiry_marks_it_read(): void
    {
        $staff = $this->supportStaff();
        $enquiry = ContactUs::create(['reference' => 'CON-3', 'name' => 'Kojo', 'email' => 'kojo@example.com', 'subject' => 'Tourism site', 'message' => 'Details please.']);

        $this->actingAs($staff)->get(route('staff.contact-messages.show', $enquiry))->assertOk();

        $enquiry->refresh();
        $this->assertSame(ContactUs::STATUS_READ, $enquiry->status);
        $this->assertSame($staff->name, $enquiry->read_by);
        $this->assertNotNull($enquiry->read_at);
    }

    public function test_staff_can_record_a_response_and_close_an_enquiry(): void
    {
        $staff = $this->supportStaff();
        $enquiry = ContactUs::create(['reference' => 'CON-4', 'name' => 'Kojo', 'email' => 'kojo@example.com', 'subject' => 'Tourism site', 'message' => 'Details please.']);

        $this->actingAs($staff)->patch(route('staff.contact-messages.update', $enquiry), [
            'action' => 'respond',
            'admin_note' => 'Called the enquirer and emailed the sector brief.',
        ])->assertRedirect();

        $enquiry->refresh();
        $this->assertSame(ContactUs::STATUS_RESPONDED, $enquiry->status);
        $this->assertSame($staff->id, $enquiry->responded_by);
        $this->assertNotNull($enquiry->responded_at);
        $this->assertStringContainsString('sector brief', $enquiry->admin_note);

        $this->actingAs($staff)->patch(route('staff.contact-messages.update', $enquiry), ['action' => 'close'])
            ->assertRedirect();

        $this->assertSame(ContactUs::STATUS_CLOSED, $enquiry->fresh()->status);
    }

    public function test_staff_can_assign_an_enquiry_to_a_colleague(): void
    {
        $staff = $this->supportStaff();
        $colleague = User::factory()->create(['account_type' => User::ACCOUNT_STAFF]);
        $enquiry = ContactUs::create(['reference' => 'CON-5', 'name' => 'Kojo', 'email' => 'kojo@example.com', 'subject' => 'Tourism site', 'message' => 'Details please.']);

        $this->actingAs($staff)->patch(route('staff.contact-messages.update', $enquiry), [
            'action' => 'assign',
            'assigned_to' => $colleague->uuid,
        ])->assertRedirect();

        $this->assertSame($colleague->id, $enquiry->fresh()->assigned_to);
    }

    public function test_archiving_an_enquiry_soft_deletes_it(): void
    {
        $staff = $this->supportStaff();
        $enquiry = ContactUs::create(['reference' => 'CON-6', 'name' => 'Kojo', 'email' => 'kojo@example.com', 'subject' => 'Tourism site', 'message' => 'Details please.']);

        $this->actingAs($staff)->delete(route('staff.contact-messages.destroy', $enquiry))
            ->assertRedirect(route('staff.contact-messages.index'));

        $this->assertSoftDeleted('contact_us', ['id' => $enquiry->id]);
    }
}
