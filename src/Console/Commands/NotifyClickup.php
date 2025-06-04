<?php

namespace PlacetoPay\AppVersion\Console\Commands;

use Carbon\Carbon;
use Illuminate\Config\Repository;
use Illuminate\Console\Command;
use PlacetoPay\AppVersion\Clickup\CommentClickupTaskJob;
use PlacetoPay\AppVersion\Clickup\Parsers\TasksFileParser;
use PlacetoPay\AppVersion\Exceptions\ChangelogException;
use PlacetoPay\AppVersion\Helpers\Logger;
use Symfony\Component\Console\Command\Command as CommandStatus;

class NotifyClickup extends Command
{
    protected $signature = 'app-version:notify-clickup';
    protected $description = 'Create a comment in ClickUp platform on the tasks associated with the deployment version';

    public function handle(Repository $config, TasksFileParser $parser): int
    {
        try {
            $tasksData = $parser->tasksData($config->get('app-version.changelog_file_name'));
        } catch (ChangelogException $exception) {
            Logger::error('Error parsing changelog data', ['error' => $exception->getMessage()]);
            $this->error('[ERROR] Error parsing changelog data: ' . $exception->getMessage());

            return CommandStatus::FAILURE;
        }

        if (empty($tasksData)) {
            $this->warn('[WARNING] No task found to post to comment');
            return CommandStatus::SUCCESS;
        }

        Logger::success(sprintf(
            'It\'ll report %s tasks in clickup with version %s',
            count($tasksData['tasks']),
            $tasksData['version']
        ));

        foreach ($tasksData['tasks'] as $task) {
            dispatch(new CommentClickupTaskJob(
                $config->get('app.env'),
                $task,
                $tasksData['version'],
                Carbon::now()
            ));
        }

        $this->info(sprintf('[PROCESSING] Reported %d tasks', count($tasksData['tasks'])));

        return CommandStatus::SUCCESS;
    }
}
