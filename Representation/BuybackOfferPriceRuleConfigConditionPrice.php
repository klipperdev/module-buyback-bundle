<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Module\BuybackBundle\Representation;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class BuybackOfferPriceRuleConfigConditionPrice
{
    /**
     * @var null|int|string
     */
    private $id;

    private ?float $price = null;

    /**
     * @param null|int|string $id
     */
    public function setId($id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return null|int|string
     */
    public function getId()
    {
        return $this->id;
    }

    public function setPrice(?float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }
}
