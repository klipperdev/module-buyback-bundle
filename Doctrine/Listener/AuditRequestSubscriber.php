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
use Klipper\Component\CodeGenerator\CodeGenerator;
use Klipper\Component\DoctrineChoice\Listener\Traits\DoctrineListenerChoiceTrait;
use Klipper\Component\DoctrineExtensionsExtra\Util\ListenerUtil;
use Klipper\Component\DoctrineExtra\Util\ClassUtils;
use Klipper\Module\BuybackBundle\Model\AuditRequestInterface;
use Klipper\Module\BuybackBundle\Model\Traits\BuybackModuleableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class AuditRequestSubscriber implements EventSubscriber
{
    use DoctrineListenerChoiceTrait;

    private CodeGenerator $generator;

    private TranslatorInterface $translator;

    private array $closedStatues;

    private array $validatedStatues;

    public function __construct(
        CodeGenerator $generator,
        TranslatorInterface $translator,
        array $closedStatues = [],
        array $validatedStatues = []
    ) {
        $this->generator = $generator;
        $this->translator = $translator;
        $this->closedStatues = $closedStatues;
        $this->validatedStatues = $validatedStatues;
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
        $em = $event->getEntityManager();
        $object = $event->getObject();

        if ($object instanceof AuditRequestInterface) {
            if (null === $object->getReference()) {
                $object->setReference($this->generator->generate());
            }

            $account = $object->getAccount();
            $shippingAddress = $object->getShippingAddress();
            $workcenter = $object->getWorkcenter();
            $supplier = $object->getSupplier();
            $invoiceAddress = $object->getInvoiceAddress();
            $identifierType = $object->getIdentifierType();
            $status = $object->getStatus();

            if (null === $object->getDate()) {
                $object->setDate(new \DateTime());
            }

            if (null === $shippingAddress
                || null === $workcenter
                || null === $supplier
                || null === $invoiceAddress
                || null === $identifierType
                || null === $status
            ) {
                if (null !== $account && $account instanceof BuybackModuleableInterface && null !== ($module = $account->getBuybackModule())) {
                    $module->getWorkcenter();

                    if (null === $shippingAddress) {
                        $object->setShippingAddress($module->getShippingAddress());
                    }

                    if (null === $workcenter) {
                        $object->setWorkcenter($module->getWorkcenter());
                    }

                    if (null === $supplier) {
                        $object->setSupplier($module->getSupplier());
                    }

                    if (null === $invoiceAddress) {
                        $object->setInvoiceAddress($module->getInvoiceAddress());
                    }

                    if (null === $identifierType) {
                        $object->setIdentifierType($module->getIdentifierType());
                    }

                    if (null === $status) {
                        $status = $module->getDefaultAuditRequestStatus() ?? $this->getChoice($em, 'audit_request_status', null);
                        $object->setStatus($status);
                    }
                }
            }
        }
    }

    public function onFlush(OnFlushEventArgs $event): void
    {
        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $object) {
            $this->validateModuleEnabled($object);
            $this->updateClosed($em, $object, true);
        }

        foreach ($uow->getScheduledEntityUpdates() as $object) {
            $this->updateClosed($em, $object, true);
        }
    }

    private function validateModuleEnabled(object $object): void
    {
        if ($object instanceof AuditRequestInterface) {
            $account = $object->getAccount();
            $module = null !== $account && $account instanceof BuybackModuleableInterface
                ? $account->getBuybackModule()
                : null;

            if (null === $module || !$module->isEnabled()) {
                ListenerUtil::thrownError($this->translator->trans(
                    'klipper_buyback.audit_request.module_must_be_enabled',
                    [],
                    'validators'
                ));
            }
        }
    }

    private function updateClosed(EntityManagerInterface $em, object $object, bool $create = false): void
    {
        if ($object instanceof AuditRequestInterface) {
            $uow = $em->getUnitOfWork();
            $changeSet = $uow->getEntityChangeSet($object);

            if ($create || isset($changeSet['status'])) {
                $closed = null === $object->getStatus() || \in_array($object->getStatus()->getValue(), $this->closedStatues, true);
                $invalidated = null === $object->getStatus() || !\in_array($object->getStatus()->getValue(), $this->validatedStatues, true);
                $object->setClosed($closed);
                $object->setValidated(!$invalidated);

                $classMetadata = $em->getClassMetadata(ClassUtils::getClass($object));
                $uow->recomputeSingleEntityChangeSet($classMetadata, $object);
            }
        }
    }
}
