<?php

namespace PlacetoPay\AppVersion\Console\Commands;

use Carbon\Carbon;
use Illuminate\Config\Repository;
use Illuminate\Console\Command;
use PlacetoPay\AppVersion\Exceptions\ReadFileException;
use PlacetoPay\AppVersion\Jobs\PostClickupCommentsJob;
use PlacetoPay\AppVersion\Parsers\TasksFileParser;
use PlacetoPay\AppVersion\Services\ClickupService;

class NotifyClickup extends Command
{
    protected $signature = 'utilities:notify-clickup';
    protected $description = 'Create a comment on the ClickUp platform on the tasks associated with the deployment version';

    /**
     * @throws ReadFileException
     */
    public function handle(Repository $config, TasksFileParser $parser): int
    {
        $changelogData = $parser->getTasksData($config->get('utilities.clickup.changelog'));

        if (empty($changelogData)) {
            $this->warn('[WARNING] No task found to post comment');
            return 0;
        }

        dispatch(new PostClickupCommentsJob($config->get('app.env'), $changelogData, Carbon::now()));

        $this->info('[PROCESSING] Reported tasks');

        return 0;
    }
}
