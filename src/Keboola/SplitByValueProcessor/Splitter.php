<?php

namespace Keboola\SplitByValueProcessor;

use Keboola\Component\Manifest\ManifestManager;
use Keboola\Csv;
use Keboola\SplitByValueProcessor\Exception\UserException;

class Splitter
{

    /**
     * @var string
     */
    protected $filePath;

    /**
     * @var int
     */
    protected $columnIndex;

    /**
     * Splitter constructor.
     *
     * @param string $filePath
     * @param int $columnIndex
     * @throws UserException
     */
    public function __construct(string $filePath, int $columnIndex)
    {
        if($columnIndex < 0) {
            throw new UserException('Column index cannot be negative.');
        }

        $this->columnIndex = $columnIndex;
        $this->filePath = $filePath;
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
     * @param string $outputFilePath
     * @return string
     */
    private function getOutputManifestName(string $outputFilePath)
    {
        return ManifestManager::getManifestFilename($outputFilePath);
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

    /**
     * @param string $outputFilePath
     * @return string
     */
    private function createOutputManifest($outputFilePath)
    {
        $outputManifestPath = $this->getOutputManifestName($outputFilePath);

        touch($outputManifestPath);

        // @todo write source table manifest

        return $outputManifestPath;
    }

    /**
     * @param string $outputDirPath
     * @return Splitter
     * @throws Csv\Exception
     * @throws Csv\InvalidArgumentException
     * @throws UserException
     */
    public function split(string $outputDirPath)
    {
        $csvFile = new Csv\CsvFile($this->filePath);

        /**
         * @var Csv\CsvFile[] $outFiles column's values => output files
         */
        $outFiles = [];

        $c_i = $this->columnIndex;

        foreach($csvFile as $row) {
            if($c_i >= count($row)) {
                throw new UserException(sprintf("Index %d out of bounds of table's %d columns", $c_i, count($row)));
            }

            $value = $row[$c_i];

            if(! isset($outFiles[$value])) {
                $outputFilePath = $this->createOutputFile($outputDirPath, $value);

                $outFiles[$value] = new Csv\CsvFile($outputFilePath);
            }

            $outFiles[$value]->writeRow($row);
        }

        $this->createOutputManifest($outputDirPath);

        return $this;
    }

}
