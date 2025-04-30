<?php

namespace PlacetoPay\AppVersion\Tests\Helpers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\MockObject\MockObject;
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

    public function buildChangelogMock(string $currenCommit, string $currentBranch): MockObject
    {
        $mock = $this->createPartialMock(Changelog::class, ['commitInformation', 'changelogDiff']);
        $mock->expects($this->once())
            ->method('commitInformation')
            ->willReturn([
                'currentCommit' => $currenCommit,
                'currentBranch' => $currentBranch,
            ]);

        return $mock;
    }

    /** @test */
    public function can_throws_exception_if_deploy_commit_or_branch_is_missing(): void
    {
        $changelog = new Changelog();
        $this->expectException(ChangelogException::class);
        $this->expectExceptionMessage('Could not get commit or branch information from the deployment.');

        $changelog->lastChanges(['sha' => null, 'branch' => 'master'], 'changelog.md');
    }

    /** @test */
    public function can_throws_exception_if_branches_do_not_match(): void
    {
        $this->expectException(ChangelogException::class);
        $this->expectExceptionMessage('The deployment branch does not match the current branch');
        $changelog = $this->buildChangelogMock('develop', 'abcdef');
        $changelog->expects($this->never())->method('changelogDiff');

        /** @var $changelog Changelog */
        $changelog->lastChanges(['sha' => 'abcdef', 'branch' => 'master'], 'changelog.md');
    }

    /** @test */
    public function can_returns_changelog_changes_when_commit_has_differences(): void
    {
        $changelog = $this->buildChangelogMock(
            'abcdef',
            'testing');
        $changelog->expects($this->once())
            ->method('changelogDiff')
            ->willReturn(
                "-## Unreleased\n
                    +## 1.1.0 (2025-04-28)\n
                    +- Change [CU-12345](https://app.clickup.com/t/789/CU-12345)
                    +- Change (https://app.clickup.com/t/789/CU-12345)
                    +  -  Change (https://app.clickup.com/t/789/CU-12389)
                    +- Change [868c4frhp](https://app.clickup.com/t/868c4frhp)
                    + - Change [@user](https://bitbucket.org/user/) [#CU-12345](https://app.clickup.com/t/789/CU-12345)
                    +Change [CU-12345](https://app.clickup.com/t/789/CU-12345)
                    +Change (https://app.clickup.com/t/789/CU-12345)
                    Change [868c4frhp](https://app.clickup.com/t/868c4frhp)"
            );

        /** @var $changelog Changelog */
        $result = $changelog->lastChanges(self::VERSION, 'changelog.md');

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
        $changelog = $this->buildChangelogMock('abcdef', 'testing',);
        $changelog->expects($this->once())->method('changelogDiff')
            ->willReturn(
                '+- Change (https://app.clickup.com/t/789/CU-12345)
                +  -  Change (https://app.clickup.com/t/789/CU-12389)
                +Change [CU-12345](https://app.clickup.com/t/789/CU-12345)
                Change [868c4frhp](https://app.clickup.com/t/868c4frhp)'
            );

        /** @var $changelog Changelog */
        $result = $changelog->lastChanges(self::VERSION, 'changelog.md');

        $this->assertEmpty($result);
    }

    /** @test */
    public function can_returns_version_and_information_when_valid_diff_is_provided(): void
    {
        $changelog = $this->buildChangelogMock(
            'abcdef',
            'testing',

        );
        $changelog->expects($this->once())->method('changelogDiff')
            ->willReturn(
                '+- Change (https://app.clickup.com/t/789/CU-12345)
                    +  -  Change (https://app.clickup.com/t/789/CU-12389)
                    +Change [CU-12345](https://app.clickup.com/t/789/CU-12345)
                    Change [868c4frhp](https://app.clickup.com/t/868c4frhp)'
            );

        /** @var $changelog Changelog */
        $result = $changelog->lastChanges(self::VERSION, 'changelog.md');

        $this->assertEmpty($result);
    }

    /** @test */
    public function can_handles_unreleased_section_with_tasks(): void
    {
        $changelog = $this->buildChangelogMock('abcdef', 'testing');
        $changelog->expects($this->once())->method('changelogDiff')
            ->willReturn('## Unreleased
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
+');

        /** @var $changelog Changelog */

        $result = $changelog->lastChanges(self::VERSION, 'changelog.md');

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
        $changelog = $this->buildChangelogMock('abcdef', 'testing');
        $changelog->expects($this->once())->method('changelogDiff')
            ->willReturn("
            $versionHeader

+- A Change [CU-9876](https://app.clickup.com/t/123/CU-9876)
+- Task without link
+
+[2.0.0]
+- Other Change [CU-4321](https://app.clickup.com/t/123/CU-4321)
+");

        /** @var $changelog Changelog */
        $result = $changelog->lastChanges(self::VERSION, 'changelog.md');

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

        Log::shouldReceive('log')
            ->once()
            ->with('warning', "[WARNING - app-version] No changes were found in the file 'changelog.md'.", \Mockery::on(function ($context) {
                return $context['currentCommit'] === 'abcdef'
                    && $context['currentBranch'] === 'testing'
                    && $context['deployCommit'] === 'TESTING_SHA'
                    && $context['deployBranch'] === 'testing';
            }));


        $changelog = $this->buildChangelogMock('abcdef', 'testing', $diff);

        $result = $changelog->lastChanges(self::VERSION, 'changelog.md');
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
