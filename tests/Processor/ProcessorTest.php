<?php

namespace Keboola\SplitByValueProcessor\Tests\Processor;

use Keboola\SplitByValueProcessor;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class ProcessorTest extends TestCase
{

    public function testProcessOneSheet() : void
    {
        $columnIndex = 0;

        $manifestManager = new \Keboola\Component\Manifest\ManifestManager(__DIR__ . '/fixtures/onefile');

        $processor = new SplitByValueProcessor\Processor(null, $columnIndex, $manifestManager);

        $fileSystem = vfsStream::setup();

        $processor->processFile(
            __DIR__ . '/fixtures/onefile/in/tables/input.csv',
            $fileSystem->url() . '/input.csv'
        );

        $this->assertEquals(
            scandir($fileSystem->url() . '/input.csv'),
            [
                '.',
                '..',
                'Berlin',
                'Bratislava',
                'Budapest',
                'Prague',
            ]
        );

        $this->assertFileEquals(
            __DIR__ . '/fixtures/onefile/out/tables/input.csv/Berlin',
            $fileSystem->url() . '/input.csv/Berlin'
        );

        $this->assertFileEquals(
            __DIR__ . '/fixtures/onefile/out/tables/input.csv/Bratislava',
            $fileSystem->url() . '/input.csv/Bratislava'
        );

        $this->assertFileEquals(
            __DIR__ . '/fixtures/onefile/out/tables/input.csv/Budapest',
            $fileSystem->url() . '/input.csv/Budapest'
        );

        $this->assertFileEquals(
            __DIR__ . '/fixtures/onefile/out/tables/input.csv/Prague',
            $fileSystem->url() . '/input.csv/Prague'
        );
    }

    public function testProcessDirectory() : void
    {
        $columnIndex = 0;

        $manifestManager = new \Keboola\Component\Manifest\ManifestManager(__DIR__ . '/fixtures/directory');

        $processor = new SplitByValueProcessor\Processor(null, $columnIndex, $manifestManager);

        $fileSystem = vfsStream::setup();

        $processor->processDir(
            __DIR__ . '/fixtures/directory/in/tables',
            $fileSystem->url() . '/'
        );

        $this->assertTrue($fileSystem->hasChild('input.csv'));
        $this->assertTrue($fileSystem->hasChild('input.csv/Berlin'));
        $this->assertTrue($fileSystem->hasChild('input.csv/Bratislava'));
        $this->assertTrue($fileSystem->hasChild('input.csv/Budapest'));
        $this->assertTrue($fileSystem->hasChild('input.csv/Prague'));
        $this->assertTrue($fileSystem->hasChild('input2.csv'));
        $this->assertTrue($fileSystem->hasChild('input2.csv/Rome'));

        $this->assertFileEquals(
            __DIR__ . '/fixtures/directory/out/tables/input.csv/Berlin',
            $fileSystem->url() . '/input.csv/Berlin'
        );

        $this->assertFileEquals(
            __DIR__ . '/fixtures/directory/out/tables/input.csv/Bratislava',
            $fileSystem->url() . '/input.csv/Bratislava'
        );

        $this->assertFileEquals(
            __DIR__ . '/fixtures/directory/out/tables/input.csv/Budapest',
            $fileSystem->url() . '/input.csv/Budapest'
        );

        $this->assertFileEquals(
            __DIR__ . '/fixtures/directory/out/tables/input.csv/Prague',
            $fileSystem->url() . '/input.csv/Prague'
        );

        $this->assertFileEquals(
            __DIR__ . '/fixtures/directory/out/tables/input2.csv/Rome',
            $fileSystem->url() . '/input2.csv/Rome'
        );
    }

    public function testProcessManifest() : void
    {
        $columnName = "city";

        $manifestManager = new \Keboola\Component\Manifest\ManifestManager(__DIR__ . '/fixtures/manifest');

        $processor = new SplitByValueProcessor\Processor($columnName, null, $manifestManager);

        $fileSystem = vfsStream::setup();

        $processor->processFile(
            __DIR__ . '/fixtures/manifest/in/tables/input.csv',
            $fileSystem->url() . '/input.csv'
        );

        $this->assertFileEquals(
            __DIR__ . '/fixtures/manifest/out/tables/input.csv.manifest',
            $fileSystem->url() . '/input.csv.manifest'
        );
    }

    public function testProcessMissingColumn() : void
    {
        $columnName = "missing_column";

        $manifestManager = new \Keboola\Component\Manifest\ManifestManager(__DIR__ . '/fixtures/missingcolumn');

        $processor = new SplitByValueProcessor\Processor($columnName, null, $manifestManager);

        $fileSystem = vfsStream::setup();

        $this->expectException("\Keboola\SplitByValueProcessor\Exception\UserException");

        $processor->processFile(
            __DIR__ . '/fixtures/missingcolumn/in/tables/input.csv',
            $fileSystem->url() . '/input.csv'
        );
    }

}
