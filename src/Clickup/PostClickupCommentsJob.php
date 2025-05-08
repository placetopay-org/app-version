<?php

namespace PlacetoPay\AppVersion\Clickup;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use PlacetoPay\AppVersion\Helpers\Logger;
use Throwable;

class PostClickupCommentsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public array $data;
    public Carbon $date;
    public string $environment;
    private ClickupApi $service;

    public function __construct(string $environment, array $data, Carbon $date)
    {
        $this->environment = $environment;
        $this->data = $data;
        $this->date = $date;
    }

    public function handle(ClickupApi $service)
    {
        $tasks = $this->data['tasks'];
        $comment = $this->buildCommentText();

        foreach ($tasks as $task) {
            try {
                $service->postCommentOnTask($task['id'], $comment, $task['team']);
                Logger::success(
                    'ClickUp publish comment',
                    ['version' => $this->data['version'], 'task' => $task]
                );
            } catch (Throwable $e) {
                Logger::error('ClickUp publish comment', [
                    'env' => $this->environment,
                    'deploy_date' => $this->date,
                    'version' => $this->data['version'],
                    'task' => $task,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function buildCommentText(): string
    {
        return sprintf(
            "Despligue realizado en ambiente: %s\nFecha: %s\nVersion: %s",
            $this->environment,
            $this->date->format('Y-m-d H:i:s', config('app.timezone')),
            $this->data['version']
        );
    }
}
