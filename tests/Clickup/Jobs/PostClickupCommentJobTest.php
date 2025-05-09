<?php

namespace PlacetoPay\AppVersion\Tests\Clickup\Jobs;

use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Mockery\MockInterface;
use PlacetoPay\AppVersion\Clickup\PostClickupCommentJob;
use PlacetoPay\AppVersion\Exceptions\BadResponseException;
use PlacetoPay\AppVersion\Tests\TestCase;

class PostClickupCommentJobTest extends TestCase
{
    private const ENVIRONMENT = 'testing';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('app-version.clickup.base_url', 'https://test.com/api');
    }

    /** @test */
                //
    public function can_post_a_comment_successfully(): void
    {
        Carbon::setTestNow('2025-01-01');

        $task = ['id' => 'TST-123', 'team' => '999'];

        $this->partialMock(PendingRequest::class, function (MockInterface $mock) {
            $mock->shouldReceive('post')
                ->once()
                ->andReturn(new Response(new \GuzzleHttp\Psr7\Response()));
        });

        Log::shouldReceive('log')
            ->once()
            ->with('info', '[SUCCESS - app-version] ClickUp published comment', \Mockery::on(function ($context) use ($task) {
                return $context['version'] === '1.2.0' && $context['task'] === $task;
            }));

        $job = new PostClickupCommentJob(self::ENVIRONMENT, $task, '1.2.0', Carbon::now());

        dispatch($job);
    }

    /** @test */
    public function logs_error_when_task_fails(): void
    {
        Carbon::setTestNow('2025-01-01');
        $this->expectException(BadResponseException::class);
        $this->expectExceptionMessage('Unsuccessful response: Error posting comment');

        $failTask = ['id' => '12345678', 'team' => null];

        $this->partialMock(PendingRequest::class, function (MockInterface $mock) {
            $mock->shouldReceive('post')
                ->once()
                ->andReturn(new Response(new \GuzzleHttp\Psr7\Response(401, [], '', '1.2.0', 'Error posting comment')));
        });

        Log::shouldReceive('log')
            ->once()
            ->with('error', '[ERROR - app-version] ClickUp publishing comment', \Mockery::on(function ($context) use ($failTask) {
                return $context['task'] === $failTask && str_contains($context['error'], 'Error posting comment');
            }));

        $job = new PostClickupCommentJob(self::ENVIRONMENT, $failTask, '1.2.0', Carbon::now());

        dispatch($job);
    }

}
