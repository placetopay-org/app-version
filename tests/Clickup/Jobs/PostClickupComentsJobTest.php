<?php

namespace PlacetoPay\AppVersion\Tests\Clickup\Jobs;

use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use PlacetoPay\AppVersion\Clickup\ClickupApi;
use PlacetoPay\AppVersion\Clickup\PostClickupCommentsJob;
use PlacetoPay\AppVersion\Tests\TestCase;

class PostClickupComentsJobTest extends TestCase
{
    private const ENVIRONMENT = 'testing';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('utilities.clickup.base_url', 'https://test.com/api');
    }

    /** @test */
    public function can_posts_comment_when_changelog_has_clickup_tasks(): void
    {
        Carbon::setTestNow('2025-01-01');

        $this->partialMock(ClickupApi::class, function (MockInterface $mock) {
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
    public function can_logs_error_when_post_comment_fails_for_a_task(): void
    {
        $errorTask = ['id' => '12345678', 'team' => null];

        $this->partialMock(ClickupApi::class, function (MockInterface $mock) {
            $mock->shouldReceive('postComment')
                ->twice()
                ->andReturn(
                    new Response(new \GuzzleHttp\Psr7\Response(401, [], '', '1.1', 'Error posting comment')),
                    new Response(new \GuzzleHttp\Psr7\Response())
                );
        });

        Log::shouldReceive('error')
            ->once()
            ->with('[ERROR] ClickUp publish comment', \Mockery::on(function ($context) use ($errorTask) {
                return $context['task'] === $errorTask && str_contains($context['error'], 'Error posting comment');
            }));

        $job = new PostClickupCommentsJob(self::ENVIRONMENT, [
            'version' => '1.2.0',
            'tasks' => [$errorTask, ['id' => 'TST-123', 'team' => '999']],
        ], Carbon::now());

        dispatch($job);
    }
}
