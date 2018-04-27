<?php

namespace Keboola\SplitByValueProcessor;

use Keboola\Component\BaseComponent;

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
     * @param string $columnIndex
     * @return Processor
     */
    public function initProcessor(string $columnIndex): Processor
    {
        return new Processor($columnIndex);
    }

    /**
     *
     */
    public function run() : void
    {
        $processor = $this->initProcessor($this->getConfig()->getValue(['parameters', 'column_index']));
        $processor->processDir(
            sprintf('%s%s', $this->getDataDir(), '/in/tables'),
            sprintf('%s%s', $this->getDataDir(), '/out/tables')
        );
    }

}
