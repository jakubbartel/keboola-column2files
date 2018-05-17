<?php

namespace Keboola\SplitByValueProcessor;

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
                ->integerNode('column_index')
                ->end()
                ->scalarNode('column_name')
                ->end()
            ->end();

        return $parametersNode;
    }

}
