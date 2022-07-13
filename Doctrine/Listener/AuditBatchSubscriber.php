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
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Klipper\Component\CodeGenerator\CodeGenerator;
use Klipper\Component\DoctrineChoice\ChoiceManagerInterface;
use Klipper\Component\DoctrineExtensionsExtra\Util\ListenerUtil;
use Klipper\Component\DoctrineExtra\Util\ClassUtils;
use Klipper\Module\BuybackBundle\Model\AuditBatchInterface;
use Klipper\Module\BuybackBundle\Model\AuditItemInterface;
use Klipper\Module\BuybackBundle\Model\BuybackOfferInterface;
use Klipper\Module\BuybackBundle\Model\Traits\BuybackModuleableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class AuditBatchSubscriber implements EventSubscriber
{
    private ChoiceManagerInterface $choiceManager;

    private CodeGenerator $generator;

    private TranslatorInterface $translator;

    private array $closedStatues;

    private array $validatedStatues;

    /**
     * @var array<int|string, null|BuybackOfferInterface>
     */
    private array $updateBuybackOfferIds = [];

    public function __construct(
        ChoiceManagerInterface $choiceManager,
        CodeGenerator $generator,
        TranslatorInterface $translator,
        array $closedStatues = [],
        array $validatedStatues = []
    ) {
        $this->choiceManager = $choiceManager;
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
            Events::postFlush,
        ];
    }

    public function prePersist(LifecycleEventArgs $event): void
    {
        $this->preUpdate($event);
    }

    public function preUpdate(LifecycleEventArgs $event): void
    {
        $object = $event->getObject();

        if ($object instanceof AuditBatchInterface) {
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
                if ($account instanceof BuybackModuleableInterface && null !== ($module = $account->getBuybackModule())) {
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
                        $status = $module->getDefaultAuditBatchStatus() ?? $this->choiceManager->getChoice('audit_batch_status', null);
                        $object->setStatus($status);
                    }
                }

                if (null === $object->getShippingAddress()) {
                    ListenerUtil::thrownError($this->translator->trans(
                        'klipper_buyback.audit_batch.shipping_address_required',
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
        }

        foreach ($uow->getScheduledEntityUpdates() as $object) {
            $this->updateAuditItemBuybackOffer($em, $object);
            $this->updateClosed($em, $object);
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $em = $args->getEntityManager();

        if (\count($this->updateBuybackOfferIds) > 0) {
            foreach ($this->updateBuybackOfferIds as $auditBatchId => $buybackOffer) {
                // Do not the persist/flush in postFlush event
                $em->createQueryBuilder()
                    ->update(AuditItemInterface::class, 'ai')
                    ->set('ai.buybackOffer', ':buybackOffer')
                    ->where('ai.auditBatch = :auditBatchId')
                    ->setParameter('auditBatchId', $auditBatchId)
                    ->setParameter('buybackOffer', $buybackOffer)
                    ->getQuery()
                    ->execute()
                ;
            }
        }

        $this->updateBuybackOfferIds = [];
    }

    private function validateModuleEnabled(object $object): void
    {
        if ($object instanceof AuditBatchInterface) {
            $account = $object->getAccount();
            $module = $account instanceof BuybackModuleableInterface
                ? $account->getBuybackModule()
                : null;

            if (null === $module || !$module->isEnabled()) {
                ListenerUtil::thrownError($this->translator->trans(
                    'klipper_buyback.audit_batch.module_must_be_enabled',
                    [],
                    'validators'
                ));
            }
        }
    }

    private function updateAuditItemBuybackOffer(EntityManagerInterface $em, object $object): void
    {
        if ($object instanceof AuditBatchInterface) {
            $uow = $em->getUnitOfWork();
            $changeSet = $uow->getEntityChangeSet($object);

            if (isset($changeSet['buybackOffer'])) {
                $this->updateBuybackOfferIds[$object->getId()] = $changeSet['buybackOffer'];
            }
        }
    }

    private function updateClosed(EntityManagerInterface $em, object $object, bool $create = false): void
    {
        if ($object instanceof AuditBatchInterface) {
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
}
