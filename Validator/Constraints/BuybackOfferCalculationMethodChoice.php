<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Module\BuybackBundle\Validator\Constraints;

use Klipper\Component\Choice\Validator\Constraints\Choice;
use Klipper\Module\BuybackBundle\Choice\BuybackOfferCalculationMethod;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 *
 * @Annotation
 */
final class BuybackOfferCalculationMethodChoice extends Choice
{
    public $callback = [
        BuybackOfferCalculationMethod::class,
        'getValues',
    ];
}
