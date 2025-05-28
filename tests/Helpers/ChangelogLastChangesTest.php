<?php

namespace PlacetoPay\AppVersion\Tests\Helpers;

use PlacetoPay\AppVersion\Exceptions\ChangelogException;
use PlacetoPay\AppVersion\Helpers\ChangelogLastChanges;
use PlacetoPay\AppVersion\Tests\TestCase;

class ChangelogLastChangesTest extends TestCase
{
    private ChangelogLastChanges $changelog;
    private string $tempFilePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->changelog = new ChangelogLastChanges();
        $this->tempFilePath = sys_get_temp_dir() . '/test_changelog.md';
    }

    /** @test */
    public function it_throws_exception_if_file_does_not_exist(): void
    {
        $path = '/ruta/no/existente.md';
        $this->expectException(ChangelogException::class);
        $this->expectExceptionMessage("/ruta/no/existente.md file not found.");
        $this->changelog->read($path);
    }

    /** @test */
    public function it_can_resolve_when_file_is_empty(): void
    {
        file_put_contents($this->tempFilePath, '');
        $this->changelog->read($this->tempFilePath);
        $this->assertEmpty($this->changelog->content());
        $this->assertNull($this->changelog->version());
    }

    /** @test */
    public function it_returns_resolve_when_file_has_no_version(): void
    {
        file_put_contents($this->tempFilePath, '
            - A Change [CU-9876](https://app.clickup.com/t/123/CU-9876)
            - Task without link
        ');
        $this->changelog->read($this->tempFilePath);
        $this->assertEmpty($this->changelog->content());
        $this->assertNull($this->changelog->version());
    }

    /** @test */
    public function it_extracts_content_correctly_from_changelog(): void
    {
        file_put_contents($this->tempFilePath, '# Changelog
## [Unreleased]

## [6.1.15 (2024-12-12)](https://bitbucket.org/project/commits/tag/6.1.15)
### Removed
- `Testing` "changes" [@user](https://bitbucket.org/user/) [#PT-7841](https://app.clickup.com/t/123456/PT-123456)
    - Other testing changes
    - Other change
    - Other testing [PT-9770](https://app.clickup.com/t/11111/TK-9712)
- 
- Other change [868c4frhp](https://app.clickup.com/t/123a4asdf)
### Fixed
- Fix change. [@user](https://bitbucket.org/user/) [#PT-2222](https://app.clickup.com/t/22333/PT-2222)
### Updated
- Update [@user](https://bitbucket.org/user/) [#PT-5432](https://app.clickup.com/t/323232/PT-5432)
## [6.1.14 (2024-12-11)](https://bitbucket.org/project/commits/tag/6.1.14)
### Fixed
- other task [@user](https://bitbucket.org/user/) [#PT_1234](https://app.clickup.com/t/123456/PT-1234)
');
        $this->changelog->read($this->tempFilePath);
        $this->assertEquals('6.1.15', $this->changelog->version());
        $this->assertCount(10, $this->changelog->content());
        $this->assertEquals([
            'Removed',
            '`Testing` "changes" [@user](https://bitbucket.org/user/) [#PT-7841](https://app.clickup.com/t/123456/PT-123456)',
            'Other testing changes',
            'Other change',
            'Other testing [PT-9770](https://app.clickup.com/t/11111/TK-9712)',
            'Other change [868c4frhp](https://app.clickup.com/t/123a4asdf)',
            'Fixed',
            'Fix change. [@user](https://bitbucket.org/user/) [#PT-2222](https://app.clickup.com/t/22333/PT-2222)',
            'Updated',
            'Update [@user](https://bitbucket.org/user/) [#PT-5432](https://app.clickup.com/t/323232/PT-5432)',
        ], $this->changelog->content());
    }

    /** @test */
    public function it_ignore_unreleased_section(): void
    {
        file_put_contents($this->tempFilePath, '## Unreleased
- Fix the bug [CU-1111](https://app.clickup.com/t/789/CU-1111)
## 3.0.0 (2024-01-01)
- Fix a bug [CU-12343](https://app.clickup.com/t/789/CU-12343)
');
        $this->changelog->read($this->tempFilePath);
        $this->assertEquals('Unreleased', $this->changelog->version());
        $this->assertEmpty($this->changelog->content());
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
        $this->changelog->read($this->tempFilePath);
        $this->assertEquals($expectedVersion, $this->changelog->version());
        $this->assertCount(2, $this->changelog->content());
        $this->assertEquals([
            'A Change [CU-9876](https://app.clickup.com/t/123/CU-9876)',
            'Task without link',
        ], $this->changelog->content());
    }
    public function versionFormatsProvider(): array
    {
        return [
            ['## 1.0.0', '1.0.0'],
            ['## [1.0.0]', '1.0.0'],
            ['## 1.0.0 (2024-01-01)', '1.0.0'],
            ['## [1.0.0 (2024-01-01)]', '1.0.0'],
            ['## [1.0.0 (2024-01-01)](https://bitbucket.org/project/commits/tag/6.1.15)', '1.0.0'],
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
    public function it_can_process_valid_change_formats(string $changeLogEntry, string $expectedChange): void
    {
        file_put_contents($this->tempFilePath, "## 1.0.0 (2024-01-01)
$changeLogEntry
");
        $this->changelog->read($this->tempFilePath);
        $this->assertEquals($this->changelog->version(), '1.0.0');
        $this->assertCount(1, $this->changelog->content());
        $this->assertEquals([$expectedChange], $this->changelog->content());
    }
    public function changeFormatsProvider(): array
    {
        return [
            ['- Change [@user](https://bitbucket.org/user/) [#CU-12345](https://app.clickup.com/t/789/CU-12345)', 'Change [@user](https://bitbucket.org/user/) [#CU-12345](https://app.clickup.com/t/789/CU-12345)'],
            ['- Change [CU-12345](https://app.clickup.com/t/789/CU-12345)', 'Change [CU-12345](https://app.clickup.com/t/789/CU-12345)'],
            ['- Change (https://app.clickup.com/t/789/CU-12345)', 'Change (https://app.clickup.com/t/789/CU-12345)'],
            ['- Change [868c4frhp](https://app.clickup.com/t/868c4frhp)', 'Change [868c4frhp](https://app.clickup.com/t/868c4frhp)'],
            ['- Change [@user](https://bitbucket.org/user/) [#CU-12345](https://app.clickup.com/t/789/CU-12345)', 'Change [@user](https://bitbucket.org/user/) [#CU-12345](https://app.clickup.com/t/789/CU-12345)'],
            ['* Change [@user](https://bitbucket.org/user/) [#CU-12345](https://app.clickup.com/t/789/CU-12345)', 'Change [@user](https://bitbucket.org/user/) [#CU-12345](https://app.clickup.com/t/789/CU-12345)'],
            ['* Change [CU-12345](https://app.clickup.com/t/789/CU-12345)', 'Change [CU-12345](https://app.clickup.com/t/789/CU-12345)'],
            ['* Change (https://app.clickup.com/t/789/CU-12345)', 'Change (https://app.clickup.com/t/789/CU-12345)'],
            ['* Change [868c4frhp](https://app.clickup.com/t/868c4frhp)', 'Change [868c4frhp](https://app.clickup.com/t/868c4frhp)'],
            ['* Change [@user](https://bitbucket.org/user/) [#CU-12345](https://app.clickup.com/t/789/CU-12345)', 'Change [@user](https://bitbucket.org/user/) [#CU-12345](https://app.clickup.com/t/789/CU-12345)'],
            ['Change [CU-12345](https://app.clickup.com/t/789/CU-12345)', 'Change [CU-12345](https://app.clickup.com/t/789/CU-12345)'],
            ['Change (https://app.clickup.com/t/789/CU-12345)', 'Change (https://app.clickup.com/t/789/CU-12345)'],
            ['Change [868c4frhp](https://app.clickup.com/t/868c4frhp)', 'Change [868c4frhp](https://app.clickup.com/t/868c4frhp)'],
        ];
    }
}
