<?php

namespace App\Console\Commands;

use App\Mail\BookingMessageMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmail extends Command
{
    protected $signature = 'mail:test {to : Email address that should receive the test}';

    protected $description = 'Send a test email to check the SMTP settings in .env';

    public function handle(): int
    {
        try {
            Mail::to($this->argument('to'))->send(
                new BookingMessageMail('Test email', 'If you can read this, e-TempahSurau email works.')
            );
        } catch (\Throwable $e) {
            $this->error('Failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info('Sent. Check the inbox (and Spam) of ' . $this->argument('to'));

        return self::SUCCESS;
    }
}
