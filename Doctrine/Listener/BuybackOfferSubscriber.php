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
use Klipper\Module\BuybackBundle\Model\AuditItemInterface;
use Klipper\Module\BuybackBundle\Model\BuybackOfferInterface;
use Klipper\Module\BuybackBundle\Model\Traits\BuybackModuleableInterface;
use Klipper\Module\DeviceBundle\Model\DeviceInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class BuybackOfferSubscriber implements EventSubscriber
{
    private ChoiceManagerInterface $choiceManager;

    private CodeGenerator $generator;

    private TranslatorInterface $translator;

    private array $closedStatues;

    private array $validatedStatues;

    /**
     * @var int[]|string[]
     */
    private array $updateQuantities = [];

    /**
     * @var int[]|string[]
     */
    private array $updateDeviceStatuses = [];

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
        $uow = $event->getEntityManager()->getUnitOfWork();
        $changeSet = $uow->getEntityChangeSet($object);

        if ($object instanceof BuybackOfferInterface) {
            if (null === $object->getReference()) {
                $object->setReference($this->generator->generate());
            }

            $account = $object->getAccount();
            $shippingAddress = $object->getShippingAddress();
            $supplier = $object->getSupplier();
            $invoiceAddress = $object->getInvoiceAddress();
            $status = $object->getStatus();
            $calculationMethod = $object->getCalculationMethod();

            if (null === $object->getDate()) {
                $object->setDate(new \DateTime());
            }

            if (null === $shippingAddress
                || null === $supplier
                || null === $invoiceAddress
                || null === $status
                || null === $calculationMethod
            ) {
                if ($account instanceof BuybackModuleableInterface && null !== ($module = $account->getBuybackModule())) {
                    if (null === $shippingAddress) {
                        $object->setShippingAddress($module->getShippingAddress());
                    }

                    if (null === $supplier) {
                        $object->setSupplier($module->getSupplier());
                    }

                    if (null === $invoiceAddress) {
                        $object->setInvoiceAddress($module->getInvoiceAddress());
                    }

                    if (null === $status) {
                        $status = $this->choiceManager->getChoice('buyback_offer_status', null);
                        $object->setStatus($status);
                    }
                }

                if (null === $calculationMethod) {
                    $calculationMethod = 'by_state';
                    $object->setCalculationMethod($calculationMethod);
                }

                if (null === $object->getShippingAddress()) {
                    ListenerUtil::thrownError($this->translator->trans(
                        'klipper_buyback.buyback_offer.shipping_address_required',
                        [],
                        'validators'
                    ), $object, 'shippingAddress');
                }
            }

            if (isset($changeSet['calculationMethod'])) {
                $price = 'by_condition' === $object->getCalculationMethod()
                    ? $object->getTotalConditionPrice()
                    : $object->getTotalStatePrice();

                $object->setTotalPrice($price + (float) $object->getTotalRepairPrice());
            }
        } elseif ($object instanceof AuditItemInterface) {
            if (isset($changeSet['buybackOffer'])) {
                /** @var null|BuybackOfferInterface $oldOffer */
                $oldOffer = $changeSet['buybackOffer'][0];

                /** @var null|BuybackOfferInterface $newOffer */
                $newOffer = $changeSet['buybackOffer'][1];

                if (null !== $oldOffer) {
                    $this->reCalculateBuybackOfferQuantities($oldOffer);
                }

                if (null !== $newOffer) {
                    $this->reCalculateBuybackOfferQuantities($newOffer);
                }
            } elseif (null !== ($offer = $object->getBuybackOffer())) {
                if (isset($changeSet['statePrice'])
                    || isset($changeSet['conditionPrice'])
                    || isset($changeSet['repairPrice'])
                    || isset($changeSet['includedRepairPrice'])
                    || null === $object->getId()
                ) {
                    $this->reCalculateBuybackOfferQuantities($offer);
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
            $this->validateClosedEmptyItems($object);
        }

        foreach ($uow->getScheduledEntityUpdates() as $object) {
            $this->validateAuditItems($em, $object);
            $this->updateClosed($em, $object);
            $this->validateClosedEmptyItems($object);
        }

        foreach ($uow->getScheduledEntityDeletions() as $object) {
            if ($object instanceof AuditItemInterface && null !== ($buybackOffer = $object->getBuybackOffer())) {
                $this->reCalculateBuybackOfferQuantities($buybackOffer);
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if (\count($this->updateQuantities) > 0) {
            $em = $args->getEntityManager();

            $res = $em->createQueryBuilder()
                ->addSelect('abo.id as buybackOfferId')
                ->addSelect('COUNT(ai.id) as total')
                ->addSelect('SUM(ai.statePrice) as statePrice')
                ->addSelect('SUM(ai.conditionPrice) as conditionPrice')
                ->addSelect('SUM(CASE WHEN ai.includedRepairPrice = true THEN ai.repairPrice ELSE 0 END) as repairPrice')

                ->from(AuditItemInterface::class, 'ai')
                ->join('ai.buybackOffer', 'abo')

                ->groupBy('abo.id')

                ->where('abo.id in (:ids)')

                ->setParameter('ids', $this->updateQuantities)

                ->getQuery()
                ->getResult()
            ;

            $findBuybackOfferIds = array_map(static function ($item) {
                return $item['buybackOfferId'];
            }, $res);

            foreach ($this->updateQuantities as $id) {
                if (!\in_array($id, $findBuybackOfferIds, true)) {
                    $res[] = [
                        'buybackOfferId' => $id,
                        'total' => 0,
                        'statePrice' => 0,
                        'conditionPrice' => 0,
                        'repairPrice' => 0,
                    ];
                }
            }

            foreach ($res as $resItem) {
                // Do not the persist/flush in postFlush event
                if ((float) $resItem['statePrice'] >= (float) $resItem['conditionPrice']) {
                    $calculationMethod = 'by_state';
                    $totalPrice = (float) $resItem['statePrice'];
                } else {
                    $calculationMethod = 'by_condition';
                    $totalPrice = (float) $resItem['conditionPrice'];
                }

                $totalPrice = $totalPrice + (float) $resItem['repairPrice'];

                $em->createQueryBuilder()
                    ->update(BuybackOfferInterface::class, 'bo')
                    ->set('bo.numberOfItems', ':numberOfItems')
                    ->set('bo.totalStatePrice', ':statePrice')
                    ->set('bo.totalConditionPrice', ':conditionPrice')
                    ->set('bo.totalRepairPrice', ':repairPrice')
                    ->set('bo.totalPrice', ':totalPrice')
                    ->set('bo.calculationMethod', ':calculationMethod')

                    ->where('bo.id = :id')

                    ->setParameter('id', $resItem['buybackOfferId'])
                    ->setParameter('numberOfItems', (int) $resItem['total'])
                    ->setParameter('statePrice', (float) $resItem['statePrice'])
                    ->setParameter('conditionPrice', (float) $resItem['conditionPrice'])
                    ->setParameter('repairPrice', (float) $resItem['repairPrice'])
                    ->setParameter('totalPrice', $totalPrice)
                    ->setParameter('calculationMethod', $calculationMethod)

                    ->getQuery()
                    ->execute()
                ;
            }
        }

        if (\count($this->updateDeviceStatuses) > 0) {
            $em = $args->getEntityManager();
            $status = $this->choiceManager->getChoice('device_status', 'buybacked');

            $em->createQueryBuilder()
                ->update(DeviceInterface::class, 'd')
                ->set('d.status', ':status')
                ->where('d.lastAuditItem IN (SELECT ai.id FROM '.AuditItemInterface::class.' ai where ai.buybackOffer IN (:ids))')
                ->setParameter('ids', $this->updateDeviceStatuses)
                ->setParameter('status', $status)
                ->getQuery()
                ->execute()
            ;
        }

        $this->updateQuantities = [];
        $this->updateDeviceStatuses = [];
    }

    private function validateModuleEnabled(object $object): void
    {
        if ($object instanceof BuybackOfferInterface) {
            $account = $object->getAccount();
            $module = $account instanceof BuybackModuleableInterface
                ? $account->getBuybackModule()
                : null;

            if (null === $module || !$module->isEnabled()) {
                ListenerUtil::thrownError($this->translator->trans(
                    'klipper_buyback.buyback_offer.module_must_be_enabled',
                    [],
                    'validators'
                ));
            }
        }
    }

    private function validateAuditItems(EntityManagerInterface $em, object $object): void
    {
        if ($object instanceof AuditItemInterface) {
            $uow = $em->getUnitOfWork();
            $changeSet = $uow->getEntityChangeSet($object);

            if (isset($changeSet['buybackOffer'])) {
                /** @var null|BuybackOfferInterface $oldOffer */
                $oldOffer = $changeSet['buybackOffer'][0];

                /** @var null|BuybackOfferInterface $newOffer */
                $newOffer = $changeSet['buybackOffer'][1];

                if (null !== $oldOffer && $oldOffer->isValidated()) {
                    ListenerUtil::thrownError($this->translator->trans(
                        'klipper_buyback.buyback_offer.closed_offer_cannot_be_detached',
                        [],
                        'validators'
                    ));
                }

                if (null !== $newOffer && $newOffer->isClosed()) {
                    ListenerUtil::thrownError($this->translator->trans(
                        'klipper_buyback.buyback_offer.closed_offer_cannot_be_attached',
                        [],
                        'validators'
                    ));
                }
            }
        }
    }

    private function updateClosed(EntityManagerInterface $em, object $object, bool $create = false): void
    {
        if ($object instanceof BuybackOfferInterface) {
            $uow = $em->getUnitOfWork();
            $changeSet = $uow->getEntityChangeSet($object);

            if ($create || isset($changeSet['status'])) {
                $closed = null === $object->getStatus() || \in_array($object->getStatus()->getValue(), $this->closedStatues, true);
                $invalidated = null === $object->getStatus() || !\in_array($object->getStatus()->getValue(), $this->validatedStatues, true);
                $object->setClosed($closed);
                $object->setValidated(!$invalidated);
                $object->setValidatedAt(null);

                if ($object->isValidated()) {
                    $object->setValidatedAt(new \DateTime());
                    $this->reCalculateBuybackOfferDeviceStatuses($object);
                }

                $classMetadata = $em->getClassMetadata(ClassUtils::getClass($object));
                $uow->recomputeSingleEntityChangeSet($classMetadata, $object);
            }
        }
    }

    private function validateClosedEmptyItems(object $object): void
    {
        if ($object instanceof BuybackOfferInterface) {
            if ($object->isClosed() && $object->isValidated() && 0 === $object->getNumberOfItems()) {
                ListenerUtil::thrownError($this->translator->trans(
                    'klipper_buyback.buyback_offer.cannot_be_validated',
                    [],
                    'validators'
                ));
            }
        }
    }

    private function reCalculateBuybackOfferQuantities(BuybackOfferInterface $buybackOffer): void
    {
        $this->updateQuantities[] = $buybackOffer->getId();
        $this->updateQuantities = array_unique($this->updateQuantities);
    }

    private function reCalculateBuybackOfferDeviceStatuses(BuybackOfferInterface $buybackOffer): void
    {
        $this->updateDeviceStatuses[] = $buybackOffer->getId();
        $this->updateDeviceStatuses = array_unique($this->updateDeviceStatuses);
    }
}
