<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Module\BuybackBundle\Model\Traits;

use Klipper\Module\BuybackBundle\Model\BuybackModuleInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
interface BuybackModuleableInterface
{
    /**
     * @return static
     */
    public function setBuybackModule(?BuybackModuleInterface $buybackModule);

    public function getBuybackModule(): ?BuybackModuleInterface;
}
