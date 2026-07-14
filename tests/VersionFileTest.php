<?php

namespace PlacetoPay\AppVersion\Tests;

use PlacetoPay\AppVersion\VersionFile;

class VersionFileTest extends TestCase
{
    protected $input = [
        'sha' => 'abcdef',
        'time' => '20200315170330',
        'project' => 'test-project',
        'branch' => 'master',
    ];

    public function test_it_can_generate_the_file(): void
    {
        VersionFile::generate($this->input);

        $this->assertTrue(VersionFile::exists());
        $this->assertEquals(VersionFile::read(), $this->input);
    }

    public function test_it_can_delete_the_file(): void
    {
        VersionFile::generate($this->input);
        $this->assertTrue(VersionFile::exists());

        VersionFile::delete();
        $this->assertFalse(VersionFile::exists());
    }

    public function test_it_can_read_the_sha(): void
    {
        VersionFile::generate($this->input);

        $this->assertEquals('abcdef', VersionFile::readSha());
    }

    public function test_it_can_read_the_file(): void
    {
        VersionFile::generate($this->input);

        $content = VersionFile::read();

        $this->assertEquals($this->input, $content);
    }

    protected function tearDown(): void
    {
        VersionFile::delete();
        parent::tearDown();
    }
}
