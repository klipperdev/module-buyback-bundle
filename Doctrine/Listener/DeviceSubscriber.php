<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Module\BuybackBundle\Doctrine\Listener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Klipper\Component\DoctrineExtra\Util\ClassUtils;
use Klipper\Module\BuybackBundle\Model\AuditItemInterface;
use Klipper\Module\BuybackBundle\Model\Traits\DeviceAuditableInterface;
use Klipper\Module\DeviceBundle\Model\DeviceInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class DeviceSubscriber implements EventSubscriber
{
    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function prePersist(LifecycleEventArgs $event): void
    {
        $this->preUpdate($event);
    }

    public function preUpdate(LifecycleEventArgs $event): void
    {
        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();
        $object = $event->getObject();

        if ($object instanceof DeviceInterface && $object instanceof DeviceAuditableInterface) {
            $changeSet = $uow->getEntityChangeSet($object);
            $lastAudit = null;

            if (isset($changeSet['product'])
                && null !== $object->getProduct()
                && null !== $object->getLastAuditItem()
                && $object->getProduct() !== $object->getLastAuditItem()->getProduct()
            ) {
                $lastAudit = $object->getLastAuditItem();
                $lastAudit->setProduct($object->getProduct());
            }

            if (isset($changeSet['productCombination'])
                && null !== $object->getProductCombination()
                && null !== $object->getLastAuditItem()
                && $object->getProductCombination() !== $object->getLastAuditItem()->getProductCombination()
            ) {
                $lastAudit = $object->getLastAuditItem();
                $lastAudit->setProductCombination($object->getProductCombination());
            }

            if (null !== $lastAudit) {
                $auditMeta = $em->getClassMetadata(ClassUtils::getClass($lastAudit));
                $uow->recomputeSingleEntityChangeSet($auditMeta, $lastAudit);
            }
        } elseif ($object instanceof AuditItemInterface) {
            $changeSet = $uow->getEntityChangeSet($object);
            $device = null;

            if (isset($changeSet['product'])
                && null !== $object->getProduct()
                && null !== $object->getDevice()
                && $object->getProduct() !== $object->getDevice()->getProduct()
            ) {
                $device = $object->getDevice();
                $device->setProduct($object->getProduct());
            }

            if (isset($changeSet['productCombination'])
                && null !== $object->getProductCombination()
                && null !== $object->getDevice()
                && $object->getProductCombination() !== $object->getDevice()->getProductCombination()
            ) {
                $device = $object->getDevice();
                $device->setProductCombination($object->getProductCombination());
            }

            if (null !== $device) {
                $repairMeta = $em->getClassMetadata(ClassUtils::getClass($device));
                $uow->recomputeSingleEntityChangeSet($repairMeta, $device);
            }
        }
    }
}
