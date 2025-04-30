<?php

namespace PlacetoPay\AppVersion\Tests\Helpers;

use Illuminate\Support\Arr;
use PHPUnit\Framework\TestCase;
use PlacetoPay\AppVersion\Exceptions\ChangelogException;
use PlacetoPay\AppVersion\Helpers\Changelog;

class ChangelogHelperTest extends TestCase
{
    private const VERSION = [
        'sha' => 'TESTING_SHA',
        'time' => '2025-04-29T11:19:34-05:00',
        'branch' => 'testing',
        'version' => '1.0.0',
    ];

    public function buildChangelogMock(string $currenCommit, string $currentBranch, ?string $differences = null): Changelog
    {
        $mock = $this->createPartialMock(Changelog::class, ['commitInformation', 'changelogDiff']);
        $mock->expects($this->once())
            ->method('commitInformation')
            ->willReturn([
                'currentCommit' => $currenCommit,
                'currentBranch' => $currentBranch,
            ]);
        $mock->expects(isset($differences) ? $this->once() : $this->never())
            ->method('changelogDiff')
            ->willReturn($differences);

        return $mock;
    }

    /** @test */
    public function can_throws_exception_if_deploy_commit_or_branch_is_missing(): void
    {
        $changelog = new Changelog();
        $this->expectException(ChangelogException::class);
        $this->expectExceptionMessage('Could not get commit or branch information from the deployment.');

        $changelog->lastChanges(['sha' => null, 'branch' => 'master']);
    }

    /** @test */
    public function can_throws_exception_if_branches_do_not_match(): void
    {
        $this->expectException(ChangelogException::class);
        $this->expectExceptionMessage('The deployment branch does not match the current branch');
        $changelog = $this->buildChangelogMock('develop', 'abcdef');

        $changelog->lastChanges(['sha' => 'abcdef', 'branch' => 'master']);
    }

