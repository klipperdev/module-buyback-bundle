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

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Klipper\Module\BuybackBundle\Model\BuybackModuleInterface;

/**
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
trait BuybackModuleableTrait
{
    /**
     * @ORM\OneToOne(
     *     targetEntity="Klipper\Module\BuybackBundle\Model\BuybackModuleInterface",
     *     mappedBy="account",
     *     fetch="EXTRA_LAZY"
     * )
     * @ORM\JoinColumn(
     *     name="buyback_module_id",
     *     referencedColumnName="id",
     *     onDelete="SET NULL",
     *     nullable=true
     * )
     *
     * @Serializer\Expose
     * @Serializer\ReadOnly
     * @Serializer\MaxDepth(3)
     * @Serializer\Groups({"View", "ViewsDetails"})
     */
    protected ?BuybackModuleInterface $buybackModule = null;

    /**
     * @see BuybackModuleableInterface::setBuybackModule()
     */
    public function setBuybackModule(?BuybackModuleInterface $buybackModule): self
    {
        $this->buybackModule = $buybackModule;

        return $this;
    }

    /**
     * @see BuybackModuleableInterface::getBuybackModule()
     */
    public function getBuybackModule(): ?BuybackModuleInterface
    {
        return $this->buybackModule;
    }
}
