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

use Klipper\Module\BuybackBundle\Model\AuditConditionInterface;
use Klipper\Module\BuybackBundle\Model\AuditRequestInterface;
use Klipper\Module\DeviceBundle\Model\DeviceInterface;
use Klipper\Module\ProductBundle\Model\ProductCombinationInterface;
use Klipper\Module\ProductBundle\Model\ProductInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class AuditItemConfirmationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('audit_request_reference', EntityType::class, [
                'class' => AuditRequestInterface::class,
                'property_path' => 'auditRequest',
                'choice_value' => 'reference',
            ])
            ->add('device_imei', EntityType::class, [
                'class' => DeviceInterface::class,
                'property_path' => 'device',
                'choice_value' => 'imei',
            ])
            ->add('product_reference', EntityType::class, [
                'class' => ProductInterface::class,
                'property_path' => 'product',
                'choice_value' => 'reference',
            ])
            ->add('product_combination_reference', EntityType::class, [
                'class' => ProductCombinationInterface::class,
                'property_path' => 'productCombination',
                'choice_value' => 'reference',
            ])
            ->add('condition_name', EntityType::class, [
                'class' => AuditConditionInterface::class,
                'property_path' => 'auditCondition',
                'choice_value' => 'name',
            ])
        ;
    }
}
