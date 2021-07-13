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
use Klipper\Component\DoctrineExtensionsExtra\Util\ListenerUtil;
use Klipper\Module\BuybackBundle\Model\Traits\BuybackModuleableInterface;
use Klipper\Module\BuybackBundle\Model\Traits\RepairAuditableInterface;
use Klipper\Module\RepairBundle\Model\RepairInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class RepairSubscriber implements EventSubscriber
{
    private TranslatorInterface $translator;

    public function __construct(
        TranslatorInterface $translator
    ) {
        $this->translator = $translator;
    }

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

            if (null !== $object->getAuditItem() && null === $object->getDevice()) {
                ListenerUtil::thrownError($this->translator->trans(
                    'klipper_buyback.audit_item.repair.device_required',
                    [],
                    'validators'
                ), $object);
            }
        }
    }
}
