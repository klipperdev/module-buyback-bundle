<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Module\BuybackBundle\DependencyInjection;

use Klipper\Bundle\SecurityBundle\DependencyInjection\NodeUtils;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your config files.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('klipper_buyback');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
            ->append($this->getAuditBatchNode())
            ->append($this->getAuditItemNode())
            ->append($this->getBuybackOfferNode())
            ->end()
        ;

        return $treeBuilder;
    }

    private function getAuditBatchNode(): NodeDefinition
    {
        return NodeUtils::createArrayNode('audit_batch')
            ->addDefaultsIfNotSet()
            ->children()
            ->arrayNode('closed_statuses')
            ->scalarPrototype()->end()
            ->end()
            ->arrayNode('validated_statuses')
            ->scalarPrototype()->end()
            ->end()
            ->end()
        ;
    }

    private function getAuditItemNode(): NodeDefinition
    {
        return NodeUtils::createArrayNode('audit_item')
            ->addDefaultsIfNotSet()
            ->children()
            ->arrayNode('closed_statuses')
            ->scalarPrototype()->end()
            ->end()
            ->end()
        ;
    }

    private function getBuybackOfferNode(): NodeDefinition
    {
        return NodeUtils::createArrayNode('buyback_offer')
            ->addDefaultsIfNotSet()
            ->children()
            ->arrayNode('closed_statuses')
            ->scalarPrototype()->end()
            ->end()
            ->arrayNode('validated_statuses')
            ->scalarPrototype()->end()
            ->end()
            ->end()
        ;
    }
}
