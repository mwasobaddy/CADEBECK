<?php

namespace App\Console\Commands;

use App\Mail\LaunchNotification;
use App\Models\LaunchSubscriber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class NotifyLaunchSubscribers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'launch:notify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send the launch notification email to every subscriber who has not been notified yet';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! config('app.launched')) {
            $this->comment('Site is not launched yet. Add APP_LAUNCHED=true to env and cache config to enable.');

            return self::SUCCESS;
        }

        $subscribers = LaunchSubscriber::whereNull('notified_at')->get();

        $count = $subscribers->count();

        if ($count === 0) {
            $this->info('No pending launch subscribers to notify.');

            return self::SUCCESS;
        }

        $this->info("Dispatching launch notifications to {$count} subscriber(s)...");

        foreach ($subscribers as $subscriber) {
            Mail::to($subscriber->email)->queue(new LaunchNotification());
        }

        LaunchSubscriber::whereKey($subscribers->pluck('id'))->update(['notified_at' => now()]);

        $this->info("Queued launch notifications for {$count} subscriber(s).");

        return self::SUCCESS;
    }
}