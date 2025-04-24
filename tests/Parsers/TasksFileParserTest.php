<?php

namespace PlacetoPay\AppVersion\Tests\Parsers;

use PHPUnit\Framework\TestCase;
use PlacetoPay\AppVersion\Exceptions\ReadFileException;
use PlacetoPay\AppVersion\Parsers\TasksFileParser;

class TasksFileParserTest extends TestCase
{
    private TasksFileParser $parser;
    private string $tempFilePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new TasksFileParser();
        $this->tempFilePath = sys_get_temp_dir() . '/changelog_test.md';
    }

    /** @test */
    public function it_throws_exception_if_file_does_not_exist(): void
    {
        $path = '/ruta/no/existente.md';

        $this->expectException(ReadFileException::class);
        $this->expectExceptionMessage("File $path does not exist.");

        $this->parser->getTasksData($path);
    }

    /** @test */
    public function it_returns_null_when_no_tasks_are_found(): void
    {
        file_put_contents($this->tempFilePath, "## 1.0.0 (2024-01-01)\n\n- Testing change");

        $result = $this->parser->getTasksData($this->tempFilePath);

        $this->assertNull($result);
    }

    /** @test */
    public function it_returns_null_when_file_is_empty(): void
    {
        file_put_contents($this->tempFilePath, '');

        $result = $this->parser->getTasksData($this->tempFilePath);

        $this->assertNull($result);
    }

    /** @test */
    public function it_returns_null_if_file_has_no_version(): void
    {
        file_put_contents($this->tempFilePath, '
            - A Change [CU-9876](https://app.clickup.com/t/123/CU-9876)
            - Task without link
        ');

        $result = $this->parser->getTasksData($this->tempFilePath);

        $this->assertNull($result);
    }

    /** @test */
    public function it_extracts_tasks_correctly_from_changelog(): void
    {
        file_put_contents($this->tempFilePath, '# Changelog
## [Unreleased]

## [6.1.15 (2024-12-12)](https://bitbucket.org/project/commits/tag/6.1.15)

### Removed

- `Testing` "changes" [@user](https://bitbucket.org/user/) [#PT-7841](https://app.clickup.com/t/123456/PT-123456)
    - Other testing changes
    - Other change
    - Other testing [PT-9770](https://app.clickup.com/t/11111/TK-9712)

- Other change [868c4frhp](https://app.clickup.com/t/123a4asdf)

### Fixed

- Fix change. [@user](https://bitbucket.org/user/) [#PT-2222](https://app.clickup.com/t/22333/PT-2222)

### Updated

- Update [@user](https://bitbucket.org/user/) [#PT-5432](https://app.clickup.com/t/323232/PT-5432)

## [6.1.14 (2024-12-11)](https://bitbucket.org/project/commits/tag/6.1.14)

### Fixed

- other task [@user](https://bitbucket.org/user/) [#PT_1234](https://app.clickup.com/t/123456/PT-1234)
');

        $result = $this->parser->getTasksData($this->tempFilePath);

        $this->assertNotNull($result);
        $this->assertEquals('6.1.15', $result['version']);
        $this->assertCount(5, $result['tasks']);

        $this->assertEquals('PT-123456', $result['tasks'][0]['id']);
        $this->assertEquals('123456', $result['tasks'][0]['team']);

        $this->assertEquals('TK-9712', $result['tasks'][1]['id']);
        $this->assertEquals('11111', $result['tasks'][1]['team']);

        $this->assertEquals('123a4asdf', $result['tasks'][2]['id']);
        $this->assertNull($result['tasks'][2]['team']);

        $this->assertEquals('PT-2222', $result['tasks'][3]['id']);
        $this->assertEquals('22333', $result['tasks'][3]['team']);

        $this->assertEquals('PT-5432', $result['tasks'][4]['id']);
        $this->assertEquals('323232', $result['tasks'][4]['team']);
    }

    /** @test */
    public function it_handles_unreleased_section_with_tasks(): void
    {
        file_put_contents($this->tempFilePath, '## Unreleased
- Fix the bug [CU-1111](https://app.clickup.com/t/789/CU-1111)

## 3.0.0 (2024-01-01)

- Fix a bug [CU-12343](https://app.clickup.com/t/789/CU-12343)
');

        $result = $this->parser->getTasksData($this->tempFilePath);

        $this->assertNotNull($result);
        $this->assertEquals('Unreleased', $result['version']);
        $this->assertCount(1, $result['tasks']);
        $this->assertEquals('CU-1111', $result['tasks'][0]['id']);
        $this->assertEquals('789', $result['tasks'][0]['team']);
    }

    /** @test */
    public function it_ignore_unreleased_section_without_tasks(): void
    {
        file_put_contents($this->tempFilePath, '## Unreleased

## 2.0.0 (2024-01-01)

- Fix other bug [CU-67890](https://app.clickup.com/t/789/CU-67890)
');

        $result = $this->parser->getTasksData($this->tempFilePath);

        $this->assertNotNull($result);
        $this->assertEquals('2.0.0', $result['version']);
        $this->assertCount(1, $result['tasks']);
        $this->assertEquals('CU-67890', $result['tasks'][0]['id']);
        $this->assertEquals('789', $result['tasks'][0]['team']);
    }

    /**
     * @test
     * @dataProvider versionFormatsProvider
     */
    public function it_can_process_different_version_format(string $versionHeader, string $expectedVersion): void
    {
        file_put_contents($this->tempFilePath, "$versionHeader

- A Change [CU-9876](https://app.clickup.com/t/123/CU-9876)
- Task without link

[2.0.0]
- Other Change [CU-4321](https://app.clickup.com/t/123/CU-4321)
");

        $result = $this->parser->getTasksData($this->tempFilePath);

        $this->assertNotNull($result);
        $this->assertEquals($expectedVersion, $result['version']);
        $this->assertCount(1, $result['tasks']);
        $this->assertEquals('CU-9876', $result['tasks'][0]['id']);
        $this->assertEquals('123', $result['tasks'][0]['team']);
    }

    public function versionFormatsProvider(): array
    {
        return [
            ['## Unreleased', 'Unreleased'],
            ['## [Unreleased]', 'Unreleased'],
            ['## 1.0.0', '1.0.0'],
            ['## [1.0.0]', '1.0.0'],
            ['## 1.0.0 (2024-01-01)', '1.0.0'],
            ['## [1.0.0 (2024-01-01)]', '1.0.0'],
            ['## [1.0.0 (2024-01-01)](https://bitbucket.org/project/commits/tag/6.1.15)', '1.0.0'],
            ['Unreleased', 'Unreleased'],
            ['[Unreleased]', 'Unreleased'],
            ['1.0.0', '1.0.0'],
            ['[1.0.0]', '1.0.0'],
            ['1.0.0 (2024-01-01)', '1.0.0'],
            ['[1.0.0 (2024-01-01)]', '1.0.0'],
            ['[1.0.0 (2024-01-01)](https://bitbucket.org/project/commits/tag/6.1.15)', '1.0.0'],
        ];
    }

    /**
     * @test
     * @dataProvider changeFormatsProvider
     */
    public function it_can_process_valid_change_formats(string $changeLogEntry, string $expectedTaskId, ?string $expectedTeamId = null): void
    {
        file_put_contents($this->tempFilePath, "## 1.0.0 (2024-01-01)
$changeLogEntry
");

        $result = $this->parser->getTasksData($this->tempFilePath);

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
            ['- Change [@user](https://bitbucket.org/user/) [#CU-12345](https://app.clickup.com/t/789/CU-12345)', 'CU-12345', '789'],
            ['- Change [CU-12345](https://app.clickup.com/t/789/CU-12345)', 'CU-12345', '789'],
            ['- Change (https://app.clickup.com/t/789/CU-12345)', 'CU-12345', '789'],
            ['- Change [868c4frhp](https://app.clickup.com/t/868c4frhp)', '868c4frhp'],
            ['- Change [@user](https://bitbucket.org/user/) [#CU-12345](https://app.clickup.com/t/789/CU-12345)', 'CU-12345', '789'],
            ['Change [CU-12345](https://app.clickup.com/t/789/CU-12345)', 'CU-12345', '789'],
            ['Change (https://app.clickup.com/t/789/CU-12345)', 'CU-12345', '789'],
            ['Change [868c4frhp](https://app.clickup.com/t/868c4frhp)', '868c4frhp'],
        ];
    }
}
