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
use Klipper\Component\DoctrineChoice\ChoiceManagerInterface;
use Klipper\Component\DoctrineExtensionsExtra\Util\ListenerUtil;
use Klipper\Component\DoctrineExtra\Util\ClassUtils;
use Klipper\Component\Resource\Object\ObjectFactoryInterface;
use Klipper\Module\BuybackBundle\Model\AuditItemInterface;
use Klipper\Module\BuybackBundle\Model\AuditRequestInterface;
use Klipper\Module\BuybackBundle\Model\Traits\BuybackModuleableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class AuditRequestSubscriber implements EventSubscriber
{
    private ChoiceManagerInterface $choiceManager;

    private CodeGenerator $generator;

    private TranslatorInterface $translator;

    private ObjectFactoryInterface $objectFactory;

    private array $closedStatues;

    private array $validatedStatues;

    public function __construct(
        ChoiceManagerInterface $choiceManager,
        CodeGenerator $generator,
        TranslatorInterface $translator,
        ObjectFactoryInterface $objectFactory,
        array $closedStatues = [],
        array $validatedStatues = []
    ) {
        $this->choiceManager = $choiceManager;
        $this->generator = $generator;
        $this->translator = $translator;
        $this->objectFactory = $objectFactory;
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
                        $status = $module->getDefaultAuditRequestStatus() ?? $this->choiceManager->getChoice('audit_request_status', null);
                        $object->setStatus($status);
                    }
                }

                if (null === $object->getShippingAddress()) {
                    ListenerUtil::thrownError($this->translator->trans(
                        'klipper_buyback.audit_request.shipping_address_required',
                        [],
                        'validators'
                    ), $object, 'shippingAddress');
                }
            }

            if (null === $object->getReceiptedAt()
                && null !== $object->getStatus()
                && 'waiting_counting' === $object->getStatus()->getValue()
            ) {
                $object->setReceiptedAt(new \DateTime());
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
            $this->validateClosedEmptyItems($object);
            $this->convertToAuditItems($em, $object, true);
        }

        foreach ($uow->getScheduledEntityUpdates() as $object) {
            $this->updateClosed($em, $object);
            $this->validateClosedEmptyItems($object);
            $this->convertToAuditItems($em, $object);
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

                if (null === $object->getReceiptedAt() && $object->isClosed()) {
                    $object->setReceiptedAt(new \DateTime());
                }

                $classMetadata = $em->getClassMetadata(ClassUtils::getClass($object));
                $uow->recomputeSingleEntityChangeSet($classMetadata, $object);
            }
        }
    }

    private function validateClosedEmptyItems(object $object): void
    {
        if ($object instanceof AuditRequestInterface) {
            if ($object->isClosed() && $object->isValidated() && 0 === $object->getNumberOfItems()) {
                ListenerUtil::thrownError($this->translator->trans(
                    'klipper_buyback.audit_request.cannot_be_validated',
                    [],
                    'validators'
                ));
            }
        }
    }

    private function convertToAuditItems(EntityManagerInterface $em, object $object, bool $create = false): void
    {
        if ($object instanceof AuditRequestInterface) {
            $uow = $em->getUnitOfWork();
            $changeSet = $uow->getEntityChangeSet($object);

            if ($create || isset($changeSet['status'])) {
                $validated = null !== $object->getStatus() && 'validated' === $object->getStatus()->getValue();

                if ($validated && !$object->isConverted()) {
                    $object->setConverted(true);

                    $classMetadata = $em->getClassMetadata(ClassUtils::getClass($object));
                    $uow = $em->getUnitOfWork();
                    $uow->recomputeSingleEntityChangeSet($classMetadata, $object);

                    $this->createAuditItems($em, $object);
                }
            }
        }
    }

    private function createAuditItems(EntityManagerInterface $em, AuditRequestInterface $object): void
    {
        $uow = $em->getUnitOfWork();

        foreach ($object->getItems() as $item) {
            for ($i = 0; $i < (int) $item->getReceivedQuantity(); ++$i) {
                /** @var AuditItemInterface $auditItem */
                $auditItem = $this->objectFactory->create(AuditItemInterface::class);
                $auditItem->setAuditRequest($object);
                $auditItem->setProduct($item->getProduct());
                $auditItem->setProductCombination($item->getProductCombination());
                $auditItem->setReceiptedAt($object->getReceiptedAt() ?? new \DateTime());

                $em->persist($auditItem);
                $auditItemMeta = $em->getClassMetadata(ClassUtils::getClass($auditItem));
                $uow->computeChangeSet($auditItemMeta, $auditItem);
            }
        }
    }
}
