<?php

namespace Tests\Feature\Site;

use App\Enums\AnnouncementType;
use App\Enums\RequestStatus;
use App\Enums\TransferStatus;
use App\Models\Announcement;
use App\Models\Currency;
use App\Models\Service;
use App\Models\TransferRequest;
use App\Support\Permissions;
use Database\Seeders\ReferenceDataSeeder;
use Tests\Feature\AlraedTestCase;

class PublicSiteTest extends AlraedTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ReferenceDataSeeder::class);
    }

    private function validRequest(array $overrides = []): array
    {
        return [
            'name' => 'أحمد محمد', 'phone' => '+970 597 000 111', 'operation' => 'withdraw',
            'amount' => '١٬٠٠٠', 'currency_id' => Currency::where('code', 'USDT')->value('id'),
            'service_id' => Service::where('code', 'usdt_withdraw')->value('id'), 'consent' => '1', ...$overrides,
        ];
    }

    public function test_every_public_page_renders_in_both_languages(): void
    {
        foreach (['ar' => 'rtl', 'en' => 'ltr'] as $locale => $dir) {
            $this->withSession(['locale' => $locale]);
            foreach (['/', '/services', '/request', '/track', '/announcements', '/contact', '/page/instructions', '/page/privacy', '/page/terms', '/page/legal'] as $url) {
                $this->get($url)->assertOk()->assertSee("dir=\"$dir\"", false);
            }
        }
    }

    public function test_home_lists_active_services_only(): void
    {
        Service::where('code', 'us_venmo')->update(['is_active' => false]);

        $this->get('/')->assertSee('Vodafone Cash')->assertDontSee('Venmo');
    }

    public function test_unknown_policy_page_is_404(): void
    {
        $this->get('/page/../../.env')->assertNotFound();
        $this->get('/page/secrets')->assertNotFound();
    }

    public function test_request_is_stored_with_normalised_amount_and_unguessable_reference(): void
    {
        $this->post('/request', $this->validRequest())->assertRedirect(route('site.request.done'));

        $r = TransferRequest::sole();
        $this->assertMatchesRegularExpression('/^RQ-[A-Z2-9]{7}$/', $r->reference);
        $this->assertSame('1000.00000000', $r->amount);
        $this->assertSame(RequestStatus::New, $r->status);
        $this->assertSame('597000111', $r->phone_digits);

        $this->get(route('site.request.done'))->assertOk();
    }

    public function test_done_page_needs_a_fresh_submission(): void
    {
        $this->get(route('site.request.done'))->assertRedirect(route('site.request'));
    }

    public function test_request_validation(): void
    {
        $this->post('/request', $this->validRequest(['consent' => null, 'amount' => '-5', 'phone' => 'abc']))
            ->assertSessionHasErrors(['consent', 'amount', 'phone']);
        $this->assertDatabaseCount('transfer_requests', 0);
    }

    public function test_honeypot_blocks_bots(): void
    {
        $this->post('/request', $this->validRequest(['website' => 'http://spam']))->assertSessionHasErrors('website');
        $this->assertDatabaseCount('transfer_requests', 0);
    }

    public function test_request_form_is_rate_limited(): void
    {
        foreach (range(1, 5) as $i) {
            $this->post('/request', $this->validRequest());
        }
        $this->post('/request', $this->validRequest())->assertStatus(429);
    }

    public function test_tracking_needs_matching_phone(): void
    {
        $this->post('/request', $this->validRequest());
        $ref = TransferRequest::sole()->reference;

        $this->post('/track', ['reference' => strtolower($ref), 'phone' => '0597-000-111'])
            ->assertOk()->assertSee($ref)->assertSee(__('web.track.stages.received'));

        $this->post('/track', ['reference' => $ref, 'phone' => '0599 999 999'])
            ->assertOk()->assertSee(__('web.track.not_found'))->assertDontSee('أحمد محمد');
    }

    public function test_converting_a_request_creates_customer_and_transfer_and_updates_tracking(): void
    {
        $this->post('/request', $this->validRequest());
        $request = TransferRequest::sole();
        $this->actingAs($this->staff(Permissions::ROLE_EMPLOYEE));

        $transfer = $request->convertToTransfer();

        $this->assertSame(RequestStatus::Converted, $request->status);
        $this->assertSame('1000.00000000', $transfer->send_amount);
        $this->assertSame('أحمد محمد', $transfer->customer->name);
        $this->assertSame(2, $request->fresh()->publicStage()['step']);

        $this->forceStatus($transfer, TransferStatus::Approved);
        $this->assertSame('executing', $request->fresh()->publicStage()['key']);

        $this->expectException(\RuntimeException::class);
        $request->fresh()->convertToTransfer();
    }

    public function test_request_permissions(): void
    {
        $this->post('/request', $this->validRequest());
        $request = TransferRequest::sole();

        $this->assertTrue($this->staff(Permissions::ROLE_EMPLOYEE)->can('convert', $request));
        $this->assertFalse($this->staff(Permissions::ROLE_CASHIER)->can('convert', $request));
        $this->assertFalse($this->staff(Permissions::ROLE_VIEWER)->can('handle', $request));
        $this->assertFalse($this->staff(Permissions::ROLE_ADMIN)->can('update', $request));
    }

    public function test_only_live_announcements_are_public(): void
    {
        Announcement::create(['type' => AnnouncementType::AccountChange, 'title_ar' => 'حساب جديد للاستقبال', 'body_ar' => 'نص', 'is_published' => true]);
        Announcement::create(['type' => AnnouncementType::Info, 'title_ar' => 'مسودة سرية', 'body_ar' => 'نص', 'is_published' => false]);
        Announcement::create(['type' => AnnouncementType::Offer, 'title_ar' => 'عرض منتهٍ', 'body_ar' => 'نص', 'is_published' => true, 'expires_at' => now()->subDay()]);

        $this->get('/announcements')->assertSee('حساب جديد للاستقبال')->assertDontSee('مسودة سرية')->assertDontSee('عرض منتهٍ');
        $this->get('/')->assertSee('حساب جديد للاستقبال');
    }

    public function test_staff_screens_open(): void
    {
        $this->post('/request', $this->validRequest());
        $this->actingAs($this->staff(Permissions::ROLE_SUPERVISOR));

        $this->get('/alraed/transfer-requests')->assertOk()->assertSee(TransferRequest::sole()->reference);
        $this->get('/alraed/announcements')->assertOk();
        $this->get('/alraed/transfers')->assertOk();
        $this->get('/alraed/customers')->assertOk();
    }
}
