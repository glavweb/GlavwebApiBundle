<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\ApiDoc;

use ReflectionMethod;
use Glavweb\ApiBundle\Attribute\AttributeReader;
use Glavweb\ApiBundle\Attribute\ErrorCodesDescription;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberInterface;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberTrait;
use OpenApi\Annotations as OA;
use OpenApi\Generator;
use Symfony\Component\Routing\Route;

/**
 * Добавляет описание кодов ошибок из #[ErrorCodesDescription].
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
final class ErrorCodesDescriber implements RouteDescriberInterface
{
    use RouteDescriberTrait;

    public function __construct(
        private readonly AttributeReader $attributeReader,
    ) {
    }

    public function describe(OA\OpenApi $api, Route $route, ReflectionMethod $reflectionMethod): void
    {
        $annotation = $this->attributeReader->getMethodAttribute(
            $reflectionMethod,
            ErrorCodesDescription::class
        );
        if (!$annotation instanceof ErrorCodesDescription) {
            return;
        }

        $currentDescription = $this->getCurrentDescription($reflectionMethod->getDocComment() ?: '');
        $errorCodesDescription = $this->getErrorCodesDescription(
            $annotation->errorCodes,
            (array) $annotation->messages
        );
        $description = trim($currentDescription."\r\n".$errorCodesDescription);

        foreach ($this->getOperations($api, $route) as $operation) {
            if (Generator::UNDEFINED === $operation->description || null === $operation->description || '' === $operation->description) {
                $operation->description = $description;
            } else {
                $operation->description = rtrim((string) $operation->description)."\r\n".$errorCodesDescription;
            }
        }
    }

    private function getErrorCodesDescription(array $errorCodes, array $messages): string
    {
        $description = 'Коды ошибок: ';
        $description .= '<ul>';

        foreach ($errorCodes as $errorCode) {
            $description .= sprintf(
                '<li>(%s) %s</li>',
                $errorCode,
                $messages[$errorCode] ?? ' - '
            );
        }
        $description .= '</ul>';

        return $description;
    }

    private function getCurrentDescription(string $docComment): string
    {
        $lines = preg_split('/\R/', $docComment) ?: [];
        $parts = [];

        foreach ($lines as $line) {
            $line = preg_replace('/^\s*\*\s?/', '', $line) ?? $line;
            $line = trim($line);
            if ('' === $line || '/**' === $line || '*/' === $line || str_starts_with($line, '@')) {
                if ([] !== $parts && str_starts_with($line, '@')) {
                    break;
                }
                continue;
            }
            $parts[] = $line;
        }

        return implode("\n", $parts);
    }
}
