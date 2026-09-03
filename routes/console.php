<?php

use App\Console\Commands\EvaluateVegetableWatchesCommand;
use App\Console\Commands\ExpirePostItemsCommand;
use App\Console\Commands\ExpireSubscriptionsCommand;
use App\Console\Commands\NotifyPostsDueTodayCommand;
use App\Console\Commands\NotifyPostsReminderCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(ExpirePostItemsCommand::class)->daily()->onOneServer();
Schedule::command(ExpireSubscriptionsCommand::class)->daily()->onOneServer();

Schedule::command(EvaluateVegetableWatchesCommand::class)->weekly()->onOneServer();

Schedule::command(NotifyPostsDueTodayCommand::class)->dailyAt('07:00')->onOneServer();
Schedule::command(NotifyPostsReminderCommand::class, ['morning'])->dailyAt('06:00')->onOneServer();
Schedule::command(NotifyPostsReminderCommand::class, ['evening'])->dailyAt('18:00')->onOneServer();
