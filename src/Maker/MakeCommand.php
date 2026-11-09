<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Maker;

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
 * Scaffolds CQRS commands + handler under Command/{Domain}/{Entity}/.
 *
 * @author nilov <nilov@glavweb.ru>
 */
class MakeCommand extends AbstractMaker
{
    public static function getCommandName(): string
    {
        return 'make:command';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates CQRS command classes and handler (mirrors Entity path)';
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

        $managerClass = $generator->createClassNameDetails(
            $modelArgument,
            'Model\\',
            'Manager'
        );

        $shortName = $modelClass->getShortName();
        $varName = lcfirst($shortName);

        $createCommandClass = $generator->createClassNameDetails(
            $this->nestedClassName($modelArgument, 'Create'.$shortName.'Command'),
            'Command\\',
            ''
        );
        $updateCommandClass = $generator->createClassNameDetails(
            $this->nestedClassName($modelArgument, 'Update'.$shortName.'Command'),
            'Command\\',
            ''
        );
        $deleteCommandClass = $generator->createClassNameDetails(
            $this->nestedClassName($modelArgument, 'Delete'.$shortName.'Command'),
            'Command\\',
            ''
        );
        $handlerClass = $generator->createClassNameDetails(
            $this->nestedClassName($modelArgument, $shortName.'CommandHandler'),
            'Command\\',
            ''
        );

        $common = [
            'modelClass' => $modelClass,
            'managerClass' => $managerClass,
            'shortName' => $shortName,
            'varName' => $varName,
            'author' => $input->getOption('author'),
            'authorEmail' => $input->getOption('author-email'),
        ];

        $generator->generateClass(
            $createCommandClass->getFullName(),
            __DIR__.'/../../templates/skeleton/command/CreateCommand.tpl.php',
            $common + [
                'handlerClass' => $handlerClass,
                'handlerMethod' => 'create'.$shortName,
            ]
        );

        $generator->generateClass(
            $updateCommandClass->getFullName(),
            __DIR__.'/../../templates/skeleton/command/UpdateCommand.tpl.php',
            $common + [
                'handlerClass' => $handlerClass,
                'handlerMethod' => 'update'.$shortName,
            ]
        );

        $generator->generateClass(
            $deleteCommandClass->getFullName(),
            __DIR__.'/../../templates/skeleton/command/DeleteCommand.tpl.php',
            $common + [
                'handlerClass' => $handlerClass,
                'handlerMethod' => 'delete'.$shortName,
            ]
        );

        $generator->generateClass(
            $handlerClass->getFullName(),
            __DIR__.'/../../templates/skeleton/command/CommandHandler.tpl.php',
            $common + [
                'createCommandClass' => $createCommandClass,
                'updateCommandClass' => $updateCommandClass,
                'deleteCommandClass' => $deleteCommandClass,
            ]
        );

        $generator->writeChanges();
        $this->writeSuccessMessage($io);

        $io->text([
            'Next:',
            sprintf('  - Fill #[FromRequest] / #[EntityField] fields on Create/Update commands'),
            sprintf('  - Wire API controller to CommandBus::handle()'),
        ]);
    }

    /**
     * Task\Task + CreateTaskCommand → Task\Task\CreateTaskCommand
     */
    private function nestedClassName(string $modelArgument, string $classShortName): string
    {
        $parts = explode('\\', $modelArgument);
        $parts[] = $classShortName;

        return implode('\\', $parts);
    }
}
