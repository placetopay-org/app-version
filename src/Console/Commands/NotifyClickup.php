<?php

namespace PlacetoPay\AppVersion\Console\Commands;

use Carbon\Carbon;
use Illuminate\Config\Repository;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use PlacetoPay\AppVersion\Clickup\Parsers\TasksFileParser;
use PlacetoPay\AppVersion\Clickup\PostClickupCommentsJob;
use Symfony\Component\Console\Command\Command as CommandStatus;

class NotifyClickup extends Command
{
    protected $signature = 'utilities:notify-clickup';
    protected $description = 'Create a comment on the ClickUp platform on the tasks associated with the deployment version';

    public function handle(Repository $config, TasksFileParser $parser): int
    {
        $appVersion = $config->get('app-version');

        if (!Arr::exists($appVersion, 'version')) {
            $this->error('You must execute app-version:create command before.');
            return CommandStatus::FAILURE;
        }

        $changelogData = $parser->tasksData(Arr::get($appVersion, 'version'));

        if (empty($changelogData)) {
            $this->warn('[WARNING] No task found to post comment');
            return CommandStatus::SUCCESS;
        }

        dispatch(new PostClickupCommentsJob($config->get('app.env'), $changelogData, Carbon::now()));

        $this->info('[PROCESSING] Reported tasks');

        return CommandStatus::SUCCESS;
    }
}
