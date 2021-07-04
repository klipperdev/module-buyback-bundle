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

use Klipper\Module\BuybackBundle\Doctrine\Listener\AuditRequestSubscriber;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class KlipperBuybackExtension extends Extension
{
    /**
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $this->configAuditRequest($container, $loader, $config['audit_request']);
    }

    /**
     * @throws
     */
    protected function configAuditRequest(ContainerBuilder $container, LoaderInterface $loader, array $config): void
    {
        $loader->load('doctrine_subscriber.xml');

        $def = $container->getDefinition(AuditRequestSubscriber::class);

        $def->replaceArgument(3, array_unique(array_merge($config['closed_statuses'], [
            'accepted',
            'refused',
            'canceled',
        ])));
        $def->replaceArgument(4, array_unique(array_merge($config['validated_statuses'], [
            'accepted',
        ])));
    }
}
