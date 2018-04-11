<?php

namespace Keboola\ColumnToFilesProcessor;

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
     * @return Processor
     */
    public function initProcessor(): Processor
    {
        return new Processor();
    }

    /**
     *
     */
    public function run() : void
    {
        $processor = $this->initProcessor();
    }

}
