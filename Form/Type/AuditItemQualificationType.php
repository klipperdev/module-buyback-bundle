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
use Klipper\Component\Resource\Object\ObjectFactoryInterface;
use Klipper\Module\BuybackBundle\Audit\AuditManagerInterface;
use Klipper\Module\BuybackBundle\Model\AuditConditionInterface;
use Klipper\Module\BuybackBundle\Model\AuditItemInterface;
use Klipper\Module\BuybackBundle\Model\AuditRequestInterface;
use Klipper\Module\DeviceBundle\Model\DeviceInterface;
use Klipper\Module\ProductBundle\Model\ProductCombinationInterface;
use Klipper\Module\ProductBundle\Model\ProductInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PostSubmitEvent;
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

    private ObjectFactoryInterface $objectFactory;

    private AuditManagerInterface $auditManager;

    private TranslatorInterface $translator;

    public function __construct(
        EntityManagerInterface $em,
        ObjectFactoryInterface $objectFactory,
        AuditManagerInterface $auditManager,
        TranslatorInterface $translator
    ) {
        $this->em = $em;
        $this->objectFactory = $objectFactory;
        $this->auditManager = $auditManager;
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
            ->add('repair_declared_breakdown_by_customer', TextType::class, [
                'mapped' => false,
            ])
        ;

        $builder->get('device_imei_or_sn')->addEventListener(FormEvents::PRE_SUBMIT, function (PreSubmitEvent $event): void {
            $data = $event->getData();

            if (\is_string($data)) {
                /** @var null|DeviceInterface $res */
                $res = $this->em->createQueryBuilder()
                    ->select('d')
                    ->from(DeviceInterface::class, 'd')
                    ->where('d.imei = :data')
                    ->orWhere('d.imei2 = :data')
                    ->orWhere('d.serialNumber = :data')
                    ->setMaxResults(1)
                    ->setParameter('data', $event->getData())
                    ->getQuery()
                    ->getOneOrNullResult()
                ;

                if (null !== $res) {
                    $event->setData($res);
                } else {
                    /** @var DeviceInterface $device */
                    $device = $this->objectFactory->create(DeviceInterface::class);
                    $device->setImei($data);

                    try {
                        $this->em->persist($device);
                        $this->em->flush();

                        $event->setData($device);
                    } catch (\Throwable $e) {
                        $event->setData(null);
                        $event->getForm()->addError(
                            new FormError($this->translator->trans('This value is not valid.', [], 'validators'))
                        );
                    }
                }
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (PreSubmitEvent $event): void {
            $data = $event->getData();

            if (!empty($data['repair_declared_breakdown_by_customer']) && empty($data['device_imei_or_sn'])) {
                $event->getForm()->addError(
                    new FormError($this->translator->trans('klipper_buyback.audit_item.repair.device_required', [], 'validators'))
                );
            }
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (PostSubmitEvent $event): void {
            /** @var AuditItemInterface $data */
            $data = $event->getData();
            $declaredBreakdownByCustomer = $event->getForm()->get('repair_declared_breakdown_by_customer')->getData();

            if (!empty($declaredBreakdownByCustomer)) {
                $repair = $this->auditManager->transferToRepair($data);
                $repair->setDeclaredBreakdownByCustomer($declaredBreakdownByCustomer);

                try {
                    $this->em->persist($repair);
                    $this->em->flush();
                } catch (\Throwable $e) {
                    $event->getForm()->addError(
                        new FormError($this->translator->trans('domain.database_error', [], 'KlipperResource'))
                    );
                }
            }
        });
    }
}
