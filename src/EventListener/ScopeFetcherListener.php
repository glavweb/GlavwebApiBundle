<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\EventListener;

use ReflectionMethod;
use ReflectionParameter;
use ReflectionNamedType;
use InvalidArgumentException;
use Glavweb\ApiBundle\Scope\ScopeFetcherInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * Class ScopeFetcherListener.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class ScopeFetcherListener
{
    /**
     * Name of scope fetcher interface.
     */
    public const SCOPE_FETCHER_INTERFACE = ScopeFetcherInterface::class;

    private ScopeFetcherInterface $scopeFetcher;

    /**
     * Constructor.
     */
    public function __construct(ScopeFetcherInterface $scopeFetcher)
    {
        $this->scopeFetcher = $scopeFetcher;
    }

    /**
     * Core controller handler.
     *
     * @throws InvalidArgumentException
     */
    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $controller = $event->getController();

        if (\is_object($controller) && \is_callable($controller) && method_exists($controller, '__invoke')) {
            $controller = [$controller, '__invoke'];
        }

        $this->scopeFetcher->setController($controller);

        $attributeName = $this->getAttributeName($controller);
        $request->attributes->set($attributeName, $this->scopeFetcher);
    }

    /**
     * Determines which attribute the ParamFetcher should be injected as.
     *
     * @param callable $controller the controller action as an "array" callable
     */
    private function getAttributeName(callable $controller): string
    {
        if (!\is_array($controller)) {
            return 'scopeFetcher';
        }

        list($controllerObject, $methodName) = $controller;

        $method = new ReflectionMethod($controllerObject, $methodName);
        foreach ($method->getParameters() as $param) {
            if ($this->isScopeFetcherType($param)) {
                return $param->getName();
            }
        }

        // If there is no typehint, inject the ScopeFetcher using a default name.
        return 'scopeFetcher';
    }

    /**
     * Returns true if the given controller parameter is type-hinted as an instance of ScopeFetcher.
     *
     * @param ReflectionParameter $controllerParam a parameter of the controller action
     *
     * @return bool
     */
    private function isScopeFetcherType(ReflectionParameter $controllerParam)
    {
        $type = $controllerParam->getType();
        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return false;
        }

        return is_a($type->getName(), self::SCOPE_FETCHER_INTERFACE, true);
    }
}
