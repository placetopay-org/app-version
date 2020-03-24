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

    /** @test * */
    public function can_get_the_path()
    {
        $this->assertEquals(storage_path('app/app-version.json'), VersionFile::path());
    }

    /** @test * */
    public function can_generate_the_file()
    {
        VersionFile::generate($this->input);

        $this->assertFileExists(VersionFile::path());
        $this->assertJsonStringEqualsJsonFile(VersionFile::path(), json_encode($this->input));
    }

    /** @test * */
    public function can_delete_the_file()
    {
        VersionFile::generate($this->input);
        $this->assertFileExists(VersionFile::path());

        VersionFile::delete();
        $this->assertFileNotExists(VersionFile::path());
    }

    /** @test * */
    public function can_read_the_sha()
    {
        VersionFile::generate($this->input);

        $this->assertEquals('abcdef', VersionFile::readSha());
    }

    /** @test */
    public function can_read_the_file()
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
