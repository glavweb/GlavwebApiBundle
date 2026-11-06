<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Maker\DTO;

/**
 * Class ControllerMethodDTO.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class ControllerMethodDTO
{
    private string $template;

    private array $data = [];

    private array $namespaces = [];

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function setTemplate(string $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getNamespaces(): array
    {
        return $this->namespaces;
    }

    public function setNamespaces(array $namespaces): self
    {
        $this->namespaces = $namespaces;

        return $this;
    }
}
