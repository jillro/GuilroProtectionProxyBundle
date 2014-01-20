<?php
/*
 * This file is part of GuilroProtectionProxyBundle.
 *
 * Copyright (C) 2013 Guillaume Royer
 *
 * GuilroProtectionProxyBundle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * GuilroProtectionProxyBundle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with GuilroProtectionProxyBundle.  If not, see <http://www.gnu.org/licenses/>.
 */


namespace Guilro\ProtectionProxyBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('guilro_protection_proxy');

        $rootNode
            ->children()
                ->arrayNode('protected_classes')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                    ->children()
                        ->scalarNode('view')
                        ->end()
                        ->arrayNode('methods')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                            ->children()
                                ->scalarNode('attribute')->end()
                                ->booleanNode('return_proxy')->end()
                                ->scalarNode('deny_value')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
