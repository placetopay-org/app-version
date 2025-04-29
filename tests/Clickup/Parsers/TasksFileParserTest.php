<?php

namespace PlacetoPay\AppVersion\Tests\Clickup\Parsers;

use PHPUnit\Framework\TestCase;
use PlacetoPay\AppVersion\Clickup\Parsers\TasksFileParser;
use PlacetoPay\AppVersion\Helpers\Changelog;

class TasksFileParserTest extends TestCase
{
    private const VERSION = [
        "sha" => "TESTING_SHA",
        "time" => "2025-04-29T11:19:34-05:00",
        "branch" => "master",
        "version" => "3.12.0"
    ];

    public function buildParser(array $changelogData, ?string $version = null): TasksFileParser
    {
        $mock = $this->createPartialMock(Changelog::class, ['lastChanges']);
        $mock->expects($this->once())
            ->method('lastChanges')
            ->willReturn($version ? ['version' => $version, 'information' => $changelogData] : $changelogData);

        return new TasksFileParser($mock);
    }

    protected function setUp(): void
    {
        parent::setUp();

    }

    /** @test */
    public function can_returns_null_when_no_tasks_are_found(): void
    {
        $parser = $this->buildParser([]);

        $result = $parser->tasksData(self::VERSION);

        $this->assertNull($result);
    }

    /**
     * @test
     * @dataProvider changeFormatsProvider
     */
    public function can_process_valid_change_formats(string $changeLogEntry, string $expectedTaskId, ?string $expectedTeamId = null): void
    {
        $parser = $this->buildParser([$changeLogEntry], self::VERSION['version']);

        $result = $parser->tasksData(self::VERSION);

        $this->assertNotNull($result);
        $this->assertCount(1, $result['tasks']);
        $this->assertEquals($expectedTaskId, $result['tasks'][0]['id']);

        if ($expectedTeamId !== null) {
            $this->assertEquals($expectedTeamId, $result['tasks'][0]['team']);
        }
    }

    public function changeFormatsProvider(): array
    {
        return [
            ['Change [@user](https://bitbucket.org/user/) [#CU-12345](https://app.clickup.com/t/789/CU-12345)', 'CU-12345', '789'],
            ['Change [CU-12345](https://app.clickup.com/t/789/CU-12345)', 'CU-12345', '789'],
            ['Change (https://app.clickup.com/t/789/CU-12345)', 'CU-12345', '789'],
            ['Change [868c4frhp](https://app.clickup.com/t/868c4frhp)', '868c4frhp'],
            ['Change [@user](https://bitbucket.org/user/) [#CU-12345](https://app.clickup.com/t/789/CU-12345)', 'CU-12345', '789'],
        ];
    }
}
