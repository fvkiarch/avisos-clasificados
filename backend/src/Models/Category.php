<?php

declare(strict_types=1);

final class Category
{
    public const JOB = 'Laboral';

    public const PRODUCT = 'Producto';

    public const REAL_ESTATE = 'Inmobiliario';

    public const ALL = [self::JOB, self::PRODUCT, self::REAL_ESTATE];
}
