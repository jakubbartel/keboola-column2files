<?php

namespace Keboola\SplitByValueProcessor;

use Keboola\Component\BaseComponent;
use Keboola\SplitByValueProcessor\Exception\UserException;

class Component extends BaseComponent
{

    /**
     * @return string
     */
    protected function getConfigDefinitionClass(): string
    {
        return ConfigDefinition::class;
    }

    /**
     * @return Processor
     * @throws UserException
     */
    public function initProcessor(): Processor
    {
        $columnName = $this->getConfig()->getValue(['parameters', 'column_name'], false);
        $columnIndex = $this->getConfig()->getValue(['parameters', 'column_index'], false);

        return new Processor(
            $columnName === false ? null : $columnName,
            $columnIndex === false ? null : $columnIndex,
            $this->getManifestManager()
        );
    }

    /**
     * @throws Exception\UserException
     */
    public function run() : void
    {
        $processor = $this->initProcessor();

        $processor->processDir(
            sprintf('%s%s', $this->getDataDir(), '/in/tables'),
            sprintf('%s%s', $this->getDataDir(), '/out/tables')
        );
    }

}
