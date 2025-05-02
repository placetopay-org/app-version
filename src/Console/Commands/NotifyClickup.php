<?php

namespace PlacetoPay\AppVersion\Console\Commands;

use Carbon\Carbon;
use Illuminate\Config\Repository;
use Illuminate\Console\Command;
use PlacetoPay\AppVersion\Clickup\Parsers\TasksFileParser;
use PlacetoPay\AppVersion\Clickup\PostClickupCommentsJob;
use PlacetoPay\AppVersion\Exceptions\ChangelogException;
use PlacetoPay\AppVersion\Helpers\Logger;
use Symfony\Component\Console\Command\Command as CommandStatus;

class NotifyClickup extends Command
{
    protected $signature = 'app-version:notify-clickup';
    protected $description = 'Create a comment on the ClickUp platform on the tasks associated with the deployment version';

    public function handle(Repository $config, TasksFileParser $parser): int
    {
        $versionInformation = $config->get('app-version.version');

        if (!$versionInformation) {
            $this->error('You must execute app-version:create command before.');
            return CommandStatus::FAILURE;
        }

        try {
            $changelogData = $parser->tasksData(
                $versionInformation,
                $config->get('app-version.changelog_file_name'),
            );
            Logger::success(
                'Tasks received successfully',
                ['changelogData' => $changelogData]
            );
        } catch (ChangelogException $exception) {
            Logger::error(
                'Error parsing changelog data',
                ['error' => $exception->getMessage()]
            );

            $this->error('[ERROR] Error parsing changelog data: ' . $exception->getMessage());

            return CommandStatus::FAILURE;
        }

        if (empty($changelogData)) {
            $this->warn('[WARNING] No task found to post comment');
            return CommandStatus::SUCCESS;
        }

        dispatch(new PostClickupCommentsJob($config->get('app.env'), $changelogData, Carbon::now()));

        $this->info('[PROCESSING] Reported tasks');

        return CommandStatus::SUCCESS;
    }
}