    /** @test */
    public function can_returns_changelog_changes_when_commit_has_differences(): void
    {
        $changelog = $this->buildChangelogMock(
            'abcdef',
            'testing',
            "-## Unreleased\n
                                +## 1.1.0 (2025-04-28)\n
                                +- Change [CU-12345](https://app.clickup.com/t/789/CU-12345)
                                +- Change (https://app.clickup.com/t/789/CU-12345)
                                +  -  Change (https://app.clickup.com/t/789/CU-12389)
                                +- Change [868c4frhp](https://app.clickup.com/t/868c4frhp)
                                +- Change [@user](https://bitbucket.org/user/) [#CU-12345](https://app.clickup.com/t/789/CU-12345)
                                +Change [CU-12345](https://app.clickup.com/t/789/CU-12345)
                                +Change (https://app.clickup.com/t/789/CU-12345)
                                Change [868c4frhp](https://app.clickup.com/t/868c4frhp)"
        );

        $result = $changelog->lastChanges(self::VERSION);

        $this->assertEquals(['version' => '1.1.0', 'information' => [
            'Change [CU-12345](https://app.clickup.com/t/789/CU-12345)',
            'Change (https://app.clickup.com/t/789/CU-12345)',
            'Change (https://app.clickup.com/t/789/CU-12389)',
            'Change [868c4frhp](https://app.clickup.com/t/868c4frhp)',
            'Change [@user](https://bitbucket.org/user/) [#CU-12345](https://app.clickup.com/t/789/CU-12345)',
            'Change [CU-12345](https://app.clickup.com/t/789/CU-12345)',
            'Change (https://app.clickup.com/t/789/CU-12345)',
            'Change [868c4frhp](https://app.clickup.com/t/868c4frhp)',
        ]], $result);
    }

    /** @test */
    public function can_returns_empty_array_if_no_version_is_found(): void
    {
        $changelog = $this->buildChangelogMock(
            'abcdef',
            'testing',
            '+- Change (https://app.clickup.com/t/789/CU-12345)
                    +  -  Change (https://app.clickup.com/t/789/CU-12389)
                    +Change [CU-12345](https://app.clickup.com/t/789/CU-12345)
                    Change [868c4frhp](https://app.clickup.com/t/868c4frhp)'
        );
        $result = $changelog->lastChanges(self::VERSION);

        $this->assertEmpty($result);
    }

    /** @test */
    public function can_returns_version_and_information_when_valid_diff_is_provided(): void
    {
        $changelog = $this->buildChangelogMock(
            'abcdef',
            'testing',
            '+- Change (https://app.clickup.com/t/789/CU-12345)
                    +  -  Change (https://app.clickup.com/t/789/CU-12389)
                    +Change [CU-12345](https://app.clickup.com/t/789/CU-12345)
                    Change [868c4frhp](https://app.clickup.com/t/868c4frhp)'
        );
        $result = $changelog->lastChanges(self::VERSION);

        $this->assertEmpty($result);
    }

    /** @test */
    public function can_handles_unreleased_section_with_tasks(): void
    {
        $changelog = $this->buildChangelogMock(
            'abcdef',
            'testing',
            '## Unreleased
+### Removed
+
+- `Testing` "changes" [@user](https://bitbucket.org/user/) [#PT-123456](https://app.clickup.com/t/123456/PT-123456)
+    - Other testing changes
+    - Other change
+    - Other testing [TK-97122](https://app.clickup.com/t/11111/TK-97122)
+
+- Other change [868c4frhp](https://app.clickup.com/t/123a4asdf)
+
+### Fixed
+
+- Fix change. [@user](https://bitbucket.org/user/) [#PT-2222](https://app.clickup.com/t/22333/PT-2222)
+
+### Updated
+
+- Update [@user](https://bitbucket.org/user/) [#PT-5432](https://app.clickup.com/t/323232/PT-5432)
+
+## [6.1.14 (2024-12-11)](https://bitbucket.org/project/commits/tag/6.1.14)
+
+### Fixed
+
+- other task [@user](https://bitbucket.org/user/) [#PT_1234](https://app.clickup.com/t/123456/PT-1234)
+'
        );

        $result = $changelog->lastChanges(self::VERSION);

        $this->assertEquals(['version' => 'Unreleased', 'information' => [
            '### Removed',
            '`Testing` "changes" [@user](https://bitbucket.org/user/) [#PT-123456](https://app.clickup.com/t/123456/PT-123456)',
            'Other testing changes',
            'Other change',
            'Other testing [TK-97122](https://app.clickup.com/t/11111/TK-97122)',
            'Other change [868c4frhp](https://app.clickup.com/t/123a4asdf)',
            '### Fixed',
            'Fix change. [@user](https://bitbucket.org/user/) [#PT-2222](https://app.clickup.com/t/22333/PT-2222)',
            '### Updated',
            'Update [@user](https://bitbucket.org/user/) [#PT-5432](https://app.clickup.com/t/323232/PT-5432)',
        ]], $result);
    }

    /**
     * @test
     * @dataProvider versionFormatsProvider
     */
    public function can_process_different_version_format(string $versionHeader, string $expectedVersion): void
    {
        $changelog = $this->buildChangelogMock(
            'abcdef',
            'testing',
            "
            $versionHeader

+- A Change [CU-9876](https://app.clickup.com/t/123/CU-9876)
+- Task without link
+
+[2.0.0]
+- Other Change [CU-4321](https://app.clickup.com/t/123/CU-4321)
+"
        );

        $result = $changelog->lastChanges(self::VERSION);

        $this->assertEquals($expectedVersion, $result['version']);
        $this->assertCount(2, $result['information']);
        $this->assertEquals([
            'A Change [CU-9876](https://app.clickup.com/t/123/CU-9876)',
            'Task without link',
        ], $result['information']);
    }

    public function versionFormatsProvider(): array
    {
        return [
            ['+## Unreleased', 'Unreleased'],
            ['+## [Unreleased]', 'Unreleased'],
            ['+## 1.0.0', '1.0.0'],
            ['+## [1.0.0]', '1.0.0'],
            ['+## 1.0.0 (2024-01-01)', '1.0.0'],
            ['+## [1.0.0 (2024-01-01)]', '1.0.0'],
            ['+## [1.0.0 (2024-01-01)](https://bitbucket.org/project/commits/tag/6.1.15)', '1.0.0'],
            ['+Unreleased', 'Unreleased'],
            ['+[Unreleased]', 'Unreleased'],
            ['+1.0.0', '1.0.0'],
            ['+[1.0.0]', '1.0.0'],
            ['+1.0.0 (2024-01-01)', '1.0.0'],
            ['+[1.0.0 (2024-01-01)]', '1.0.0'],
            ['+[1.0.0 (2024-01-01)](https://bitbucket.org/project/commits/tag/6.1.15)', '1.0.0'],
        ];
    }

    /**
     * @test
     * @dataProvider emptyDataProvider()
     */
    public function can_process_empty_changes(string $diff = null): void
    {
        $changelog = $this->buildChangelogMock('abcdef', 'testing', $diff);

        $result = $changelog->lastChanges(self::VERSION);
        $this->assertEmpty(Arr::get($result, 'information', $result));
    }

    public function emptyDataProvider(): array
    {
        return [
            ['+'],
            [''],
            ["+\n"],
            ['+##Unreleased'],
            ['+## [Unreleased]'],
            ['+## 1.0.0'],
            ['+## [1.0.0 (2024-01-01)]'],
            ['+[1.0.0 (2024-01-01)](https://bitbucket.org/project/commits/tag/6.1.15)'],
        ];
    }
}
