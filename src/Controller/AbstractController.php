<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Glavweb\ApiBundle\ErrorCodes;
use Glavweb\ApiBundle\Exception\ClientErrorException;
use League\Tactician\Bundle\Middleware\InvalidCommandException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseController;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Base API controller with Doctrine filter helpers and command validation.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
abstract class AbstractController extends BaseController
{
    public function __construct(private readonly ManagerRegistry $managerRegistry)
    {
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'doctrine' => '?'.ManagerRegistry::class,
            'validator' => '?'.ValidatorInterface::class,
        ]);
    }

    protected function getDoctrine(): ManagerRegistry
    {
        return $this->container->get('doctrine');
    }

    /**
     * @throws ClientErrorException
     */
    protected function getSortParameter(?string $sort): ?array
    {
        if (null !== $sort) {
            $sort = json_decode($sort, true);

            if (null === $sort) {
                throw new ClientErrorException('Parameter sort has wrong format', ErrorCodes::PARAMETER_WRONG_FORMAT);
            }

            return $sort;
        }

        return null;
    }

    protected function getRepository(string $class): EntityRepository
    {
        $repository = $this->managerRegistry->getManager()->getRepository($class);

        if ($repository instanceof EntityRepository) {
            return $repository;
        }

        throw new RuntimeException('Repository class must be instance of EntityRepository.');
    }

    protected function enablePublishableFilter(): void
    {
        $this->enableFilter('publishable_filter');
    }

    protected function disablePublishableFilter(): void
    {
        $this->disableFilter('publishable_filter');
    }

    protected function enableActivableFilter(): void
    {
        $this->enableFilter('activable_filter');
    }

    protected function disableActivableFilter(): void
    {
        $this->disableFilter('activable_filter');
    }

    /**
     * @param array<string, mixed> $parameters
     */
    protected function enableFilter(string $name, array $parameters = []): void
    {
        /** @var EntityManagerInterface $em */
        $em = $this->managerRegistry->getManager();

        if (!$em->getFilters()->isEnabled($name)) {
            $em->getFilters()->enable($name);
        }

        if ([] !== $parameters) {
            $filter = $em->getFilters()->getFilter($name);

            foreach ($parameters as $parameterName => $parameterValue) {
                $filter->setParameter($parameterName, $parameterValue);
            }
        }
    }

    protected function disableFilter(string $name): void
    {
        /** @var EntityManagerInterface $em */
        $em = $this->managerRegistry->getManager();

        if ($em->getFilters()->isEnabled($name)) {
            $em->getFilters()->disable($name);
        }
    }

    /**
     * @throws InvalidCommandException
     */
    protected function validateCommand(object $command): void
    {
        /** @var ValidatorInterface $validator */
        $validator = $this->container->get('validator');
        $violations = $validator->validate($command);
        if ($violations->count()) {
            throw InvalidCommandException::onCommand($command, $violations);
        }
    }
}
