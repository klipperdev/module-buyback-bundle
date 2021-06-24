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

use JMS\Serializer\Annotation as Serializer;
use Klipper\Component\Model\Traits\EnableTrait;
use Klipper\Component\Model\Traits\LabelableTrait;
use Klipper\Component\Model\Traits\OrganizationalRequiredTrait;
use Klipper\Component\Model\Traits\SortableTrait;
use Klipper\Component\Model\Traits\TimestampableTrait;
use Klipper\Component\Model\Traits\UserTrackableTrait;

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
    use OrganizationalRequiredTrait;
    use SortableTrait;
    use TimestampableTrait;
    use UserTrackableTrait;
}
