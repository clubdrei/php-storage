<?php
declare(strict_types=1);

namespace C3\PhpStorage\Model;

use MyCLabs\Enum\Enum;

/**
 * @method static ProcessFileStatusEnum OK()
 * @method static ProcessFileStatusEnum ERROR()
 */
class ProcessFileStatusEnum extends Enum
{
    public const OK = 1;
    public const ERROR = 2;
}
