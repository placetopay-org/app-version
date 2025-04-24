<?php

namespace PlacetoPay\AppVersion\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use PlacetoPay\AppVersion\Exceptions\ReadFileException;
use PlacetoPay\AppVersion\Jobs\PostClickupCommentsJob;
use PlacetoPay\AppVersion\Parsers\TasksFileParser;
use PlacetoPay\AppVersion\Services\ClickupService;

class NotifyClickup extends Command
{
    private ClickupService $service;
    protected $signature = 'utilities:notify-clickup';
    protected $description = 'Create a comment on the ClickUp platform on the tasks associated with the deployment version';

    public function __construct(ClickupService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    /**
     * @throws ReadFileException
     */
    public function handle(TasksFileParser $parser): int
    {
        $changelogData = $parser->getTasksData(config('utilities.clickup.changelog'));

        if (empty($changelogData)) {
            $this->warn('[WARNING] No task found to post comment');
            return 0;
        }

        dispatch(new PostClickupCommentsJob(config('app.env'), $changelogData, Carbon::now()));

        $this->info('[PROCESSING] Reported tasks');

        return 0;
    }
}
