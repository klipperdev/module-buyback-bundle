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
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Klipper\Component\DoctrineChoice\ChoiceManagerInterface;
use Klipper\Component\DoctrineExtensionsExtra\Util\ListenerUtil;
use Klipper\Component\DoctrineExtra\Util\ClassUtils;
use Klipper\Component\Security\Model\UserInterface;
use Klipper\Module\BuybackBundle\Model\AuditItemInterface;
use Klipper\Module\BuybackBundle\Model\Traits\DeviceAuditableInterface;
use Klipper\Module\DeviceBundle\Model\DeviceInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class AuditItemSubscriber implements EventSubscriber
{
    private ChoiceManagerInterface $choiceManager;

    private TranslatorInterface $translator;

    private TokenStorageInterface $tokenStorage;

    private array $closedStatues;

    public function __construct(
        ChoiceManagerInterface $choiceManager,
        TranslatorInterface $translator,
        TokenStorageInterface $tokenStorage,
        array $closedStatues = []
    ) {
        $this->choiceManager = $choiceManager;
        $this->translator = $translator;
        $this->tokenStorage = $tokenStorage;
        $this->closedStatues = $closedStatues;
    }

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

        if ($object instanceof AuditItemInterface) {
            if (null === $object->getConditionPrice()) {
                $object->setConditionPrice(0.0);
            }

            if (null === $object->getStatePrice()) {
                $object->setStatePrice(0.0);
            }

            if (null === $object->getReceiptedAt()) {
                if (null !== $object->getAuditRequest() && null !== $object->getAuditRequest()->getReceiptedAt()) {
                    $object->setReceiptedAt($object->getAuditRequest()->getReceiptedAt());
                } else {
                    $object->setReceiptedAt(new \DateTime());
                }
            }
        }
    }

    public function onFlush(OnFlushEventArgs $event): void
    {
        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $object) {
            $this->updateLastAuditOnDevice($em, $object, true);
            $this->updateStatus($em, $object);
            $this->updateClosed($em, $object, true);
            $this->updateValidated($em, $object, true);
            $this->updateAuditDates($em, $object);
            $this->updateAuditor($em, $object);
            $this->updateDeviceStatus($em, $object, true);
        }

        foreach ($uow->getScheduledEntityUpdates() as $object) {
            $this->updateLastAuditOnDevice($em, $object);
            $this->updateStatus($em, $object);
            $this->updateClosed($em, $object);
            $this->updateValidated($em, $object);
            $this->updateAuditDates($em, $object);
            $this->updateAuditor($em, $object);
            $this->updateDeviceStatus($em, $object);
        }
    }

    private function updateLastAuditOnDevice(EntityManagerInterface $em, object $object, bool $create = false): void
    {
        if ($object instanceof AuditItemInterface && null !== $device = $object->getDevice()) {
            if ($device instanceof DeviceAuditableInterface) {
                $uow = $em->getUnitOfWork();

                if ($create && null !== $device->getLastAuditItem() && !$device->getLastAuditItem()->isClosed()) {
                    ListenerUtil::thrownError($this->translator->trans(
                        'klipper_buyback.audit_item.previous_audit_already_open',
                        [],
                        'validators'
                    ));
                }

                if (null === $object->getPreviousAuditItem()
                    && null !== $device->getLastAuditItem()
                    && $object !== $device->getLastAuditItem()
                ) {
                    $object->setPreviousAuditItem($device->getLastAuditItem());

                    $classMetadata = $em->getClassMetadata(ClassUtils::getClass($object));
                    $uow->recomputeSingleEntityChangeSet($classMetadata, $object);
                }

                if ($create || null === $device->getLastAuditItem()) {
                    $device->setLastAuditItem($object);

                    $classMetadata = $em->getClassMetadata(ClassUtils::getClass($device));
                    $uow->recomputeSingleEntityChangeSet($classMetadata, $device);
                }
            }
        }
    }

    private function updateStatus(EntityManagerInterface $em, object $object): void
    {
        if ($object instanceof AuditItemInterface) {
            $uow = $em->getUnitOfWork();
            $currentStatus = $object->getStatus();
            $currentStatusValue = null !== $currentStatus ? $currentStatus->getValue() : null;
            $newStatusValue = $this->findStatusValue($object);

            if ($newStatusValue !== $currentStatusValue) {
                $object->setStatus($this->choiceManager->getChoice('audit_item_status', $newStatusValue));

                $classMetadata = $em->getClassMetadata(ClassUtils::getClass($object));
                $uow->recomputeSingleEntityChangeSet($classMetadata, $object);
            }
        }
    }

    private function findStatusValue(AuditItemInterface $object): ?string
    {
        $newStatusValue = 'confirmed';

        if (null !== $object->getAuditRequest()
            && null !== $object->getAuditRequest()->getSupplierOrderNumber()
            && null !== $object->getProduct()
        ) {
            $newStatusValue = 'qualified';

            if (null !== $object->getDevice()
                && null !== $object->getAuditCondition()
            ) {
                $newStatusValue = 'audited';

                if (null !== $object->getBuybackOffer()) {
                    $newStatusValue = 'valorised';
                }
            }
        }

        return $newStatusValue;
    }

    private function updateClosed(EntityManagerInterface $em, object $object, bool $create = false): void
    {
        if ($object instanceof AuditItemInterface) {
            $uow = $em->getUnitOfWork();
            $changeSet = $uow->getEntityChangeSet($object);

            if ($create || isset($changeSet['status'])) {
                $closed = null === $object->getStatus() || \in_array($object->getStatus()->getValue(), $this->closedStatues, true);
                $object->setClosed($closed);

                $classMetadata = $em->getClassMetadata(ClassUtils::getClass($object));
                $uow->recomputeSingleEntityChangeSet($classMetadata, $object);
            }
        }
    }

    private function updateValidated(EntityManagerInterface $em, object $object, bool $create = false): void
    {
        if ($object instanceof AuditItemInterface) {
            $uow = $em->getUnitOfWork();
            $changeSet = $uow->getEntityChangeSet($object);

            if ($create || isset($changeSet['status'])) {
                $validated = null !== $object->getStatus() && \in_array($object->getStatus()->getValue(), [
                    'audited',
                    'validated',
                ], true);

                $object->setValidated($validated);

                $classMetadata = $em->getClassMetadata(ClassUtils::getClass($object));
                $uow->recomputeSingleEntityChangeSet($classMetadata, $object);
            }
        }
    }

    private function updateAuditDates(EntityManagerInterface $em, object $object): void
    {
        if ($object instanceof AuditItemInterface) {
            $uow = $em->getUnitOfWork();
            $auditStatus = null !== $object->getStatus() ? $object->getStatus()->getValue() : '';
            $edited = false;

            if ('qualified' === $auditStatus) {
                if (null === $object->getQualifiedAt()) {
                    $object->setQualifiedAt(new \DateTime());
                    $edited = true;
                }
            } elseif ('audited' === $auditStatus) {
                if (null === $object->getQualifiedAt()) {
                    $object->setQualifiedAt(new \DateTime());
                    $edited = true;
                }

                if (null === $object->getAuditedAt()) {
                    $object->setAuditedAt(new \DateTime());
                    $edited = true;
                }
            } elseif ('valorised' === $auditStatus || $object->isClosed()) {
                if (null === $object->getQualifiedAt()) {
                    $object->setQualifiedAt(new \DateTime());
                    $edited = true;
                }

                if (null === $object->getAuditedAt()) {
                    $object->setAuditedAt(new \DateTime());
                    $edited = true;
                }

                if (null === $object->getValorisedAt()) {
                    $object->setValorisedAt(new \DateTime());
                    $edited = true;
                }
            }

            if ($edited) {
                $classMetadata = $em->getClassMetadata(ClassUtils::getClass($object));
                $uow->recomputeSingleEntityChangeSet($classMetadata, $object);
            }
        }
    }

    private function updateAuditor(EntityManagerInterface $em, object $object): void
    {
        if ($object instanceof AuditItemInterface) {
            $uow = $em->getUnitOfWork();
            $auditStatus = null !== $object->getStatus() ? $object->getStatus()->getValue() : '';

            if (null === $object->getAuditor()
                && (\in_array($auditStatus, ['audited', 'valorised'], true) || $object->isClosed())
            ) {
                $token = $this->tokenStorage->getToken();
                $user = null !== $token ? $token->getUser() : null;

                if ($user instanceof UserInterface) {
                    $object->setAuditor($user);

                    $classMetadata = $em->getClassMetadata(ClassUtils::getClass($object));
                    $uow->recomputeSingleEntityChangeSet($classMetadata, $object);
                }
            }
        }
    }

    private function updateDeviceStatus(EntityManagerInterface $em, object $object, bool $create = false): void
    {
        if (!$object instanceof AuditItemInterface || null === $object->getDevice()) {
            return;
        }

        $uow = $em->getUnitOfWork();
        $changeSet = $uow->getEntityChangeSet($object);
        $device = $object->getDevice();

        if ($create || isset($changeSet['device'])) {
            if (isset($changeSet['device'][0])) {
                /** @var DeviceInterface $oldDevice */
                $oldDevice = $changeSet['device'][0];
                $statusOperational = $this->choiceManager->getChoice('device_status', 'in_use');

                if (null !== $statusOperational) {
                    $oldDevice->setStatus($statusOperational);

                    $classMetadata = $em->getClassMetadata(ClassUtils::getClass($oldDevice));
                    $uow->recomputeSingleEntityChangeSet($classMetadata, $oldDevice);
                }
            }
        }

        if (null === $device->getTerminatedAt()) {
            $auditStatus = null !== $object->getStatus() ? $object->getStatus()->getValue() : '';

            switch ($auditStatus) {
                case 'valorised':
                    $newDeviceStatusValue = 'in_buyback_offer';

                    break;

                case 'confirmed':
                case 'qualified':
                case 'audited':
                default:
                    $newDeviceStatusValue = 'in_audit';

                    break;
            }

            if (null === $device->getStatus() || $newDeviceStatusValue !== $device->getStatus()->getValue()) {
                $newDeviceStatus = $this->choiceManager->getChoice('device_status', $newDeviceStatusValue);

                if (null !== $newDeviceStatus) {
                    $device->setStatus($newDeviceStatus);

                    $classMetadata = $em->getClassMetadata(ClassUtils::getClass($device));
                    $uow->recomputeSingleEntityChangeSet($classMetadata, $device);
                }
            }
        }
    }
}
