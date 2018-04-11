<?php

namespace Keboola\ColumnToFilesProcessor;

use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;

class ConfigDefinition extends BaseConfigDefinition
{

    /**
     * @return ArrayNodeDefinition|NodeDefinition
     */
    protected function getParametersDefinition()
    {
        $parametersNode = parent::getParametersDefinition();

        $parametersNode
            ->isRequired()
            ->children()
                ->scalarNode('column')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
            ->end();

        return $parametersNode;
    }

}
