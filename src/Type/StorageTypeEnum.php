<?php
declare(strict_types=1);

namespace C3\PhpStorage\Type;

use MyCLabs\Enum\Enum;

/**
 * @method static StorageTypeEnum LOCAL()
 * @method static StorageTypeEnum WEB_DAV()
 */
class StorageTypeEnum extends Enum
{
    const LOCAL = 1;
    const WEB_DAV = 2;
}
