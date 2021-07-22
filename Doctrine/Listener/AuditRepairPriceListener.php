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

use Doctrine\ORM\EntityManagerInterface;
use Klipper\Module\BuybackBundle\Model\AuditItemInterface;
use Klipper\Module\BuybackBundle\Model\Traits\AuditRepairableInterface;
use Klipper\Module\RepairBundle\Doctrine\Listener\RepairPriceListenerInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class AuditRepairPriceListener implements RepairPriceListenerInterface
{
    public function onUpdate(EntityManagerInterface $em, array $repairPrices): void
    {
        /** @var AuditItemInterface[] $audits */
        $audits = $em->createQueryBuilder()
            ->select('ai')
            ->from(AuditItemInterface::class, 'ai')
            ->where('ai.repair IN (:repairIds)')
            ->setParameter('repairIds', array_keys($repairPrices))
            ->getQuery()
            ->getResult()
        ;

        if (\count($audits)) {
            $persist = false;

            foreach ($audits as $audit) {
                if ($audit instanceof AuditRepairableInterface) {
                    $price = -1 * ($repairPrices[$audit->getRepairId()] ?? 0.0);

                    if ($price !== $audit->getRepairPrice()) {
                        $audit->setRepairPrice($price);

                        $em->persist($audit);
                        $persist = true;
                    }
                }
            }

            if ($persist) {
                $em->flush();
            }
        }
    }
}
