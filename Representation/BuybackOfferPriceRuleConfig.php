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
class BuybackOfferPriceRuleConfig
{
    private ?float $functionalPrice = null;

    private ?float $nonfunctionalPrice = null;

    /**
     * @var BuybackOfferPriceRuleConfigConditionPrice[]
     */
    private array $conditionPrices = [];

    public function setFunctionalPrice(?float $functionalPrice): self
    {
        $this->functionalPrice = $functionalPrice;

        return $this;
    }

    public function getFunctionalPrice(): ?float
    {
        return $this->functionalPrice;
    }

    public function setNonfunctionalPrice(?float $nonfunctionalPrice): self
    {
        $this->nonfunctionalPrice = $nonfunctionalPrice;

        return $this;
    }

    public function getNonfunctionalPrice(): ?float
    {
        return $this->nonfunctionalPrice;
    }

    /**
     * @param BuybackOfferPriceRuleConfigConditionPrice[] $conditionPrices
     */
    public function setConditionPrices(array $conditionPrices): self
    {
        $this->conditionPrices = $conditionPrices;

        return $this;
    }

    /**
     * @return BuybackOfferPriceRuleConfigConditionPrice[]
     */
    public function getConditionPrices(): array
    {
        return $this->conditionPrices;
    }
}
