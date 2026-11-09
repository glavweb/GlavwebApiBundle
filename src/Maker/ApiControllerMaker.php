<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Maker;

use ReflectionClass;
use RuntimeException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Persistence\ManagerRegistry;
use Glavweb\ApiBundle\Maker\DTO\ControllerMethodDTO;
use Glavweb\ApiBundle\Maker\Helper\ApiControllerHelper;
use Glavweb\ApiBundle\Maker\Helper\Helper;
use Glavweb\DataSchemaBundle\GlavwebDataSchemaBundle;
use Glavweb\UploaderBundle\Mapping\MetadataReader;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class ApiControllerMaker.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class ApiControllerMaker extends AbstractMaker
{
    private EntityManager $entityManager;

    private MetadataReader $metadataReader;

    private ApiControllerHelper $helper;

    private FileManager $fileManager;

    /**
     * ApiControllerMaker constructor.
     */
    public function __construct(
        ManagerRegistry $doctrine,
        MetadataReader $metadataReader,
        ApiControllerHelper $helper,
        FileManager $fileManager,
    ) {
        /** @var EntityManager $entityManager */
        $entityManager = $doctrine->getManager();
        $this->entityManager = $entityManager;
        $this->metadataReader = $metadataReader;
        $this->helper = $helper;
        $this->fileManager = $fileManager;
    }

    public static function getCommandName(): string
    {
        return 'make:api-controller';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setDescription('Creates a new api controller class')
            ->addArgument('model', InputArgument::REQUIRED, 'The name of the model class (e.g. <fg=yellow>Article</>)')
            ->addOption('list')
            ->addOption('view')
            ->addOption('author', null, InputOption::VALUE_OPTIONAL, 'The author of class')
            ->addOption('author-email', null, InputOption::VALUE_OPTIONAL, 'The author e-mail of class')
        ;
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(
            GlavwebDataSchemaBundle::class,
            'GlavwebDataSchemaBundle'
        );
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $modelArgument = (string) $input->getArgument('model');

        $modelClass = $generator->createClassNameDetails(
            $modelArgument,
            'Entity\\',
            ''
        );

        // Path mirrors Entity: Task\Task → Controller\Api\Task\TaskController
        $controllerClass = $generator->createClassNameDetails(
            $modelArgument,
            'Controller\\Api\\',
            'Controller'
        );
        $controllerFullName = $controllerClass->getFullName();

        if ($this->isClassExists($controllerFullName)) {
            $controllerFullName .= 'Generated';

            if ($this->isClassExists($controllerFullName)) {
                $path = $this->fileManager->getRelativePathForFutureClass($controllerFullName);
                if (null !== $path) {
                    unlink($path);
                }
            }
        }

        $fields = $this->getFields($modelClass->getFullName());
        $uploadableFields = $this->getUploadableFields($modelClass->getFullName());
        $enumFields = $this->getEnumFields($modelClass->getFullName());
        $associations = $this->getAssociations($modelClass->getFullName(), $generator);

        $methods = [];

        $queryHandler = null;
        if ($input->getOption('list') || $input->getOption('view')) {
            $queryHandler = $generator->createClassNameDetails(
                $this->getEntityQueryHandlerClassNameByModelName($modelArgument),
                'Query\\',
                'QueryHandler'
            );
        }

        $entitiesQueryClass = null;
        if ($input->getOption('list')) {
            if (null === $queryHandler) {
                throw new \LogicException('Не удалось создать QueryHandler для листинга');
            }
            $entitiesQueryClass = $generator->createClassNameDetails(
                $this->getEntitiesQueryClassNameByModelName($modelArgument),
                'Query\\',
                ''
            );

            $methods['list'] = $this->getListMethodData($queryHandler, $entitiesQueryClass);
        }

        $entityQueryClass = null;
        if ($input->getOption('view')) {
            if (null === $queryHandler) {
                throw new \LogicException('Не удалось создать QueryHandler для view');
            }
            $entityQueryClass = $generator->createClassNameDetails(
                $this->getEntityQueryClassNameByModelName($modelArgument),
                'Query\\',
                ''
            );

            $methods['view'] = $this->getViewMethodData($modelClass, $queryHandler, $entityQueryClass);
        }

        $namespaces = $this->getNamespaces($methods);

        $generator->generateClass(
            $controllerFullName,
            __DIR__.'/../../templates/skeleton/api/controller/class.tpl.php',
            [
                'methods' => $methods,
                'namespaces' => $namespaces,
                'modelClass' => $modelClass,
                'queryHandler' => $queryHandler,
                'entityQueryClass' => $entityQueryClass,
                'entitiesQueryClass' => $entitiesQueryClass,
                'helper' => $this->helper,
                'fields' => $fields,
                'uploadableFields' => $uploadableFields,
                'enumFields' => $enumFields,
                'associations' => $associations,
                'author' => $input->getOption('author'),
                'authorEmail' => $input->getOption('author-email'),
            ]
        );

        $generator->writeChanges();
        $this->writeSuccessMessage($io);
    }

    /**
     * @throws MappingException
     */
    private function getFields(string $modelClass): array
    {
        /** @var class-string<object> $modelClass */
        $metadata = $this->entityManager->getClassMetadata($modelClass);

        $fields = [];
        $fieldNames = $metadata->getFieldNames();
        foreach ($fieldNames as $fieldName) {
            $fieldMapping = $metadata->getFieldMapping($fieldName);
            $type = $fieldMapping['type'];
            $fields[$fieldName] = ['type' => $type];
        }

        return $fields;
    }

    /**
     * @throws MappingException
     */
    private function getEnumFields(string $modelClass): array
    {
        /** @var class-string<object> $modelClass */
        $metadata = $this->entityManager->getClassMetadata($modelClass);

        $enumFields = [];
        $fieldNames = $metadata->getFieldNames();
        foreach ($fieldNames as $fieldName) {
            $fieldMapping = $metadata->getFieldMapping($fieldName);
            $enumClass = Helper::enumClassFromFieldMapping($fieldMapping);
            if ($enumClass) {
                $enumFields[$fieldName] = [
                    'class' => $enumClass,
                    'basename' => (new ReflectionClass($enumClass))->getShortName(),
                ];
            }
        }

        return $enumFields;
    }

    private function getUploadableFields(string $modelClass): array
    {
        $uploadableFields = [];
        if ($this->metadataReader->isUploadable($modelClass)) {
            $uploadableFields = $this->metadataReader->getUploadableFields($modelClass);
        }

        return $uploadableFields;
    }

    private function getAssociations(string $modelClass, Generator $generator): array
    {
        /** @var class-string<object> $modelClass */
        $metadata = $this->entityManager->getClassMetadata($modelClass);

        $associationFields = [];
        $associationMappings = $metadata->getAssociationMappings();
        foreach ($associationMappings as $associationMapping) {
            $fieldName = $associationMapping['fieldName'];
            $type = $associationMapping['type'];
            $mappedBy = $associationMapping['mappedBy'];
            $inversedBy = $associationMapping['inversedBy'];
            $isOwningSide = $associationMapping['isOwningSide'];
            $otherSideName = $isOwningSide ? $inversedBy : $mappedBy;

            $isValidate =
                in_array($type, [ClassMetadata::ONE_TO_MANY, ClassMetadata::MANY_TO_MANY])
                && $otherSideName
            ;

            if (!$isValidate) {
                continue;
            }

            $associationModelClass = $generator->createClassNameDetails(
                $this->removePrefix($associationMapping['targetEntity'], $this->helper->getEntityNamespace()),
                'Entity\\',
                ''
            );

            $fields = $this->getFields($associationModelClass->getFullName());
            $uploadableFields = $this->getUploadableFields($associationModelClass->getFullName());

            $associationFields[$fieldName] = [
                'otherSideName' => $otherSideName,
                'modelClass' => $associationModelClass,
                'type' => $type,
                'fields' => $fields,
                'uploadableFields' => $uploadableFields,
            ];
        }

        return $associationFields;
    }

    private function getListMethodData(
        ClassNameDetails $queryHandler,
        ClassNameDetails $entitiesQueryClass,
    ): ControllerMethodDTO {
        return (new ControllerMethodDTO())
            ->setTemplate('list.tpl.php')
            ->setNamespaces([
                'Glavweb\ApiBundle\Scope\ScopeFetcherInterface',
                'Glavweb\ApiBundle\Response\ResultSetResponse',
                'Glavweb\ApiBundle\Attribute\Scope',
                'Glavweb\ApiBundle\Attribute\Input',
                'Glavweb\ApiBundle\Attribute\Output',
                'Glavweb\ApiBundle\Util\Populator',
                'Glavweb\ExtraBundle\Http\RequestBag',
                'Symfony\Component\HttpFoundation\Request',
                'Symfony\Component\Security\Http\Attribute\IsGranted',
                $queryHandler->getFullName(),
                $entitiesQueryClass->getFullName(),
            ])
        ;
    }

    private function getViewMethodData(
        ClassNameDetails $modelClass,
        ClassNameDetails $queryHandler,
        ClassNameDetails $entityQueryClass,
    ): ControllerMethodDTO {
        return (new ControllerMethodDTO())
            ->setTemplate('view.tpl.php')
            ->setNamespaces([
                'Glavweb\ApiBundle\Scope\ScopeFetcherInterface',
                'Glavweb\ApiBundle\Attribute\Scope',
                'Glavweb\ApiBundle\Attribute\Input',
                'Glavweb\ApiBundle\Attribute\Output',
                'Glavweb\ExtraBundle\Http\RequestBag',
                'Symfony\Component\HttpFoundation\Request',
                'Symfony\Component\HttpFoundation\JsonResponse',
                'Symfony\Component\Security\Http\Attribute\IsGranted',
                $modelClass->getFullName(),
                $queryHandler->getFullName(),
                $entityQueryClass->getFullName(),
            ])
        ;
    }

    private function isClassExists(string $controllerFullName): bool
    {
        $path = $this->fileManager->getRelativePathForFutureClass($controllerFullName);

        return $this->fileManager->fileExists($path);
    }

    /**
     * @param ControllerMethodDTO[] $methods
     */
    private function getNamespaces(array $methods): array
    {
        $namespaces = [];

        foreach ($methods as $method) {
            $namespaces = array_merge($namespaces, $method->getNamespaces());
        }

        $namespaces = array_unique($namespaces);

        return $namespaces;
    }

    private function removePrefix(string $name, string $prefix): string
    {
        if (0 !== strrpos($name, $prefix)) {
            throw new RuntimeException('Prefix "'.$prefix.'" not found in "'.$name.'".');
        }

        return substr($name, strlen($prefix));
    }

    private function getEntityQueryClassNameByModelName(string $modelArgument): string
    {
        $parts = explode('\\', $modelArgument);
        $parts[] = 'Get'.$parts[array_key_last($parts)].'Query';
        $queryName = implode('\\', $parts);

        return $queryName;
    }

    private function getEntityQueryHandlerClassNameByModelName(string $modelArgument): string
    {
        $parts = explode('\\', $modelArgument);
        $parts[] = $parts[array_key_last($parts)].'QueryHandler';
        $queryName = implode('\\', $parts);

        return $queryName;
    }

    private function getEntitiesQueryClassNameByModelName(string $modelArgument): string
    {
        $parts = explode('\\', $modelArgument);
        $parts[] = 'Get'.$this->helper->plural($parts[array_key_last($parts)]).'Query';
        $queryName = implode('\\', $parts);

        return $queryName;
    }
}
