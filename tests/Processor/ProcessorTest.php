<?php

namespace Keboola\SplitByValueProcessor\Tests\Processor;

use Keboola\SplitByValueProcessor;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

class ProcessorTest extends TestCase
{

    public function testProcessOneSheet() : void
    {
        $columnName = 0;

        $processor = new SplitByValueProcessor\Processor($columnName);

        $fileSystem = vfsStream::setup();

        $processor->processFile(
            __DIR__ . '/fixtures/onefile/in/input.csv',
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
            __DIR__ . '/fixtures/onefile/out/input.csv/Berlin',
            $fileSystem->url() . '/input.csv/Berlin'
        );

        $this->assertFileEquals(
            __DIR__ . '/fixtures/onefile/out/input.csv/Bratislava',
            $fileSystem->url() . '/input.csv/Bratislava'
        );

        $this->assertFileEquals(
            __DIR__ . '/fixtures/onefile/out/input.csv/Budapest',
            $fileSystem->url() . '/input.csv/Budapest'
        );

        $this->assertFileEquals(
            __DIR__ . '/fixtures/onefile/out/input.csv/Prague',
            $fileSystem->url() . '/input.csv/Prague'
        );
    }

    public function testProcessDirectory() : void
    {
        $columnName = 0;

        $processor = new SplitByValueProcessor\Processor($columnName);

        $fileSystem = vfsStream::setup();

        $processor->processDir(
            __DIR__ . '/fixtures/directory/in',
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
            __DIR__ . '/fixtures/directory/out/input.csv/Berlin',
            $fileSystem->url() . '/input.csv/Berlin'
        );

        $this->assertFileEquals(
            __DIR__ . '/fixtures/directory/out/input.csv/Bratislava',
            $fileSystem->url() . '/input.csv/Bratislava'
        );

        $this->assertFileEquals(
            __DIR__ . '/fixtures/directory/out/input.csv/Budapest',
            $fileSystem->url() . '/input.csv/Budapest'
        );

        $this->assertFileEquals(
            __DIR__ . '/fixtures/directory/out/input.csv/Prague',
            $fileSystem->url() . '/input.csv/Prague'
        );

        $this->assertFileEquals(
            __DIR__ . '/fixtures/directory/out/input2.csv/Rome',
            $fileSystem->url() . '/input2.csv/Rome'
        );
    }

}
