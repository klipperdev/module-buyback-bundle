<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Module\BuybackBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Klipper\Bundle\ApiBundle\Controller\ControllerHelper;
use Klipper\Component\Resource\Converter\ConverterRegistryInterface;
use Klipper\Component\Resource\Domain\DomainManagerInterface;
use Klipper\Module\BuybackBundle\Form\Type\BuybackOfferPriceRuleConfigType;
use Klipper\Module\BuybackBundle\Model\AuditItemInterface;
use Klipper\Module\BuybackBundle\Model\BuybackOfferInterface;
use Klipper\Module\BuybackBundle\Representation\BuybackOfferPriceRuleConfig;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class ApiBuybackOfferController
{
    /**
     * List the available products to update prices for a buyback offer.
     *
     * @Entity("id", class="App:BuybackOffer")
     *
     * @Route("/buyback_offers/{id}/available-products", methods={"GET"})
     */
    public function listProductAction(
        ControllerHelper $helper,
        EntityManagerInterface $em,
        BuybackOfferInterface $id
    ): Response {
        $concatCombination = 'GROUP_CONCAT(DISTINCT CONCAT_WS(\' : \', pcaia.label, pcai.label) ORDER BY pcai.id ASC SEPARATOR \' • \')';

        $qb = $em->createQueryBuilder()
            ->select('CONCAT_WS(\'@\', p.id, pc.id) as id')
            ->addSelect('p.id as product_id')
            ->addSelect('pc.id as product_combination_id')
            ->addSelect('b.name as product_brand_name')
            ->addSelect('p.name as product_name')
            ->addSelect('CASE WHEN ai.productCombination IS NOT NULL THEN '.$concatCombination.' ELSE :null END as product_combination_name')

            ->from(AuditItemInterface::class, 'ai')
            ->join('ai.auditRequest', 'ar')
            ->join('ai.status', 'ais')
            ->join('ai.product', 'p')
            ->leftJoin('ai.productCombination', 'pc')
            ->leftJoin('p.brand', 'b')
            ->leftJoin('pc.attributeItems', 'pcai')
            ->leftJoin('pcai.attribute', 'pcaia')

            ->where('ai.buybackOffer = :buybackOffer')
            ->andWhere('ais.value = :status')

            ->groupBy('p.id')
            ->addGroupBy('pc.id')

            ->orderBy('b.name')
            ->addOrderBy('p.name')

            ->setParameter('buybackOffer', $id)
            ->setParameter('status', 'valorised')
            ->setParameter('null', null)
        ;

        return $helper->views($qb);
    }

    /**
     * List the available audit conditions to update prices for a buyback offer.
     *
     * @Entity("id", class="App:BuybackOffer")
     *
     * @Route("/buyback_offers/{id}/available-conditions", methods={"GET"})
     */
    public function listAuditConditionAction(
        Request $request,
        ControllerHelper $helper,
        EntityManagerInterface $em,
        BuybackOfferInterface $id
    ): Response {
        $qb = $em->createQueryBuilder()
            ->select('ac.id as id')
            ->addSelect('ac.label')
            ->addSelect('ac.name')

            ->from(AuditItemInterface::class, 'ai')
            ->join('ai.auditRequest', 'ar')
            ->join('ai.auditCondition', 'ac')
            ->join('ai.status', 'ais')

            ->where('ai.buybackOffer = :buybackOffer')
            ->andWhere('ais.value = :status')

            ->groupBy('ac.id')

            ->orderBy('ac.label')

            ->setParameter('buybackOffer', $id)
            ->setParameter('status', 'valorised')
        ;

        $this->filterAvailableQueryByProducts($request, $qb);

        return $helper->views($qb);
    }

    /**
     * List the available audits to update prices for a buyback offer.
     *
     * @Entity("id", class="App:BuybackOffer")
     *
     * @Route("/buyback_offers/{id}/available-audits", methods={"GET"})
     */
    public function listAvailableAuditAction(
        Request $request,
        ControllerHelper $helper,
        EntityManagerInterface $em,
        BuybackOfferInterface $id
    ): Response {
        $qb = $em->getRepository(AuditItemInterface::class)
            ->createQueryBuilder('ai')

            ->where('ai.buybackOffer = :buybackOffer')
            ->andWhere('ar.supplierOrderNumber IS NOT NULL')
            ->andWhere('ai.auditCondition IS NOT NULL')
            ->andWhere('cs.value = :status')

            ->setParameter('buybackOffer', $id)
            ->setParameter('status', 'valorised')
        ;

        $this->filterAvailableQueryByProducts($request, $qb, false);
        $this->filterAvailableQueryByConditions($request, $qb);
        $this->filterAvailableQueryByRepairs($request, $qb);
        $this->filterAvailableQueryByAuditItems($request, $qb);

        return $helper->views($qb);
    }

    /**
     * Apply a price rule on selected audit items for a buyback offer.
     *
     * @Entity("id", class="App:BuybackOffer")
     *
     * @Route("/buyback_offers/{id}/apply-price-rule", methods={"PATCH"})
     */
    public function applyPriceRuleAction(
        Request $request,
        ConverterRegistryInterface $converterRegistry,
        FormFactoryInterface $formFactory,
        ControllerHelper $helper,
        DomainManagerInterface $domainManager,
        TranslatorInterface $translator,
        EntityManagerInterface $em,
        BuybackOfferInterface $id
    ): Response {
        $qb = $em->createQueryBuilder()
            ->select('ai')

            ->from(AuditItemInterface::class, 'ai')
            ->join('ai.auditRequest', 'ar')
            ->join('ai.status', 'cs')

            ->where('ai.buybackOffer = :buybackOffer')
            ->andWhere('ar.supplierOrderNumber IS NOT NULL')
            ->andWhere('ai.auditCondition IS NOT NULL')
            ->andWhere('cs.value = :status')

            ->setParameter('buybackOffer', $id)
            ->setParameter('status', 'valorised')
        ;

        $this->filterAvailableQueryByProducts($request, $qb);
        $this->filterAvailableQueryByConditions($request, $qb);
        $this->filterAvailableQueryByRepairs($request, $qb);
        $this->filterAvailableQueryByAuditItems($request, $qb);

        /** @var AuditItemInterface[] $audits */
        $audits = $qb->getQuery()->getResult();

        if (empty($audits)) {
            throw new BadRequestHttpException($translator->trans('klipper_buyback.buyback_offer.apply_price_rule.no_audit_selected', [], 'validators'));
        }

        $domain = $domainManager->get(AuditItemInterface::class);
        $form = $formFactory->create(BuybackOfferPriceRuleConfigType::class);
        $data = $converterRegistry->get('json')->convert((string) $request->getContent());

        $form->submit($data, false);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var BuybackOfferPriceRuleConfig $priceRuleConfig */
            $priceRuleConfig = $form->getData();

            foreach ($audits as $audit) {
                $this->applyPriceRuleOnAuditItem($priceRuleConfig, $audit);
            }

            $res = $domain->updates($audits);

            if ($res->hasErrors()) {
                $data = $helper->formatResultList($res, true);
                $view = $helper->createView($data, Response::HTTP_BAD_REQUEST);
            } else {
                $em->refresh($id);
                $view = $helper->createView($id);
            }

            return $helper->view($view);
        }

        return $helper->view($helper->createView(
                $helper->formatFormErrors($form),
                Response::HTTP_BAD_REQUEST
            ));
    }

    private function filterAvailableQueryByProducts(Request $request, QueryBuilder $qb, bool $addJoins = true): void
    {
        $productIds = (array) $request->query->get('p', []);

        if (!empty($productIds)) {
            $filterExpr = [];

            if ($addJoins) {
                $qb
                    ->join('ai.product', 'p')
                    ->leftJoin('ai.productCombination', 'pc')
                ;
            }

            foreach ($productIds as $i => $productId) {
                $split = explode('@', $productId);
                $combinationId = $split[1] ?? null;
                $productId = $split[0];

                if (null !== $combinationId) {
                    $filterExpr[] = 'p.id = :product_'.$i.' AND pc.id = :combination_'.$i;
                    $qb->setParameter('product_'.$i, $productId);
                    $qb->setParameter('combination_'.$i, $combinationId);
                } else {
                    $filterExpr[] = 'p.id = :product_'.$i;
                    $qb->setParameter('product_'.$i, $productId);
                }
            }

            if (!empty($filterExpr)) {
                $qb->andWhere($qb->expr()->orX(...$filterExpr));
            }
        }
    }

    private function filterAvailableQueryByConditions(Request $request, QueryBuilder $qb): void
    {
        $conditionIds = (array) $request->query->get('c', []);

        if (!empty($conditionIds)) {
            $filterExpr = [];

            foreach ($conditionIds as $i => $conditionId) {
                $filterExpr[] = 'ai.auditCondition = :condition_'.$i;
                $qb->setParameter('condition_'.$i, $conditionId);
            }

            if (!empty($filterExpr)) {
                $qb->andWhere($qb->expr()->orX(...$filterExpr));
            }
        }
    }

    private function filterAvailableQueryByRepairs(Request $request, QueryBuilder $qb): void
    {
        $repairs = $request->query->getAlpha('repairs');

        if (!empty($repairs)) {
            switch ($repairs) {
                case 'with':
                    $qb->andWhere('ai.repair IS NOT NULL');

                    break;

                case 'without':
                    $qb->andWhere('ai.repair IS NULL');

                    break;

                case 'all':
                default:
                    break;
            }
        }
    }

    private function filterAvailableQueryByAuditItems(Request $request, QueryBuilder $qb): void
    {
        $auditIds = (array) $request->query->get('aid', []);

        if (!empty($auditIds)) {
            $qb
                ->andWhere('ai.id IN (:auditIds)')
                ->setParameter('auditIds', $auditIds)
            ;
        }
    }

    private function applyPriceRuleOnAuditItem(BuybackOfferPriceRuleConfig $config, AuditItemInterface $audit): void
    {
        $auditCondition = $audit->getAuditCondition();

        if (null === $auditCondition) {
            return;
        }

        $auditConditionId = $auditCondition->getId();

        if (null !== ($state = $auditCondition->getState())) {
            if ('functional' === $state && null !== $config->getFunctionalPrice()) {
                $audit->setStatePrice((float) $config->getFunctionalPrice());
            } elseif ('nonfunctional' === $state && null !== $config->getNonfunctionalPrice()) {
                $audit->setStatePrice((float) $config->getNonfunctionalPrice());
            }
        }

        foreach ($config->getConditionPrices() as $conditionPrice) {
            if ($auditConditionId === $conditionPrice->getId() && null !== $conditionPrice->getPrice()) {
                $audit->setConditionPrice((float) $conditionPrice->getPrice());
            }
        }
    }
}
