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
            ->arrayNode('start_uri')
                ->info('A list of URIs to crawl.')
                ->scalarPrototype()->end()
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

        $node
            ->addDefaultsIfNotSet()
            ->children()
            ->arrayNode('allow')
                ->info('A list of regular expressions that the urls must match in order to be extracted. If not given (or empty), it will match all links. It has precedence over the deny parameter.')
                ->scalarPrototype()->end()
                ->defaultValue([])
            ->end()
            ->arrayNode('allow_domains')
                ->info('A list of string containing domains which will be considered for extracting the links. It has precedence over the deny parameter.')
                ->scalarPrototype()->end()
                ->defaultValue([])
            ->end()
            ->arrayNode('deny_domains')
                ->info('A list of strings containing domains which won’t be considered for extracting the links.')
                ->scalarPrototype()->end()
                ->defaultValue([])
            ->end()
            ->arrayNode('deny')
                ->info('A list of regular expressions) that the urls must match in order to be excluded (ie. not extracted).')
                ->scalarPrototype()->end()
                ->defaultValue([])
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
                    ->info('Describes the SSL certificate verification behavior of a request.')
                    ->defaultTrue()
                ->end()
                ->booleanNode('cookies')
                    ->info('Specifies whether or not cookies are used in a request or what cookie jar to use or what cookies to send.')
                    ->defaultTrue()
                ->end()
                ->booleanNode('allow_redirects')
                    ->info('Describes the redirect behavior of a request.')
                    ->defaultFalse()
                ->end()
                ->booleanNode('debug')
                    ->info('Set to true or to enable debug output with the handler used to send a request.')
                    ->defaultFalse()
                ->end()
                ->floatNode('connect_timeout')
                    ->info('Float describing the number of seconds to wait while trying to connect to a server. Use 0 to wait indefinitely (the default behavior).')
                    ->defaultValue(0)
                ->end()
                ->floatNode('timeout')
                    ->info('Float describing the timeout of the request in seconds. Use 0 to wait indefinitely (the default behavior).')
                    ->defaultValue(0)
                ->end()
                ->floatNode('delay')
                    ->info('The number of milliseconds to delay before sending the request.')
                    ->defaultNull()
                ->end()
            ->end()
        ->end();

        return $node;
    }
}