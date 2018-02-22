<?php
declare(strict_types=1);

namespace Zstate\Crawler\Config;


use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class ConfigDefinition implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('crawler');

        $node->children()
            ->scalarNode('start_uri')
                ->isRequired()
                ->cannotBeEmpty()
            ->end()
            ->integerNode('concurrency')
                ->defaultValue(10)
            ->end()
            ->scalarNode('save_progress_in')
                ->defaultValue('memory')
            ->end()
            ->append($this->loginOptions())
            ->append($this->filterOptions())
            ->append($this->requestOptions())
        ->end();

        return $treeBuilder;
    }

    private function loginOptions(): NodeDefinition
    {
        $builder = new TreeBuilder();
        $node = $builder->root('login');

        $node->children()
            ->scalarNode('login_uri')
                ->isRequired()
                ->cannotBeEmpty()
            ->end()
            ->arrayNode('form_params')
                ->isRequired()
                ->cannotBeEmpty()
                ->scalarPrototype()->end()
            ->end()
            ->booleanNode('relogin')
                ->defaultFalse()
            ->end()
        ->end();

        return $node;
    }

    private function filterOptions(): NodeDefinition
    {
        $builder = new TreeBuilder();
        $node = $builder->root('filter');

        $node->children()
            ->arrayNode('allow')
                ->scalarPrototype()->end()
            ->end()
            ->arrayNode('allow_domains')
                ->scalarPrototype()->end()
            ->end()
            ->arrayNode('deny')
                ->scalarPrototype()->end()
            ->end()
        ->end();

        return $node;
    }

    private function requestOptions(): NodeDefinition
    {
        $builder = new TreeBuilder();
        $node = $builder->root('request_options');

        $node
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('verify')
                    ->defaultTrue()
                ->end()
                ->booleanNode('cookies')
                    ->defaultTrue()
                ->end()
                ->booleanNode('allow_redirects')
                    ->defaultFalse()
                ->end()
                ->booleanNode('debug')
                    ->defaultFalse()
                ->end()
            ->end()
        ->end();

        return $node;
    }
}