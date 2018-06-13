<?php

namespace Keboola\SplitByValueProcessor;

use Keboola\Csv;
use Keboola\SplitByValueProcessor\Exception\SplitterException;

class SplitterFiles
{

    private const BUFFER_SIZE = 999;

    /**
     * @var Csv\CsvFile[]
     */
    private $files;

    /**
     * @var string[]
     */
    private $filesBuffer;

    /**
     *
     */
    public function __construct()
    {
        $this->files = [];
        $this->filesBuffer = [];
    }

    /**
     * @param string $outputDirPath
     * @param string $value
     * @return Csv\CsvFile
     * @throws SplitterException
     */
    public function getFile(string $outputDirPath, string $value): Csv\CsvFile
    {
        return $this->getBufferedFile($outputDirPath, $value);
    }

    /**
     * @param string $outputDirPath
     * @param string $value
     * @return Csv\CsvFile
     * @throws SplitterException
     */
    private function getBufferedFile(string $outputDirPath, string $value): Csv\CsvFile
    {
        if(!isset($this->files[$value])) {
            $this->addFileToBuffer($value);

            $outputFilePath = $this->createOutputFile($outputDirPath, $value);

            try {
                // @todo load delimiter, encl., esc. from input manifest
                $this->files[$value] = new Csv\CsvFile($outputFilePath);
            } catch(Csv\InvalidArgumentException $e) {
                throw new SplitterException('Cannot create new file');
            }
        }

        return $this->files[$value];
    }

    /**
     * @param string $value
     * @return SplitterFiles
     */
    private function addFileToBuffer(string $value): self
    {
        if(count($this->filesBuffer) > self::BUFFER_SIZE) {
            $shiftedValue = array_shift($this->filesBuffer);
            $this->files[$shiftedValue];
            unset($this->files[$shiftedValue]);
        }

        $this->filesBuffer[] = $value;

        return $this;
    }

    /**
     * @param string $outputDirPath
     * @param string $value
     * @return string
     */
    private function getOutputFileName(string $outputDirPath, string $value): string
    {
        return sprintf("%s/%s", $outputDirPath, $value);
    }

    /**
     * @param string $outputDirPath
     * @param string $value
     * @return string
     */
    private function createOutputFile(string $outputDirPath, string $value): string
    {
        $outputFilePath = $this->getOutputFileName($outputDirPath, $value);

        if(!file_exists($outputFilePath)) {
            touch($outputFilePath);
        }

        return $outputFilePath;
    }

}
