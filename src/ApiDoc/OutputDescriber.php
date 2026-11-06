<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\ApiDoc;

use ReflectionMethod;
use Glavweb\ApiBundle\Attribute\AttributeReader;
use Glavweb\ApiBundle\Attribute\Output;
use Glavweb\DatagridBundle\Factory\ORMDatagridFactory;
use Glavweb\DataSchemaBundle\DataSchema\DataSchemaFactory;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberInterface;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberTrait;
use OpenApi\Annotations as OA;
use OpenApi\Generator;
use Symfony\Component\Routing\Route;

/**
 * Описывает 200-ответ из #[Output] (dataSchema/scope) для OpenAPI 3.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
final class OutputDescriber implements RouteDescriberInterface
{
    use RouteDescriberTrait;

    public function __construct(
        private readonly AttributeReader $attributeReader,
        private readonly DataSchemaFactory $dataSchemaFactory,
    ) {
    }

    public function describe(OA\OpenApi $api, Route $route, ReflectionMethod $reflectionMethod): void
    {
        $annotation = $this->attributeReader->getMethodAttribute($reflectionMethod, Output::class);
        if (!$annotation instanceof Output || !$annotation->dataSchema) {
            return;
        }

        $dataSchema = $this->dataSchemaFactory->createDataSchema(
            $annotation->dataSchema,
            $annotation->scope,
            ORMDatagridFactory::QUERY_LANGUAGE
        );
        $configuration = $dataSchema->getConfiguration();

        foreach ($this->getOperations($api, $route) as $operation) {
            $response = $this->getOrCreateResponse($operation, 200, 'Запрос выполнен успешно.');

            $mediaType = 'application/json';
            $response->content = Generator::UNDEFINED !== $response->content ? $response->content : [];
            /** @var array<string, OA\MediaType> $content */
            $content = $response->content;
            if (!isset($content[$mediaType])) {
                $content[$mediaType] = new OA\MediaType([
                    'mediaType' => $mediaType,
                    '_context' => Util::createWeakContext($response->_context),
                ]);
            }
            $response->content = $content;

            $schema = Util::getChild($content[$mediaType], OA\Schema::class);
            if (Generator::UNDEFINED === $schema->type || null === $schema->type) {
                $schema->type = 'object';
            }
            $this->describeByConfiguration($configuration, $schema);
            $this->ensureArrayItems($schema);
        }
    }

    private function getOrCreateResponse(OA\Operation $operation, int|string $statusCode, string $description): OA\Response
    {
        if (\is_array($operation->responses)) {
            foreach ($operation->responses as $response) {
                if ($response instanceof OA\Response
                    && (string) $response->response === (string) $statusCode
                ) {
                    if (Generator::UNDEFINED === $response->description || null === $response->description || '' === $response->description) {
                        $response->description = $description;
                    }

                    return $response;
                }
            }
        }

        return Util::getCollectionItem($operation, OA\Response::class, [
            'response' => $statusCode,
            'description' => $description,
        ]);
    }

    private function describeByConfiguration(array $configuration, OA\Schema $schema): void
    {
        if (!isset($configuration['properties']) || !\is_array($configuration['properties'])) {
            return;
        }

        foreach ($configuration['properties'] as $name => $data) {
            if (!\is_array($data) || !empty($data['hidden'])) {
                continue;
            }

            // Ключ "properties" без type — это вложенная карта полей, не поле ответа.
            if ('properties' === $name && !isset($data['type']) && !isset($data['schema'])) {
                continue;
            }

            $property = Util::getProperty($schema, (string) $name);
            $hasProperties = isset($data['properties']) && \is_array($data['properties']);
            $type = isset($data['type']) ? $this->getType((string) $data['type']) : null;

            if (null !== $type && Generator::UNDEFINED === $property->type) {
                $property->type = $type;
            }

            if (!empty($data['description']) && Generator::UNDEFINED === $property->description) {
                $property->description = $data['description'];
            }

            if ('array' === $type || 'array' === $property->type) {
                if (Generator::UNDEFINED === $property->items || null === $property->items) {
                    $property->items = new OA\Items([
                        'type' => $hasProperties ? 'object' : 'string',
                        '_context' => Util::createWeakContext($property->_context),
                    ]);
                }
            }

            if (!$hasProperties) {
                continue;
            }

            if ('array' === $type || 'array' === $property->type) {
                $items = $property->items instanceof OA\Items
                    ? $property->items
                    : Util::getChild($property, OA\Items::class);
                $items->type = 'object';
                $this->describeByConfiguration($data, $items);
            } else {
                if (Generator::UNDEFINED === $property->type || null === $property->type) {
                    $property->type = 'object';
                }
                $this->describeByConfiguration($data, $property);
            }
        }
    }

    private function ensureArrayItems(OA\Schema $schema): void
    {
        if (\is_array($schema->properties)) {
            foreach ($schema->properties as $property) {
                if (!$property instanceof OA\Schema) {
                    continue;
                }
                if ('array' === $property->type
                    && (Generator::UNDEFINED === $property->items || null === $property->items)
                ) {
                    $property->items = new OA\Items([
                        'type' => 'string',
                        '_context' => Util::createWeakContext($property->_context),
                    ]);
                }
                if ($property->items instanceof OA\Schema) {
                    $this->ensureArrayItems($property->items);
                }
                $this->ensureArrayItems($property);
            }
        }

        if ('array' === $schema->type
            && (Generator::UNDEFINED === $schema->items || null === $schema->items)
        ) {
            $schema->items = new OA\Items([
                'type' => 'string',
                '_context' => Util::createWeakContext($schema->_context),
            ]);
        }
    }

    private function getType(string $inType): ?string
    {
        $allowedTypes = [
            'string' => 'string',
            'text' => 'string',
            'datetime' => 'string',
            'date' => 'string',
            'number' => 'number',
            'integer' => 'integer',
            'int' => 'integer',
            'boolean' => 'boolean',
            'array' => 'array',
            'collection' => 'array',
            'object' => 'object',
            'entity' => 'object',
        ];

        return $allowedTypes[$inType] ?? null;
    }
}
