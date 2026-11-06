<?php

declare(strict_types=1);

/*
 * This file is part of the Glavweb RestBundle package.
 *
 * (c) Andrey Nilov <nilov@glavweb.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Glavweb\ApiBundle\Scope;

use InvalidArgumentException;
use ReflectionClass;
use Glavweb\ApiBundle\Attribute\Scope;
use Glavweb\ApiBundle\Exception\InvalidScopeNameException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Class ScopeFetcher.
 *
 * @author Nilov Andrey <nilov@glavweb.ru>
 */
class ScopeFetcher implements ScopeFetcherInterface
{
    private ScopeReader $scopeReader;

    private AuthorizationCheckerInterface $authorizationChecker;

    private Request $request;

    /** @var callable|null */
    private mixed $controller = null;

    /**
     * @var Scope[]
     */
    private array $scopeAnnotations = [];

    /**
     * Initializes scope fetcher.
     */
    public function __construct(ScopeReader $scopeReader, AuthorizationCheckerInterface $authorizationChecker, RequestStack $requestStack)
    {
        $this->scopeReader = $scopeReader;
        $this->authorizationChecker = $authorizationChecker;
        $request = $requestStack->getCurrentRequest();
        if (null === $request) {
            throw new \LogicException('Нет текущего запроса');
        }
        $this->request = $request;
    }

    public function setController(callable $controller): void
    {
        $this->controller = $controller;
    }

    public function getAvailable(?string $requestName = null, ?string $default = null): ?string
    {
        $annotations = $this->getScopeAnnotations();
        if ($annotations) {
            if (!$requestName) {
                return $this->getFirstAvailableScopePath($default);
            }

            $this->checkScopeName($requestName);

            foreach ($annotations as $annotation) {
                if ($requestName != $annotation->getName()) {
                    continue;
                }

                if ($this->isGranted($annotation)) {
                    return $annotation->getPath();
                }
            }
        }

        return $default;
    }

    public function isGranted(Scope $annotation): bool
    {
        $role = $annotation->getRole();
        if ($role) {
            return $this->authorizationChecker->isGranted($role);
        }

        return true;
    }

    /**
     * @return Scope[]
     */
    private function getScopeAnnotations(): array
    {
        if (!$this->scopeAnnotations) {
            if (!$this->controller) {
                throw new InvalidArgumentException('Controller and method needs to be set via setController');
            }

            if (!is_array($this->controller) || empty($this->controller[0]) || !is_object($this->controller[0])) {
                throw new InvalidArgumentException('Controller needs to be set as a class instance (closures/functions are not supported)');
            }

            $this->scopeAnnotations = $this->scopeReader->read(
                new ReflectionClass(get_class($this->controller[0])),
                $this->controller[1]
            );
        }

        return $this->scopeAnnotations;
    }

    private function getFirstAvailableScopePath(?string $default = null): ?string
    {
        $annotations = $this->getScopeAnnotations();

        foreach ($annotations as $annotation) {
            if ($this->isGranted($annotation)) {
                return $annotation->getPath();
            }
        }

        return $default;
    }

    /**
     * @throws InvalidScopeNameException If Scope name is not defined
     */
    private function checkScopeName(string $scopeName): void
    {
        $annotations = $this->getScopeAnnotations();
        foreach ($annotations as $annotation) {
            if ($annotation->getName() == $scopeName) {
                return;
            }
        }

        throw new InvalidScopeNameException(sprintf('Scope name "%s" is not defined.', $scopeName));
    }
}
