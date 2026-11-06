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

use TypeError;
use Glavweb\ApiBundle\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * {@inheritdoc}
 *
 * @author Ener-Getick <egetick@gmail.Com>
 */
abstract class AbstractScalarParam extends AbstractParam
{
    public mixed $requirements = null;

    public bool $map = false;

    public bool $allowBlank = true;

    public function getConstraints(): array
    {
        $constraints = parent::getConstraints();

        if ($this->requirements instanceof Constraint) {
            $constraints[] = $this->requirements;
        } elseif (\is_scalar($this->requirements)) {
            $constraints[] = new Regex(
                '#^(?:'.$this->requirements.')$#xsu',
                sprintf(
                    'Parameter \'%s\' value, does not match requirements \'%s\'',
                    $this->getName(),
                    $this->requirements
                ),
            );
        } elseif (\is_array($this->requirements) && isset($this->requirements['rule'], $this->requirements['error_message'])) {
            $constraints[] = new Regex(
                '#^(?:'.$this->requirements['rule'].')$#xsu',
                $this->requirements['error_message'],
            );
        } elseif (\is_array($this->requirements)) {
            foreach ($this->requirements as $index => $requirement) {
                if ($requirement instanceof Constraint) {
                    $constraints[] = $requirement;
                } else {
                    throw new TypeError(sprintf('Expected the requirements to be an array of %s instances but got %s at position %d.', Constraint::class, \is_object($requirement) ? $requirement::class : \gettype($requirement), $index));
                }
            }
        }

        if (false === $this->allowBlank) {
            $notBlank = new NotBlank();
            $notBlank->allowNull = $this->nullable;
            $constraints[] = $notBlank;
        }

        if ($this->map) {
            if ([] !== $constraints) {
                $constraints = [
                    new All($constraints),
                ];
            }

            if (false === $this->nullable) {
                $constraints[] = new NotNull();
            }
        }

        return $constraints;
    }
}
