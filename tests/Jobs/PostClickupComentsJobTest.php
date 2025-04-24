<?php

namespace PlacetoPay\AppVersion\Tests\Jobs;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use PlacetoPay\AppVersion\Exceptions\ConnectionException;
use PlacetoPay\AppVersion\Jobs\PostClickupCommentsJob;
use PlacetoPay\AppVersion\Services\ClickupService;
use PlacetoPay\AppVersion\Tests\TestCase;

class PostClickupComentsJobTest extends TestCase
{

    private const ENVIRONMENT = 'testing';

    /** @test */
    public function it_posts_comment_when_changelog_has_clickup_tasks(): void
    {
        Carbon::setTestNow('2025-01-01');
        $this->mock(ClickupService::class, function (MockInterface $mock) {
            $message = "Despligue realizado en ambiente: testing\nFecha: 2025-01-01 00:00:00\nVersion: 1.2.0";

            $mock->shouldReceive('postCommentOnTask')
                ->once()
                ->withArgs(['TST-123', $message, 999])
                ->andReturnTrue();
            $mock->shouldReceive('postCommentOnTask')
                ->once()
                ->withArgs(['12345678', $message, null])
                ->andReturnTrue();
        });

        $job = new PostClickupCommentsJob(self::ENVIRONMENT, [
            'version' => '1.2.0',
            'tasks' => [
                ['id' => 'TST-123', 'team' => '999'],
                ['id' => '12345678', 'team' => null],
            ],
        ], Carbon::now());

        dispatch($job);
    }

    /** @test */
    public function it_logs_error_when_post_comment_fails_for_a_task(): void
    {
        $errorTask = ['id' => '12345678', 'team' => null];
        $this->mock(ClickupService::class, function (MockInterface $mock) {
            $mock->shouldReceive('postCommentOnTask')
                ->once()
                ->andThrow(new ConnectionException('Error posting comment'));

            $mock->shouldReceive('postCommentOnTask')
                ->once()
                ->andReturnTrue();
        });

        Log::shouldReceive('error')
            ->once()
            ->with('[ERROR] ClickUp publish comment', \Mockery::on(function ($context) use ($errorTask) {
                return $context['task'] === $errorTask
                    && $context['error'] === 'Error posting comment';
            }));

        $job = new PostClickupCommentsJob(self::ENVIRONMENT, [
            'version' => '1.2.0',
            'tasks' => [$errorTask, ['id' => 'TST-123', 'team' => '999']],
        ], Carbon::now());

        dispatch($job);
    }
}
