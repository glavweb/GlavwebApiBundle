<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Maker;

use Glavweb\ApiBundle\Maker\Helper\Helper;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Scaffolds CQRS queries + handler under Query/{Domain}/{Entity}/.
 *
 * @author nilov <nilov@glavweb.ru>
 */
class MakeQuery extends AbstractMaker
{
    public function __construct(
        private readonly Helper $helper,
    ) {
    }

    public static function getCommandName(): string
    {
        return 'make:query';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates CQRS query classes and handler (mirrors Entity path)';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setDescription(self::getCommandDescription())
            ->addArgument('model', InputArgument::REQUIRED, 'The model class (e.g. <fg=yellow>User\\\\User</> or <fg=yellow>Task\\\\Task</>)')
            ->addOption('author', null, InputOption::VALUE_OPTIONAL, 'The author of class')
            ->addOption('author-email', null, InputOption::VALUE_OPTIONAL, 'The author e-mail of class')
        ;
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $modelArgument = (string) $input->getArgument('model');

        $modelClass = $generator->createClassNameDetails(
            $modelArgument,
            'Entity\\',
            ''
        );

        $shortName = $modelClass->getShortName();
        $pluralShortName = $this->helper->plural($shortName);
        $varName = lcfirst($shortName);

        $entityQueryClass = $generator->createClassNameDetails(
            $this->nestedClassName($modelArgument, 'Get'.$shortName.'Query'),
            'Query\\',
            ''
        );
        $entitiesQueryClass = $generator->createClassNameDetails(
            $this->nestedClassName($modelArgument, 'Get'.$pluralShortName.'Query'),
            'Query\\',
            ''
        );
        $handlerClass = $generator->createClassNameDetails(
            $this->nestedClassName($modelArgument, $shortName.'QueryHandler'),
            'Query\\',
            ''
        );

        $dataSchema = $this->helper->getDataSchemaName($modelClass->getFullName());
        $listScope = $this->helper->getScopeName($modelClass->getFullName(), 'list');
        $viewScope = $this->helper->getScopeName($modelClass->getFullName(), 'view');

        $common = [
            'modelClass' => $modelClass,
            'shortName' => $shortName,
            'pluralShortName' => $pluralShortName,
            'varName' => $varName,
            'dataSchema' => $dataSchema,
            'listScope' => $listScope,
            'viewScope' => $viewScope,
            'author' => $input->getOption('author'),
            'authorEmail' => $input->getOption('author-email'),
        ];

        $generator->generateClass(
            $entityQueryClass->getFullName(),
            __DIR__.'/../../templates/skeleton/query/GetEntityQuery.tpl.php',
            $common + [
                'handlerClass' => $handlerClass,
                'handlerMethod' => 'get'.$shortName.'Data',
            ]
        );

        $generator->generateClass(
            $entitiesQueryClass->getFullName(),
            __DIR__.'/../../templates/skeleton/query/GetEntitiesQuery.tpl.php',
            $common + [
                'handlerClass' => $handlerClass,
                'handlerMethod' => 'get'.$pluralShortName.'ResultSet',
            ]
        );

        $generator->generateClass(
            $handlerClass->getFullName(),
            __DIR__.'/../../templates/skeleton/query/QueryHandler.tpl.php',
            $common + [
                'entityQueryClass' => $entityQueryClass,
                'entitiesQueryClass' => $entitiesQueryClass,
            ]
        );

        $generator->writeChanges();
        $this->writeSuccessMessage($io);

        $io->text([
            'Next:',
            '  - Add #[FromRequest] filters on list query',
            '  - Wire filters in QueryHandler (addFilter + build)',
            '  - Use with make:api-controller --list --view',
        ]);
    }

    /**
     * Task\Task + GetTaskQuery → Task\Task\GetTaskQuery
     */
    private function nestedClassName(string $modelArgument, string $classShortName): string
    {
        $parts = explode('\\', $modelArgument);
        $parts[] = $classShortName;

        return implode('\\', $parts);
    }
}
