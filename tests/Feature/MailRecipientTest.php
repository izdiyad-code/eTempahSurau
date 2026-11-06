<?php

namespace Tests\Feature;

use App\Mail\BookingMessageMail;
use App\Models\AppNotification;
use App\Models\User;
use App\Services\BookingNotifier;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailRecipientTest extends TestCase
{
    public function test_smtp_skips_dummy_mailbox_but_keeps_in_app_notification(): void
    {
        config(['mail.default' => 'smtp', 'mail.allowed_recipients' => []]);
        Mail::fake();
        $user = User::factory()->create(['email' => 'chairman@etempahsurau.test']);

        BookingNotifier::toUser($user, null, 'mail_check', 'Mail check', 'Test only');

        Mail::assertNothingSent();
        $this->assertDatabaseHas((new AppNotification)->getTable(), ['user_id' => $user->id, 'type' => 'mail_check']);
    }

    public function test_smtp_still_dispatches_mail_for_a_non_dummy_recipient(): void
    {
        config(['mail.default' => 'smtp', 'mail.allowed_recipients' => []]);
        Mail::fake();
        $user = User::factory()->create(['email' => 'recipient@example.com']);

        BookingNotifier::toUser($user, null, 'mail_check', 'Mail check', 'Test only');

        Mail::assertSent(BookingMessageMail::class, fn ($mail) => $mail->hasTo($user->email));
        $this->assertDatabaseHas((new AppNotification)->getTable(), ['user_id' => $user->id, 'type' => 'mail_check']);
    }

    public function test_external_demo_list_blocks_other_addresses_without_losing_notifications(): void
    {
        config(['mail.default' => 'smtp', 'mail.allowed_recipients' => ['allowed@example.com']]);
        Mail::fake();
        $dummy = User::factory()->create(['email' => 'dummy@example.org']);
        BookingNotifier::toUser($dummy, null, 'mail_check', 'Mail check', 'Test only');
        Mail::assertNothingSent();
        $this->assertDatabaseHas((new AppNotification)->getTable(), ['user_id' => $dummy->id, 'type' => 'mail_check']);

        $allowed = User::factory()->create(['email' => 'allowed@example.com']);
        BookingNotifier::toUser($allowed, null, 'mail_check', 'Mail check', 'Test only');
        Mail::assertSent(BookingMessageMail::class, fn ($mail) => $mail->hasTo($allowed->email));
    }
}
