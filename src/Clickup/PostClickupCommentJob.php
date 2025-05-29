<?php

namespace PlacetoPay\AppVersion\Clickup;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use PlacetoPay\AppVersion\Exceptions\BadResponseException;
use PlacetoPay\AppVersion\Helpers\Logger;
use Throwable;

class PostClickupCommentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 3;
    public int $backoff = 60;

    public string $environment;
    public array $task;
    public string $version;
    public Carbon $date;

    public function __construct(string $environment, array $task, string $version, Carbon $date)
    {
        $this->environment = $environment;
        $this->task = $task;
        $this->version = $version;
        $this->date = $date;
    }

    /**
     * @throws Throwable
     * @throws BadResponseException
     */
    public function handle(ClickupApi $service)
    {
        $service->postCommentOnTask($this->task['id'], $this->buildCommentText(), $this->task['team']);
        Logger::success('ClickUp published comment', [
            'version' => $this->version,
            'task' => $this->task,
        ]);
    }

    private function buildCommentText(): string
    {
        return sprintf(
            "Despliegue realizado en ambiente: %s\nFecha: %s\nVersión: %s",
            $this->environment,
            $this->date->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
            $this->version
        );
    }

    public function failed(Throwable $exception)
    {
        Logger::error('ClickUp publishing comment', [
            'environment' => $this->environment,
            'version' => $this->version,
            'task' => $this->task,
            'error' => $exception->getMessage(),
        ]);
    }
}
