<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Module\BuybackBundle\Choice;

use Klipper\Component\Choice\ChoiceInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
final class BuybackOfferCalculationMethod implements ChoiceInterface
{
    public static function listIdentifiers(): array
    {
        return [
            'by_condition' => 'buyback_offer_calculation_method.by_condition',
            'by_state' => 'buyback_offer_calculation_method.by_state',
        ];
    }

    public static function getValues(): array
    {
        return array_keys(static::listIdentifiers());
    }

    public static function getTranslationDomain(): string
    {
        return 'choices';
    }
}
