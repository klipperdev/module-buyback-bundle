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
use Klipper\Module\BuybackBundle\Model\AuditItemInterface;
use Klipper\Module\BuybackBundle\Representation\BuybackOfferPriceRuleConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class BuybackOfferPriceRuleConfigType extends AbstractType
{
    private MetadataManagerInterface $metadataManager;

    public function __construct(MetadataManagerInterface $metadataManager)
    {
        $this->metadataManager = $metadataManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $meta = $this->metadataManager->get(AuditItemInterface::class);
        $stateFormType = $meta->getField('statePrice')->getFormType();
        $stateFormOptions = $meta->getField('statePrice')->getFormOptions();

        $builder
            ->add('functional_price', $stateFormType, array_merge($stateFormOptions, [
                'property_path' => 'functionalPrice',
            ]))
            ->add('nonfunctional_price', $stateFormType, array_merge($stateFormOptions, [
                'property_path' => 'nonfunctionalPrice',
            ]))
            ->add('condition_prices', CollectionType::class, [
                'property_path' => 'conditionPrices',
                'entry_type' => BuybackOfferPriceRuleConfigConditionPriceType::class,
                'allow_add' => true,
                'allow_delete' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BuybackOfferPriceRuleConfig::class,
        ]);
    }
}
