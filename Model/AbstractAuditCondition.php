<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Module\BuybackBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Klipper\Component\Model\Traits\EnableTrait;
use Klipper\Component\Model\Traits\LabelableTrait;
use Klipper\Component\Model\Traits\NameableTrait;
use Klipper\Component\Model\Traits\OrganizationalRequiredTrait;
use Klipper\Component\Model\Traits\SortableTrait;
use Klipper\Component\Model\Traits\TimestampableTrait;
use Klipper\Component\Model\Traits\UserTrackableTrait;
use Klipper\Module\BuybackBundle\Validator\Constraints as KlipperBuybackAssert;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Audit Condition model.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 *
 * @Serializer\ExclusionPolicy("all")
 */
abstract class AbstractAuditCondition implements AuditConditionInterface
{
    use EnableTrait;
    use LabelableTrait;
    use NameableTrait;
    use OrganizationalRequiredTrait;
    use SortableTrait;
    use TimestampableTrait;
    use UserTrackableTrait;

    /**
     * @ORM\Column(type="string", length=128, nullable=true)
     *
     * @KlipperBuybackAssert\AuditItemStateChoice
     * @Assert\Type(type="string")
     * @Assert\Length(min=0, max=128)
     * @Assert\NotBlank
     *
     * @Serializer\Expose
     */
    protected ?string $state = null;

    public function setState(?string $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }
}
