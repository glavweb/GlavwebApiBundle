<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\ApiDoc;

use ReflectionMethod;
use ReflectionClass;
use ReflectionProperty;
use Glavweb\ApiBundle\Attribute\AttributeReader;
use Glavweb\ApiBundle\Attribute\FromRequest;
use Glavweb\ApiBundle\Attribute\Input;
use Glavweb\ApiBundle\Attribute\SystemRequestField;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberInterface;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberTrait;
use OpenApi\Annotations as OA;
use OpenApi\Generator;
use Symfony\Component\Routing\Route;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Описывает параметры из #[Input] (Query/Command class) для OpenAPI 3.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
final class InputDescriber implements RouteDescriberInterface
{
    use RouteDescriberTrait;

    public function __construct(
        private readonly AttributeReader $attributeReader,
    ) {
    }

    public function describe(OA\OpenApi $api, Route $route, ReflectionMethod $reflectionMethod): void
    {
        $annotation = $this->attributeReader->getMethodAttribute($reflectionMethod, Input::class);
        if (!$annotation instanceof Input) {
            return;
        }

        /** @var class-string $class */
        $class = $annotation->class;
        $reflectionClass = new ReflectionClass($class);
        $in = $annotation->in ?: 'formData';

        foreach ($this->getOperations($api, $route) as $operation) {
            foreach ($reflectionClass->getProperties() as $property) {
                if (!$this->attributeReader->getPropertyAttribute($property, FromRequest::class)) {
                    continue;
                }

                $systemRequestField = $this->attributeReader->getPropertyAttribute($property, SystemRequestField::class);
                $propertyName = $systemRequestField instanceof SystemRequestField && $systemRequestField->systemName
                    ? $systemRequestField->systemName
                    : $property->getName();

                $type = $this->getTypeByProperty($property);
                $description = $this->getDescription($property);
                $required = false;
                $minLength = null;
                $maxLength = null;
                $pattern = null;

                if ($this->attributeReader->getPropertyAttribute($property, Assert\NotBlank::class)) {
                    $required = true;
                }
                $length = $this->attributeReader->getPropertyAttribute($property, Assert\Length::class);
                if ($length instanceof Assert\Length) {
                    if (null !== $length->min) {
                        $minLength = (int) $length->min;
                    }
                    if (null !== $length->max) {
                        $maxLength = (int) $length->max;
                    }
                }
                $regex = $this->attributeReader->getPropertyAttribute($property, Assert\Regex::class);
                if ($regex instanceof Assert\Regex) {
                    $pattern = $regex->getHtmlPattern();
                }

                if ('query' === $in || 'header' === $in || 'path' === $in || 'cookie' === $in) {
                    $parameter = Util::getOperationParameter($operation, $propertyName, $in);
                    $parameter->required = $required;
                    if (Generator::UNDEFINED === $parameter->description && null !== $description) {
                        $parameter->description = $description;
                    }

                    $schema = Util::getChild($parameter, OA\Schema::class);
                    if (Generator::UNDEFINED === $schema->type && null !== $type) {
                        $schema->type = $type;
                    }
                    if ('array' === $type || 'array' === $schema->type) {
                        if (Generator::UNDEFINED === $schema->items || null === $schema->items) {
                            $schema->items = new OA\Items([
                                'type' => 'string',
                                '_context' => Util::createWeakContext($schema->_context),
                            ]);
                        }
                    }
                    if (null !== $minLength) {
                        $schema->minLength = $minLength;
                    }
                    if (null !== $maxLength) {
                        $schema->maxLength = $maxLength;
                    }
                    if (null !== $pattern) {
                        $schema->pattern = $pattern;
                    }

                    continue;
                }

                // formData / body → requestBody (OpenAPI 3)
                $requestBody = Util::getChild($operation, OA\RequestBody::class);
                $contentSchema = $this->getFormContentSchema($requestBody);
                $schema = Util::getProperty($contentSchema, $propertyName);

                if ($required) {
                    $requiredParameters = \is_array($contentSchema->required) ? $contentSchema->required : [];
                    $requiredParameters[] = $propertyName;
                    $contentSchema->required = array_values(array_unique($requiredParameters));
                }

                if (Generator::UNDEFINED === $schema->type && null !== $type) {
                    $schema->type = $type;
                }
                if ('array' === $type || 'array' === $schema->type) {
                    if (Generator::UNDEFINED === $schema->items || null === $schema->items) {
                        $schema->items = new OA\Items([
                            'type' => 'string',
                            '_context' => Util::createWeakContext($schema->_context),
                        ]);
                    }
                }
                if (Generator::UNDEFINED === $schema->description && null !== $description) {
                    $schema->description = $description;
                }
                if (null !== $minLength) {
                    $schema->minLength = $minLength;
                }
                if (null !== $maxLength) {
                    $schema->maxLength = $maxLength;
                }
                if (null !== $pattern) {
                    $schema->pattern = $pattern;
                }
            }
        }
    }

    private function getFormContentSchema(OA\RequestBody $requestBody): OA\Schema
    {
        $contentType = 'application/x-www-form-urlencoded';
        $requestBody->content = Generator::UNDEFINED !== $requestBody->content ? $requestBody->content : [];

        /** @var array<string, OA\MediaType> $content */
        $content = $requestBody->content;
        if (!isset($content[$contentType])) {
            $content[$contentType] = new OA\MediaType([
                'mediaType' => $contentType,
                '_context' => Util::createWeakContext($requestBody->_context),
            ]);
            $schema = Util::getChild($content[$contentType], OA\Schema::class);
            $schema->type = 'object';
        }
        $requestBody->content = $content;

        return Util::getChild($content[$contentType], OA\Schema::class);
    }

    private function getTypeByProperty(ReflectionProperty $property): ?string
    {
        $allowedTypes = [
            'string' => 'string',
            'integer' => 'integer',
            'int' => 'integer',
            'array' => 'array',
        ];

        $docComment = $property->getDocComment();
        if (false === $docComment || !preg_match('/@var\s+([^\s]+)/', $docComment, $matches)) {
            return null;
        }

        return $allowedTypes[$matches[1]] ?? null;
    }

    private function getDescription(ReflectionProperty $property): ?string
    {
        $docComment = $property->getDocComment();
        if (false === $docComment) {
            return null;
        }

        $lines = preg_split('/\R/', $docComment) ?: [];
        foreach ($lines as $line) {
            $line = trim($line, " \t*/");
            if ('' === $line || str_starts_with($line, '@') || str_starts_with($line, '/')) {
                continue;
            }

            return $line;
        }

        return null;
    }
}
