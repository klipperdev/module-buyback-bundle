<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Module\BuybackBundle\Form\Type;

use Klipper\Component\Metadata\MetadataManagerInterface;
use Klipper\Module\BuybackBundle\Model\AuditConditionInterface;
use Klipper\Module\BuybackBundle\Model\AuditItemInterface;
use Klipper\Module\BuybackBundle\Representation\BuybackOfferPriceRuleConfigConditionPrice;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class BuybackOfferPriceRuleConfigConditionPriceType extends AbstractType
{
    private MetadataManagerInterface $metadataManager;

    public function __construct(MetadataManagerInterface $metadataManager)
    {
        $this->metadataManager = $metadataManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $conditionMeta = $this->metadataManager->get(AuditConditionInterface::class);
        $conditionIdField = $conditionMeta->getField($conditionMeta->getFieldIdentifier());

        $auditMeta = $this->metadataManager->get(AuditItemInterface::class);
        $auditConditionPriceField = $auditMeta->getField('conditionPrice');

        $builder
            ->add('id', 'integer' === $conditionIdField->getType() ? IntegerType::class : null)
            ->add('price', $auditConditionPriceField->getFormType(), $auditConditionPriceField->getFormOptions())
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BuybackOfferPriceRuleConfigConditionPrice::class,
        ]);
    }
}
