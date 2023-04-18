<?php

namespace PhpCP;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class ConfigTreeBuilder implements ConfigurationInterface {

  /**
   * {@inheritdoc}
   */
  public function getConfigTreeBuilder() {
    $treeBuilder = new TreeBuilder('phpcp');

    $treeBuilder->getRootNode()->children()
      ->arrayNode('github')
        ->children()
        ->scalarNode('user')->isRequired()->end()
        ->scalarNode('token')->isRequired()->end()
        ->scalarNode('repo')->isRequired()->end()
        ->end()
      ->end()
      ->arrayNode('cpanel')
        ->children()
        ->scalarNode('token')->isRequired()->end()
        ->scalarNode('url')->isRequired()->end()
        ->end()
      ->end()
//      ->arrayNode('db')->canBeUnset()
//        ->children()
//        ->scalarNode('host')->end()
//        ->scalarNode('name')->end()
//        ->end()
//      ->end()
    ->end();

    return $treeBuilder;
  }

}
