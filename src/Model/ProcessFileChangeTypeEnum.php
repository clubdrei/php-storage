<?php
declare(strict_types=1);

namespace C3\PhpStorage\Model;

use MyCLabs\Enum\Enum;

/**
 * @method static ProcessFileChangeTypeEnum ADD()
 * @method static ProcessFileChangeTypeEnum CHANGE()
 * @method static ProcessFileChangeTypeEnum DELETE()
 */
class ProcessFileChangeTypeEnum extends Enum
{
    public const ADD = 1;
    public const CHANGE = 2;
    public const DELETE = 3;
}
