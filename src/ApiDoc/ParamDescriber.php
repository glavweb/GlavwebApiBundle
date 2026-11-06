<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\ApiDoc;

use ReflectionMethod;
use ReflectionClass;
use Glavweb\ApiBundle\Attribute\AttributeReader;
use Glavweb\ApiBundle\Attribute\QueryParam;
use Glavweb\ApiBundle\Attribute\RequestParam;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberInterface;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberTrait;
use OpenApi\Annotations as OA;
use OpenApi\Generator;
use Symfony\Component\Routing\Route;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
final class ParamDescriber implements RouteDescriberInterface
{
    use RouteDescriberTrait;

    public function __construct(
        private readonly AttributeReader $attributeReader,
    ) {
    }

    public function describe(OA\OpenApi $api, Route $route, ReflectionMethod $reflectionMethod): void
    {
        $annotations = array_merge(
            $this->attributeReader->getMethodAttributes($reflectionMethod, QueryParam::class),
            $this->attributeReader->getMethodAttributes($reflectionMethod, RequestParam::class),
        );

        if ([] === $annotations) {
            return;
        }

        foreach ($this->getOperations($api, $route) as $operation) {
            foreach ($annotations as $annotation) {
                $in = $annotation instanceof QueryParam ? 'query' : 'formData';
                $name = $annotation->getName();

                if ('query' === $in) {
                    $parameter = Util::getOperationParameter($operation, $name, 'query');
                    $parameter->required = !$annotation->nullable && $annotation->strict;

                    if (Generator::UNDEFINED === $parameter->description) {
                        $parameter->description = $annotation->description;
                    }

                    $schema = Util::getChild($parameter, OA\Schema::class);
                    if (Generator::UNDEFINED === $schema->type) {
                        $schema->type = $annotation->map ? 'array' : 'string';
                    }
                    if (($annotation->map || 'array' === $schema->type)
                        && (Generator::UNDEFINED === $schema->items || null === $schema->items)
                    ) {
                        $schema->items = new OA\Items([
                            'type' => 'string',
                            '_context' => Util::createWeakContext($schema->_context),
                        ]);
                    }
                    if (Generator::UNDEFINED === $schema->default) {
                        $schema->default = $annotation->getDefault();
                    }

                    $normalizedRequirements = $this->normalizeRequirements($annotation->requirements);
                    if (null !== $normalizedRequirements) {
                        $schema->pattern = $normalizedRequirements;
                    }

                    continue;
                }

                $requestBody = Util::getChild($operation, OA\RequestBody::class);
                $contentSchema = $this->getFormContentSchema($requestBody);
                $schema = Util::getProperty($contentSchema, $name);

                if (!$annotation->nullable && $annotation->strict) {
                    $requiredParameters = \is_array($contentSchema->required) ? $contentSchema->required : [];
                    $requiredParameters[] = $name;
                    $contentSchema->required = array_values(array_unique($requiredParameters));
                }

                if (Generator::UNDEFINED === $schema->type) {
                    $schema->type = $annotation->map ? 'array' : 'string';
                }
                if (($annotation->map || 'array' === $schema->type)
                    && (Generator::UNDEFINED === $schema->items || null === $schema->items)
                ) {
                    $schema->items = new OA\Items([
                        'type' => 'string',
                        '_context' => Util::createWeakContext($schema->_context),
                    ]);
                }
                if (Generator::UNDEFINED === $schema->description) {
                    $schema->description = $annotation->description;
                }
                if (Generator::UNDEFINED === $schema->default) {
                    $schema->default = $annotation->getDefault();
                }

                $normalizedRequirements = $this->normalizeRequirements($annotation->requirements);
                if (null !== $normalizedRequirements) {
                    $schema->pattern = $normalizedRequirements;
                }
            }

            $this->removeAuthHeaderParameter($operation);
        }
    }

    private function removeAuthHeaderParameter(OA\Operation $operation): void
    {
        if (!\is_array($operation->parameters)) {
            return;
        }

        $operation->parameters = array_values(array_filter(
            $operation->parameters,
            static function ($parameter): bool {
                return !($parameter instanceof OA\Parameter
                    && 'X-User-Token' === $parameter->name
                    && 'header' === $parameter->in);
            }
        ));
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

    private function normalizeRequirements(mixed $requirements): ?string
    {
        if (\is_array($requirements) && isset($requirements['rule'])) {
            return (string) $requirements['rule'];
        }
        if (\is_string($requirements)) {
            return $requirements;
        }
        if ($requirements instanceof Constraint) {
            if ($requirements instanceof Regex) {
                return $requirements->getHtmlPattern();
            }

            return (new ReflectionClass($requirements))->getShortName();
        }

        return null;
    }
}
