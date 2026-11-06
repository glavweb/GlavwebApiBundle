<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Exception;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

/**
 * Class EntityNotFoundException.
 *
 * @author Sergey Zvyagintsev <nitron.ru@gmail.com>
 */
#[WithHttpStatus(Response::HTTP_NOT_FOUND)]
class EntityNotFoundException extends Exception
{
    public static function fromClassNameAndIdentifier(string $className, array $id): self
    {
        $ids = [];

        foreach ($id as $key => $value) {
            $ids[] = $key.'('.$value.')';
        }

        return new self(
            "Entity of type '".$className."'".($ids ? ' for IDs '.implode(', ', $ids) : '').' was not found',
        );
    }
}
