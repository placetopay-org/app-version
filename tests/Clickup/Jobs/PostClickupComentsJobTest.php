<?php

namespace PlacetoPay\AppVersion\Tests\Clickup\Jobs;

use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use PlacetoPay\AppVersion\Clickup\PostClickupCommentsJob;
use PlacetoPay\AppVersion\Tests\TestCase;

class PostClickupComentsJobTest extends TestCase
{
    private const ENVIRONMENT = 'testing';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('app-version.clickup.base_url', 'https://test.com/api');
    }

    /** @test */
    public function can_posts_comment_when_changelog_has_clickup_tasks(): void
    {
        Carbon::setTestNow('2025-01-01');

        $this->partialMock(PendingRequest::class, function (MockInterface $mock) {
            $mock->shouldReceive('post')
                ->twice()
                ->andReturn(new Response(new \GuzzleHttp\Psr7\Response()));
        });

        Log::shouldReceive('log')
            ->once()
            ->with('info', '[SUCCESS - app-version] ClickUp publish comment', \Mockery::on(function ($context) {
                return $context['version'] === '1.2.0' && $context['task'] === ['id' => 'TST-123', 'team' => '999'];
            }));

        Log::shouldReceive('log')
            ->once()
            ->with('info', '[SUCCESS - app-version] ClickUp publish comment', \Mockery::on(function ($context) {
                return $context['version'] === '1.2.0' && $context['task'] === ['id' => '12345678', 'team' => null];
            }));

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
    public function can_post_comment_when_one_fails(): void
    {
        $this->partialMock(PendingRequest::class, function (MockInterface $mock) {
            $mock->shouldReceive('post')
                ->twice()
                ->andReturn(
                    new Response(new \GuzzleHttp\Psr7\Response(401, [], '', '1.1', 'Error posting comment')),
                    new Response(new \GuzzleHttp\Psr7\Response())
                );
        });

        $errorTask = ['id' => '12345678', 'team' => null];
        $successTask = ['id' => 'TST-123', 'team' => '999'];

        Log::shouldReceive('log')
            ->once()
            ->with('error', '[ERROR - app-version] ClickUp publish comment', \Mockery::on(function ($context) use ($errorTask) {
                return $context['task'] === $errorTask && str_contains($context['error'], 'Error posting comment');
            }));

        Log::shouldReceive('log')
            ->once()
            ->with('info', '[SUCCESS - app-version] ClickUp publish comment', \Mockery::on(function ($context) {
                return $context['version'] === '1.2.0' && $context['task'] === ['id' => 'TST-123', 'team' => '999'];
            }));

        $job = new PostClickupCommentsJob(self::ENVIRONMENT, [
            'version' => '1.2.0',
            'tasks' => [$errorTask, $successTask],
        ], Carbon::now());

        dispatch($job);
    }
}
