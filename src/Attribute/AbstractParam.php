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

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * {@inheritdoc}
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 * @author Boris Guéry <guery.b@gmail.com>
 * @author Ener-Getick <egetick@gmail.com>
 */
abstract class AbstractParam implements ParamInterface
{
    public ?string $name = null;

    public ?string $key = null;

    public mixed $default = null;

    public string $description = '';

    public bool $strict = false;

    public bool $nullable = false;

    public array $incompatibles = [];

    public function getName(): string
    {
        return $this->name ?? '';
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getIncompatibilities(): array
    {
        return $this->incompatibles;
    }

    /**
     * @return Constraint[]
     */
    public function getConstraints(): array
    {
        $constraints = [];
        if (!$this->nullable) {
            $constraints[] = new NotNull();
        }

        return $constraints;
    }

    public function isStrict(): bool
    {
        return $this->strict;
    }

    protected function getKey(): string
    {
        return $this->key ?: $this->name;
    }
}
