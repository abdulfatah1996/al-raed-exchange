<?php

namespace Tests\Feature\Admin;

use App\Enums\AnnouncementType;
use App\Models\Announcement;
use App\Support\Permissions;
use Tests\Feature\AlraedTestCase;

class AnnouncementTest extends AlraedTestCase
{
    public function test_states(): void
    {
        $this->assertSame('draft', Announcement::create(['title_ar' => 'a', 'body_ar' => 'b'])->state());
        $this->assertSame('live', Announcement::create(['title_ar' => 'a', 'body_ar' => 'b', 'is_published' => true])->state());
        $this->assertSame('scheduled', Announcement::create(['title_ar' => 'a', 'body_ar' => 'b', 'is_published' => true, 'published_at' => now()->addDay()])->state());
        $this->assertSame('expired', Announcement::create(['title_ar' => 'a', 'body_ar' => 'b', 'is_published' => true, 'expires_at' => now()->subMinute()])->state());
    }

    public function test_scheduled_announcement_is_hidden_until_its_time(): void
    {
        Announcement::create(['title_ar' => 'إعلان مجدول', 'body_ar' => 'b', 'is_published' => true, 'published_at' => now()->addHour()]);
        $this->get('/announcements')->assertDontSee('إعلان مجدول');

        $this->travel(2)->hours();
        $this->get('/announcements')->assertSee('إعلان مجدول');
    }

    public function test_pinned_account_change_shows_as_site_banner(): void
    {
        Announcement::create(['type' => AnnouncementType::AccountChange, 'title_ar' => 'تغيير رقم الاستقبال', 'body_ar' => 'b', 'is_published' => true, 'is_pinned' => true]);
        Announcement::create(['type' => AnnouncementType::Offer, 'title_ar' => 'عرض مثبّت', 'body_ar' => 'b', 'is_published' => true, 'is_pinned' => true]);

        $this->get('/contact')->assertSee('تغيير رقم الاستقبال');
        $this->get('/contact')->assertDontSee('عرض مثبّت');
    }

    public function test_only_permitted_staff_manage_announcements(): void
    {
        $this->actingAs($this->staff(Permissions::ROLE_SUPERVISOR))->get('/alraed/announcements')->assertOk();
        $this->assertFalse($this->staff(Permissions::ROLE_EMPLOYEE)->can('create', Announcement::class));
    }
}
