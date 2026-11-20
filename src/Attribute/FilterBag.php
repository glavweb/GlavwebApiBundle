<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Attribute;

use Attribute;

/**
 * Свойство заполняется из query `filter[поле]` / `filter[поле][оператор]`.
 *
 * @author nilov <nilov@glavweb.ru>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class FilterBag
{
}
