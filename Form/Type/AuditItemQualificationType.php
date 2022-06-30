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

use Klipper\Module\BuybackBundle\Model\AuditBatchInterface;
use Klipper\Module\BuybackBundle\Model\AuditConditionInterface;
use Klipper\Module\BuybackBundle\Representation\AuditItemQualification;
use Klipper\Module\DeviceBundle\Model\DeviceInterface;
use Klipper\Module\ProductBundle\Model\ProductCombinationInterface;
use Klipper\Module\ProductBundle\Model\ProductInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class AuditItemQualificationType extends AbstractType
{
    private TranslatorInterface $translator;

    public function __construct(
        TranslatorInterface $translator
    ) {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', NumberType::class, [
                'property_path' => 'id',
                'scale' => 0,
            ])
            ->add('audit_batch', EntityType::class, [
                'class' => AuditBatchInterface::class,
                'property_path' => 'auditBatch',
            ])
            ->add('device', EntityType::class, [
                'class' => DeviceInterface::class,
                'property_path' => 'device',
            ])
            ->add('product', EntityType::class, [
                'class' => ProductInterface::class,
                'property_path' => 'product',
            ])
            ->add('product_combination', EntityType::class, [
                'class' => ProductCombinationInterface::class,
                'property_path' => 'productCombination',
            ])
            ->add('audit_condition', EntityType::class, [
                'class' => AuditConditionInterface::class,
                'property_path' => 'auditCondition',
            ])
            ->add('comment', TextareaType::class, [
                'property_path' => 'comment',
            ])
            ->add('repair_declared_breakdown_by_customer', TextareaType::class, [
                'property_path' => 'repairDeclaredBreakdownByCustomer',
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (PreSubmitEvent $event): void {
            $data = $event->getData();

            if (!empty($data['repair_declared_breakdown_by_customer']) && empty($data['device'])) {
                $event->getForm()->addError(
                    new FormError($this->translator->trans('klipper_buyback.audit_item.repair.device_required', [], 'validators'))
                );
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AuditItemQualification::class,
        ]);
    }
}
