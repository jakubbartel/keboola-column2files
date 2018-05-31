<?php declare(strict_types = 1);

namespace Keboola\SplitByValueProcessor;

use Keboola\Component\Manifest\ManifestManager;
use Keboola\SplitByValueProcessor\Exception\UserException;
use Symfony\Component\Finder\Finder;

class Processor
{

    /**
     * @var int|null
     */
    private $columnIndex;

    /**
     * @var string|null
     */
    private $columnName;

    /**
     * @var ManifestManager
     */
    private $manifestManager;

    /**
     * @var SplitterFiles
     */
    private $splitterFiles;

    /**
     * Processor constructor.
     *
     * @param string $columnName
     * @param int $columnIndex
     * @param ManifestManager $manifestManager
     * @throws UserException
     */
    public function __construct(string $columnName=null, int $columnIndex=null, ManifestManager $manifestManager)
    {
        if($columnName === null && $columnIndex === null) {
            throw new UserException("Processor needs to have one of column name or index defined");
        }

        $this->columnName = $columnName;
        $this->columnIndex = $columnIndex;

        $this->manifestManager = $manifestManager;

        $this->splitterFiles = new SplitterFiles();
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
        $finder->files()->in($inFilesDirPath)->depth(0);

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
     * Look up all directories that are not manifests.
     *
     * @param string $inFilesDirPath
     * @param string $outFilesDirPath
     * @return array
     */
    private function getSlicedFilesToProcess(string $inFilesDirPath, string $outFilesDirPath): array
    {
        $inOuts = [];

        $finder = new Finder();
        $finder->directories()->in($inFilesDirPath)->depth(0)->notName("*.manifest");

        $finder->sort(function ($a, $b) { return strcmp($a->getFilename(), $b->getFilename()); });

        foreach($finder as $file) {
            $sliceFinder = new Finder();
            $sliceFinder->in($file->getPathname())->files();

            $sliceFinder->sort(function ($a, $b) { return strcmp($a->getFilename(), $b->getFilename()); });

            foreach($sliceFinder as $sliceFile) {
                $outputPath = sprintf('%s/%s',
                    $outFilesDirPath,
                    $file->getFilename()
                );

                $inOuts[] = [
                    'input' => $sliceFile->getPathname(),
                    'output' => $outputPath
                ];
            }
        }

        return $inOuts;
    }

    /**
     * @param string $inFilePath
     * @return int
     * @throws UserException
     */
    private function getFileColumnIndexFromManifest(string $inFilePath): int
    {
        try {
            // /data/in/files not supported
            $manifest = $this->manifestManager->getTableManifest($inFilePath);
        } catch(\Exception $e) {
            throw new UserException(sprintf('Cannot load manifest for file %s', $inFilePath));
        }

        if(!isset($manifest['columns'])) {
            throw new UserException(sprintf('Columns not defined in file\'s "%s" manifest', $inFilePath));
        }

        $columnIndex = null;

        for($i = 0; $i < count($manifest['columns']); $i++) {
            if($manifest['columns'][$i] == $this->columnName) {
                $columnIndex = $i;
            }
        }

        if($columnIndex === null) {
            throw new UserException(
                sprintf('Column "%s" not presented in file\'s "%s" manifest\'s columns', $this->columnName, $inFilePath)
            );
        }

        return $columnIndex;
    }

    /**
     * @param string $inPath
     * @param string $outPath
     * @return $this
     */
    private function copyManifest(string $inPath, string $outPath): self
    {
        $inManifestPath = ManifestManager::getManifestFilename($inPath);
        $outManifestPath = ManifestManager::getManifestFilename($outPath);

        if(file_exists($inManifestPath)) {
            copy($inManifestPath, $outManifestPath);
        }

        return $this;
    }

    /**
     * @param string $inFilePath
     * @return int
     * @throws UserException
     */
    private function getFileColumnIndex(string $inFilePath): int
    {
        if($this->columnName !== null) {
            $columnIndex = $this->getFileColumnIndexFromManifest($inFilePath);
        } else if($this->columnIndex !== null) {
            $columnIndex = $this->columnIndex;
        } else {
            $columnIndex = 0;
        }

        return $columnIndex;
    }

    /**
     * @param string $inFilePath
     * @param string $outPath
     * @param bool $skipHeader
     * @return Processor
     * @throws Exception\SplitterException
     * @throws UserException
     */
    public function processFile(string $inFilePath, string $outPath, bool $skipHeader=false): self
    {
        $fileColumnIndex = $this->getFileColumnIndex($inFilePath);

        $splitter = new Splitter($inFilePath, $fileColumnIndex, $skipHeader, $this->splitterFiles);

        if(!file_exists($outPath)) {
            mkdir($outPath, 0777, true);
        }

        $splitter->split($outPath);

        $this->copyManifest($inFilePath, $outPath);

        return $this;
    }

    /**
     * @param string $inFilesDirPath
     * @param string $outFilesDirPath
     * @return Processor
     * @throws UserException
     * @throws Exception\SplitterException
     */
    public function processDir(string $inFilesDirPath, string $outFilesDirPath): self
    {
        $inOuts = $this->getFilesToProcess($inFilesDirPath, $outFilesDirPath);

        foreach($inOuts as $inOut) {
            $this->processFile($inOut['input'], $inOut['output'], true);
        }

        $inOuts = $this->getSlicedFilesToProcess($inFilesDirPath, $outFilesDirPath);

        foreach($inOuts as $inOut) {
            $this->processFile($inOut['input'], $inOut['output'], false);
        }

        return $this;
    }

}
