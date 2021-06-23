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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Klipper\Component\Model\Traits\LabelableTrait;
use Klipper\Component\Model\Traits\OrganizationalRequiredTrait;
use Klipper\Component\Model\Traits\TimestampableTrait;
use Klipper\Component\Model\Traits\UserTrackableTrait;
use Klipper\Module\RepairBundle\Model\RepairBreakdownInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Audit Workflow model.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 *
 * @Serializer\ExclusionPolicy("all")
 */
abstract class AbstractAuditWorkflow implements AuditWorkflowInterface
{
    use LabelableTrait;
    use OrganizationalRequiredTrait;
    use TimestampableTrait;
    use UserTrackableTrait;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\Type(type="string")
     * @Assert\Length(max=255)
     *
     * @Serializer\Expose
     */
    protected ?string $description = null;

    /**
     * @var null|Collection|RepairBreakdownInterface[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Klipper\Module\BuybackBundle\Model\AuditWorkflowStepInterface",
     *     mappedBy="auditWorkflow",
     *     fetch="EXTRA_LAZY",
     *     cascade={"persist", "remove"}
     * )
     *
     * @Serializer\Expose
     * @Serializer\ReadOnly
     * @Serializer\MaxDepth(3)
     * @Serializer\Groups({"ViewsDetails", "View"})
     */
    protected ?Collection $steps = null;

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getSteps(): Collection
    {
        return $this->steps ?: $this->steps = new ArrayCollection();
    }
}
