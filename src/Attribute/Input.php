<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Attribute;

use Attribute;

/**
 * Class Input.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
#[Attribute(Attribute::TARGET_METHOD)]
class Input
{
    public string $class;

    public string $in = 'formData';

    /**
     * @param class-string|array<string, mixed>|null $class
     */
    public function __construct(string|array|null $class = null, string $in = 'formData')
    {
        if (\is_array($class)) {
            $data = $class;
            $this->class = (string) ($data['value'] ?? $data['class'] ?? '');
            $this->in = (string) ($data['in'] ?? 'formData');

            return;
        }

        $this->class = (string) $class;
        $this->in = $in;
    }
}
