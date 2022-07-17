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
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Klipper\Component\DoctrineExtra\Util\ClassUtils;
use Klipper\Module\BuybackBundle\Model\Traits\AuditRepairableInterface;
use Klipper\Module\BuybackBundle\Model\Traits\BuybackModuleableInterface;
use Klipper\Module\BuybackBundle\Model\Traits\RepairAuditableInterface;
use Klipper\Module\RepairBundle\Model\RepairInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class RepairSubscriber implements EventSubscriber
{
    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
            Events::onFlush,
        ];
    }

    public function prePersist(LifecycleEventArgs $event): void
    {
        $this->preUpdate($event);
    }

    public function preUpdate(LifecycleEventArgs $event): void
    {
        $object = $event->getObject();

        if ($object instanceof RepairInterface && $object instanceof RepairAuditableInterface) {
            $account = $object->getAccount();

            if (null === $object->getPriceList()
                && null !== $object->getAuditItem()
                && null !== $account && $account instanceof BuybackModuleableInterface
            ) {
                $module = $account->getBuybackModule();

                if (null !== $module && null !== $module->getRepairPriceList()) {
                    $object->setPriceList($module->getRepairPriceList());
                }
            }
        }
    }

    public function onFlush(OnFlushEventArgs $event): void
    {
        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityDeletions() as $object) {
            if ($object instanceof RepairInterface
                && $object instanceof RepairAuditableInterface
                && null !== ($audit = $object->getAuditItem())
            ) {
                if ($audit instanceof AuditRepairableInterface) {
                    $audit->setRepairPrice(0.0);

                    $meta = $em->getClassMetadata(ClassUtils::getClass($audit));
                    $uow->computeChangeSet($meta, $audit);
                }
            }
        }
    }
}
