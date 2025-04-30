<?php

namespace PlacetoPay\AppVersion\Tests\Commands;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use PlacetoPay\AppVersion\Clickup\Parsers\TasksFileParser;
use PlacetoPay\AppVersion\Clickup\PostClickupCommentsJob;
use PlacetoPay\AppVersion\Exceptions\ChangelogException;
use PlacetoPay\AppVersion\Tests\TestCase;
use Symfony\Component\Console\Command\Command;

class NotifyClickupCommandTest extends TestCase
{
    private const COMMAND_NAME = 'app-version:notify-clickup';
    private const ENVIRONMENT = 'testing';

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('app-version.version', [
            'sha' => 'TESTING_SHA',
            'time' => '2025-04-29T11:19:34-05:00',
            'branch' => 'testing',
            'version' => '1.0.0',
        ]);
    }


    /** @test */
    public function can_dispatch_post_clickup_job(): void
    {
        Queue::fake();

        $this->mock(TasksFileParser::class, function (MockInterface $mock) {
            $mock->makePartial()
                ->shouldReceive('tasksData')
                ->once()
                ->andReturn([
                    'version' => '1.2.0',
                    'tasks' => [
                        ['id' => 'TST-123', 'team' => '999'],
                        ['id' => '12345678', 'team' => null],
                    ],
                ]);
        });

        Log::shouldReceive('log')
            ->once()
            ->with('info', '[SUCCESS - app-version] Tasks received successfully', \Mockery::on(function ($context) {
                return $context['changelogData'] == ["version" => '1.2.0', 'tasks' => [
                        ['id' => 'TST-123', 'team' => '999'], ['id' => '12345678', 'team' => null]
                    ]];
            }));

        $this->artisan(self::COMMAND_NAME)
            ->assertExitCode(Command::SUCCESS)
            ->expectsOutput('[PROCESSING] Reported tasks');

        Queue::assertPushed(PostClickupCommentsJob::class, function ($job) {
            $tasks = $job->data['tasks'];

            return $job->environment === self::ENVIRONMENT
                && $job->data['version']
                && count($tasks) === 2
                && $tasks[0]['id'] === 'TST-123'
                && $tasks[0]['team'] === '999'
                && $tasks[1]['id'] === '12345678'
                && $tasks[1]['team'] === null;
        });
    }

    /** @test */
    public function can_not_publish_comment_if_there_are_no_clickup_tasks_in_changelog(): void
    {
        Queue::fake();

        $this->mock(TasksFileParser::class, function (MockInterface $mock) {
            $mock->makePartial()
                ->shouldReceive('tasksData')
                ->once()
                ->andReturnNull();
        });

        $this->artisan(self::COMMAND_NAME)
            ->assertExitCode(Command::SUCCESS)
            ->expectsOutput('[WARNING] No task found to post comment');

        Queue::assertNotPushed(PostClickupCommentsJob::class);
    }

    /** @test */
    public function can_not_publish_comment_if_there_are_an_error_in_changelog_configuration(): void
    {
        Queue::fake();

        $this->mock(TasksFileParser::class, function (MockInterface $mock) {
            $mock->makePartial()
                ->shouldReceive('tasksData')
                ->once()
                ->andThrow(ChangelogException::forDifferentBranches());
        });

        Log::shouldReceive('log')
            ->once()
            ->with('error', '[ERROR - app-version] Error parsing changelog data', \Mockery::on(function ($context) {
                return  $context['error'] == 'The deployment branch does not match the current branch.';
            }));

        $this->artisan(self::COMMAND_NAME)
            ->assertExitCode(Command::FAILURE)
            ->expectsOutput('[ERROR] Error parsing changelog data: The deployment branch does not match the current branch.');

        Queue::assertNotPushed(PostClickupCommentsJob::class);
    }
}
