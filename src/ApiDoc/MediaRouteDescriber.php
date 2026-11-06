<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\ApiDoc;

use ReflectionMethod;
use Glavweb\ApiBundle\ApiDoc\Model\MediaStructure;
use Nelmio\ApiDocBundle\Describer\ModelRegistryAwareInterface;
use Nelmio\ApiDocBundle\Describer\ModelRegistryAwareTrait;
use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\OpenApiPhp\Util;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberInterface;
use Nelmio\ApiDocBundle\RouteDescriber\RouteDescriberTrait;
use OpenApi\Annotations\OpenApi;
use OpenApi\Annotations\Operation;
use Symfony\Component\Routing\Route;
use Symfony\Component\TypeInfo\Type\ObjectType;

/**
 * OpenAPI для upload-роутов GlavwebUploaderBundle (подключается только если бандл установлен).
 *
 * @author Sergey Zvyagintsev <nitron.ru@gmail.com>
 */
class MediaRouteDescriber implements RouteDescriberInterface, ModelRegistryAwareInterface
{
    use ModelRegistryAwareTrait;
    use RouteDescriberTrait;

    private const string UPLOAD_CONTROLLER = 'Glavweb\UploaderBundle\Controller\UploadController';

    /**
     * @param array<string, mixed> $uploaderConfig
     */
    public function __construct(private readonly array $uploaderConfig)
    {
    }

    public function describe(OpenApi $api, Route $route, ReflectionMethod $reflectionMethod): void
    {
        if (self::UPLOAD_CONTROLLER !== $reflectionMethod->getDeclaringClass()->getName()) {
            return;
        }

        foreach ($this->getOperations($api, $route) as $operation) {
            if ('uploadAction' === $reflectionMethod->getName()) {
                $this->describeUploadAction($operation);
            }

            if ('uploadLinkAction' === $reflectionMethod->getName()) {
                $this->describeUploadLinkAction($operation);
            }

            if ('deleteAction' === $reflectionMethod->getName()) {
                $this->describeDeleteAction($operation);
            }

            if ('editAction' === $reflectionMethod->getName()) {
                $this->describeEditAction($operation);
            }

            Util::merge($operation, [
                'tags' => ['Media'],
            ]);
        }
    }

    private function describeUploadAction(Operation $operation): void
    {
        $chunkUploadConfig = $this->uploaderConfig['chunk_upload'] ?? null;
        $chunkUploadProperties = [];

        if ($chunkUploadConfig) {
            $chunkUploadProperties = [
                $chunkUploadConfig['file_id_request_parameter'] => ['type' => 'string'],
                $chunkUploadConfig['current_index_request_parameter'] => ['type' => 'string'],
                $chunkUploadConfig['total_count_request_parameter'] => ['type' => 'string'],
            ];
        }

        Util::merge($operation, [
            'requestBody' => [
                'required' => true,
                'content' => [
                    'multipart/form-data' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => array_merge([
                                'files' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'file',
                                        'format' => 'binary',
                                    ],
                                ],
                                'request_id' => ['type' => 'string'],
                                'thumbnail_filter' => ['type' => 'string'],
                            ], $chunkUploadProperties),
                            'required' => ['files'],
                        ],
                    ],
                ],
            ],
            'responses' => [
                'default' => [
                    'description' => '',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => $this->getMediaStructureModelRef(),
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    private function describeUploadLinkAction(Operation $operation): void
    {
        Util::merge($operation, [
            'tags' => ['Media'],
            'requestBody' => [
                'required' => true,
                'content' => [
                    'multipart/form-data' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'file_link' => ['type' => 'string'],
                                'request_id' => ['type' => 'string'],
                                'thumbnail_filter' => ['type' => 'string'],
                            ],
                            'required' => ['file_link'],
                        ],
                    ],
                ],
            ],
            'responses' => [
                'default' => [
                    'description' => '',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => $this->getMediaStructureModelRef(),
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    private function describeDeleteAction(Operation $operation): void
    {
        Util::merge($operation, [
            'tags' => ['Media'],
            'requestBody' => [
                'required' => true,
                'content' => [
                    'multipart/form-data' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => ['type' => 'string', 'description' => 'Secured Id'],
                                'request_id' => ['type' => 'string'],
                            ],
                            'required' => ['id', 'request_id'],
                        ],
                    ],
                ],
            ],
        ]);
    }

    private function describeEditAction(Operation $operation): void
    {
        Util::merge($operation, [
            'tags' => ['Media'],
            'requestBody' => [
                'required' => true,
                'content' => [
                    'multipart/form-data' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'id' => ['type' => 'string', 'description' => 'Secured Id'],
                                'request_id' => ['type' => 'string'],
                                'name' => ['type' => 'string'],
                                'description' => ['type' => 'string'],
                                'crop_data' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'x' => ['type' => 'integer'],
                                        'y' => ['type' => 'integer'],
                                        'width' => ['type' => 'integer'],
                                        'height' => ['type' => 'integer'],
                                    ],
                                ],
                                'soft_edit' => ['type' => 'boolean', 'default' => false],
                            ],
                            'required' => ['id'],
                        ],
                    ],
                ],
            ],
            'responses' => [
                'default' => [
                    'description' => '',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'success' => ['type' => 'boolean'],
                                    'media' => [
                                        '$ref' => $this->getMediaStructureModelRef(),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    private function getMediaStructureModelRef(): string
    {
        return $this->modelRegistry->register(new Model(new ObjectType(MediaStructure::class)));
    }
}
