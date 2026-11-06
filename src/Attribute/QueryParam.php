<?php

declare(strict_types=1);

/*
 * This file is part of the FOSRestBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\ApiBundle\Attribute;

use Attribute;
use Symfony\Component\HttpFoundation\Request;

/**
 * Represents a parameter that must be present in GET data.
 *
 * @author Alexander <iam.asm89@gmail.com>
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class QueryParam extends AbstractScalarParam
{
    public function __construct(
        ?string $name = null,
        mixed $requirements = null,
        mixed $default = null,
        string $description = '',
        array $incompatibles = [],
        ?string $key = null,
        bool $strict = false,
        bool $map = false,
        bool $nullable = false,
        bool $allowBlank = true,
    ) {
        $this->name = $name;
        $this->requirements = $requirements;
        $this->default = $default;
        $this->description = $description;
        $this->incompatibles = $incompatibles;
        $this->key = $key;
        $this->strict = $strict;
        $this->map = $map;
        $this->nullable = $nullable;
        $this->allowBlank = $allowBlank;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue(Request $request, $default = null): string|int|float|bool|null
    {
        return $request->query->get($this->getKey(), $default);
    }
}
