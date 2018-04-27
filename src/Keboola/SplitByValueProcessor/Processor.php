<?php declare(strict_types = 1);

namespace Keboola\SplitByValueProcessor;

use Symfony\Component\Finder\Finder;

class Processor
{

    /**
     * @var string
     */
    private $columnIndex;

    /**
     * Processor constructor.
     *
     * @param int $columnIndex
     */
    public function __construct(int $columnIndex)
    {
        $this->columnIndex = $columnIndex;
    }

    /**
     * Look up all files that are not manifests.
     *
     * @param string $inFilesDirPath
     * @param string $outFilesDirPath
     * @return array
     */
    private function getFilesToProcess(string $inFilesDirPath, string $outFilesDirPath): array
    {
        $inOuts = [];

        $finder = new Finder();
        $finder->files()->in($inFilesDirPath);

        $finder->files()->notName("*.manifest");

        foreach($finder as $file) {
            // not in a subfolder
            if($file->getRelativePath() === '') {
                $outFilePath = sprintf('%s/%s.csv',
                    $outFilesDirPath,
                    $file->getBasename(sprintf('.%s', $file->getExtension()))
                );
            } else {
                $outFilePath = sprintf('%s/%s/%s.csv',
                    $outFilesDirPath,
                    $file->getRelativePath(),
                    $file->getBasename(sprintf('.%s', $file->getExtension()))
                );
            }

            $inOuts[] = [
                'input' => $file->getPathname(),
                'output' => $outFilePath
            ];
        }

        return $inOuts;
    }

    /**
     * @param string $inFilePath
     * @param string $outPath
     * @return Processor
     */
    public function processFile(string $inFilePath, string $outPath): self
    {
        $splitter = new Splitter($inFilePath, $this->columnIndex);

        mkdir($outPath);

        $splitter->split($outPath);

        return $this;
    }

    /**
     * @param string $inFilesDirPath
     * @param string $outFilesDirPath
     * @return Processor
     */
    public function processDir(string $inFilesDirPath, string $outFilesDirPath): self
    {
        $inOuts = $this->getFilesToProcess($inFilesDirPath, $outFilesDirPath);

        foreach($inOuts as $inOut) {
            $this->processFile($inOut['input'], $inOut['output']);
        }

        return $this;
    }

}
