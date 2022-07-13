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

use Klipper\Bundle\ApiBundle\Util\ControllerDefinitionUtil;
use Klipper\Module\BuybackBundle\Controller\ApiAuditBatchController;
use Klipper\Module\BuybackBundle\Controller\ApiAuditItemController;
use Klipper\Module\BuybackBundle\Controller\ApiBuybackOfferController;
use Klipper\Module\BuybackBundle\Doctrine\Listener\AuditBatchSubscriber;
use Klipper\Module\BuybackBundle\Doctrine\Listener\AuditItemSubscriber;
use Klipper\Module\BuybackBundle\Doctrine\Listener\BuybackOfferSubscriber;
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

        $this->configAuditBatch($container, $loader, $config['audit_batch']);
        $this->configAuditItem($container, $loader, $config['audit_item']);
        $this->configBuybackOffer($container, $loader, $config['buyback_offer']);
        $this->configRepair($loader);

        $loader->load('manager.xml');
        $loader->load('api_form.xml');

        ControllerDefinitionUtil::set($container, ApiAuditBatchController::class);
        ControllerDefinitionUtil::set($container, ApiAuditItemController::class);
        ControllerDefinitionUtil::set($container, ApiBuybackOfferController::class);
    }

    /**
     * @throws
     */
    protected function configAuditBatch(ContainerBuilder $container, LoaderInterface $loader, array $config): void
    {
        $loader->load('doctrine_subscriber_audit_batch.xml');

        $def = $container->getDefinition(AuditBatchSubscriber::class);

        $def->replaceArgument(3, array_unique(array_merge($config['closed_statuses'], [
            'closed',
            'canceled',
        ])));
        $def->replaceArgument(4, array_unique(array_merge($config['validated_statuses'], [
            'accepted',
        ])));
    }

    /**
     * @throws
     */
    protected function configAuditItem(ContainerBuilder $container, LoaderInterface $loader, array $config): void
    {
        $loader->load('doctrine_subscriber_audit_item.xml');
        $loader->load('import_adapter_audit_item.xml');

        $def = $container->getDefinition(AuditItemSubscriber::class);

        $def->replaceArgument(3, array_unique(array_merge($config['closed_statuses'], [
            'valorised',
        ])));
    }

    /**
     * @throws
     */
    protected function configBuybackOffer(ContainerBuilder $container, LoaderInterface $loader, array $config): void
    {
        $loader->load('doctrine_subscriber_buyback_offer.xml');

        $def = $container->getDefinition(BuybackOfferSubscriber::class);

        $def->replaceArgument(3, array_unique(array_merge($config['closed_statuses'], [
            'accepted',
            'refused',
            'canceled',
        ])));
        $def->replaceArgument(4, array_unique(array_merge($config['validated_statuses'], [
            'accepted',
        ])));
    }

    /**
     * @throws
     */
    protected function configRepair(LoaderInterface $loader): void
    {
        $loader->load('doctrine_subscriber_repair.xml');
    }
}
