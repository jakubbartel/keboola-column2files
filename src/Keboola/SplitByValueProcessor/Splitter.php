<?php

namespace Keboola\SplitByValueProcessor;

use Keboola\Component\Manifest\ManifestManager;
use Keboola\Csv;
use Keboola\SplitByValueProcessor\Exception\SplitterException;
use Keboola\SplitByValueProcessor\Exception\UserException;

class Splitter
{

    /**
     * @var string
     */
    private $filePath;

    /**
     * @var string
     */
    private $sortedFilePath;

    /**
     * @var int
     */
    private $columnIndex;

    /**
     * @var bool
     */
    private $skipHeader;

    /**
     * @var SplitterFiles
     */
    private $splitterFiles;

    /**
     * Splitter constructor.
     *
     * @param string $filePath
     * @param int $columnIndex
     * @param bool $skipHeader
     * @param SplitterFiles $splitterFiles
     * @throws UserException
     */
    public function __construct(string $filePath, int $columnIndex, bool $skipHeader, SplitterFiles $splitterFiles)
    {
        if($columnIndex < 0) {
            throw new UserException('Column index cannot be negative.');
        }

        $this->columnIndex = $columnIndex;
        $this->skipHeader = $skipHeader;
        $this->filePath = $filePath;
        $this->sortedFilePath = sprintf("%s%s", $filePath, 'sorted');
        $this->splitterFiles = $splitterFiles;

        $this->sortFile();
    }

    private function sortFile(): self
    {
        $t = microtime(true);

        $cmd = "sort --buffer-size=1000m --field-separator=\",\" --parallel=2 --key=%d --output=%s %s";

        $field = $this->columnIndex + 1;
        $inputFile = $this->filePath;
        $outputFile = $this->sortedFilePath;

        $output = '';
        $code = 0;

        exec(
            sprintf($cmd, $field, $outputFile, $inputFile),
            $output,
            $code
        );

        $this->skipHeader = false;

        echo sprintf("File sorted in %.3f", microtime(true) - $t);

        return $this;
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
     * @throws UserException
     * @throws Exception\SplitterException
     */
    public function split(string $outputDirPath)
    {
        try {
            $csvFile = new Csv\CsvReader(
                $this->sortedFilePath,
                Csv\CsvReader::DEFAULT_DELIMITER,
                Csv\CsvReader::DEFAULT_ENCLOSURE,
                Csv\CsvReader::DEFAULT_ESCAPED_BY,
                $this->skipHeader ? 1 : 0
            );
        } catch(Csv\Exception $e) {
            throw new SplitterException("Cannot open input file");
        }

        $c_i = $this->columnIndex;

        foreach($csvFile as $row) {
            if($c_i >= count($row)) {
                // throw new UserException(sprintf("Index %d out of bounds of table's %d columns", $c_i, count($row)));
                echo sprintf('skipping row with %d columns', count($row)) . "\n";
                continue;
            }

            $value = $row[$c_i];

            $file = $this->splitterFiles->getFile($outputDirPath, $value);

            try {
                $file->writeRow($row);
            } catch(Csv\Exception $e) {
                throw new SplitterException("Cannot write to output file");
            }
        }

        $this->createOutputManifest($outputDirPath);

        return $this;
    }

}
