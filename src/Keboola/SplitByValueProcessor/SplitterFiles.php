<?php

namespace Keboola\SplitByValueProcessor;

use Keboola\Csv;
use Keboola\SplitByValueProcessor\Exception\SplitterException;

class SplitterFiles
{

    /**
     * @var Csv\CsvFile[]
     */
    protected $files;

    /**
     *
     */
    public function __construct()
    {
        $this->files = [];
    }

    /**
     * @param string $outputDirPath
     * @param string $value
     * @return Csv\CsvFile
     * @throws SplitterException
     */
    public function getFile(string $outputDirPath, string $value)
    {
        if(!isset($this->files[$value])) {
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
     * @param string $outputDirPath
     * @param string $value
     * @return string
     */
    private function getOutputFileName(string $outputDirPath, string $value)
    {
        return sprintf("%s/%s", $outputDirPath, $value);
    }

    /**
     * @param string $outputDirPath
     * @param string $value
     * @return string
     */
    private function createOutputFile(string $outputDirPath, string $value)
    {
        $outputFilePath = $this->getOutputFileName($outputDirPath, $value);

        touch($outputFilePath);

        return $outputFilePath;
    }

}
