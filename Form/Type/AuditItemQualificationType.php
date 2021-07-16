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

use Doctrine\ORM\EntityManagerInterface;
use Klipper\Module\BuybackBundle\Model\AuditConditionInterface;
use Klipper\Module\BuybackBundle\Model\AuditRequestInterface;
use Klipper\Module\DeviceBundle\Model\DeviceInterface;
use Klipper\Module\ProductBundle\Model\ProductCombinationInterface;
use Klipper\Module\ProductBundle\Model\ProductInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class AuditItemQualificationType extends AbstractType
{
    private EntityManagerInterface $em;

    private TranslatorInterface $translator;

    public function __construct(
        EntityManagerInterface $em,
        TranslatorInterface $translator
    ) {
        $this->em = $em;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('audit_request_reference', EntityType::class, [
                'class' => AuditRequestInterface::class,
                'property_path' => 'auditRequest',
                'choice_value' => 'reference',
                'id_field' => 'reference',
            ])
            ->add('device_imei_or_sn', TextType::class, [
                'property_path' => 'device',
            ])
            ->add('product_reference', EntityType::class, [
                'class' => ProductInterface::class,
                'property_path' => 'product',
                'choice_value' => 'reference',
                'id_field' => 'reference',
            ])
            ->add('product_combination_reference', EntityType::class, [
                'class' => ProductCombinationInterface::class,
                'property_path' => 'productCombination',
                'choice_value' => 'reference',
                'id_field' => 'reference',
            ])
            ->add('condition_name', EntityType::class, [
                'class' => AuditConditionInterface::class,
                'property_path' => 'auditCondition',
                'choice_value' => 'name',
                'id_field' => 'name',
            ])
        ;

        $builder->get('device_imei_or_sn')->addEventListener(FormEvents::PRE_SUBMIT, function (PreSubmitEvent $event): void {
            $res = $this->em->createQueryBuilder()
                ->select('d')
                ->from(DeviceInterface::class, 'd')
                ->where('d.imei = :data')
                ->orWhere('d.serialNumber = :data')
                ->setMaxResults(1)
                ->setParameter('data', $event->getData())
                ->getQuery()
                ->getOneOrNullResult()
            ;

            $event->setData($res);

            if (null === $event->getData()) {
                $event->getForm()->addError(
                    new FormError($this->translator->trans('This value is not valid.', [], 'validators'))
                );
            }
        });
    }
}
