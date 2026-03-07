<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Admin\Driver\DriverTraining;
use App\Notifications\TrainingDueSoonNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckOverdueTrainings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trainings:check-overdue
                            {--notify : Send notifications for trainings due soon}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for overdue trainings and optionally send notifications for trainings due soon';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for overdue trainings...');

        // Update overdue trainings
        $overdueCount = DriverTraining::where('status', '!=', 'completed')
            ->whereNotNull('due_date')
            ->where('due_date', '<', Carbon::now())
            ->update(['status' => 'overdue']);

        $this->info("Updated {$overdueCount} training(s) to overdue status.");

        // Send notifications if requested
        if ($this->option('notify')) {
            $this->info('Checking for trainings due soon...');
            $this->sendDueSoonNotifications();
        }

        $this->info('Done!');

        return Command::SUCCESS;
    }

    /**
     * Send notifications for trainings due within 3 days
     */
    protected function sendDueSoonNotifications()
    {
        $threeDaysFromNow = Carbon::now()->addDays(3);
        $today = Carbon::now();

        // Get trainings due within 3 days (but not overdue)
        $trainings = DriverTraining::with(['driver.user', 'training'])
            ->where('status', '!=', 'completed')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$today, $threeDaysFromNow])
            ->get();

        $notificationsSent = 0;

        foreach ($trainings as $training) {
            if (!$training->driver || !$training->driver->user) {
                continue;
            }

            $daysRemaining = Carbon::now()->diffInDays($training->due_date, false);

            // Only send notification for 3 days, 2 days, and 1 day before due
            if (in_array($daysRemaining, [3, 2, 1])) {
                try {
                    // Check if we already sent a notification today for this training
                    $alreadyNotifiedToday = $training->driver->user
                        ->notifications()
                        ->where('type', TrainingDueSoonNotification::class)
                        ->where('data->training_assignment_id', $training->id)
                        ->where('created_at', '>=', Carbon::today())
                        ->exists();

                    if (!$alreadyNotifiedToday) {
                        $training->driver->user->notify(
                            new TrainingDueSoonNotification($training, $daysRemaining)
                        );
                        $notificationsSent++;
                        
                        Log::info('Training due soon notification sent', [
                            'training_id' => $training->id,
                            'user_id' => $training->driver->user->id,
                            'days_remaining' => $daysRemaining
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to send training due soon notification', [
                        'training_id' => $training->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        $this->info("Sent {$notificationsSent} notification(s) for trainings due soon.");
    }
}

