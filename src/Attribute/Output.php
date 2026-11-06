<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Attribute;

use Attribute;

/**
 * Class Output.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
#[Attribute(Attribute::TARGET_METHOD)]
class Output
{
    public ?string $dataSchema = null;

    public ?string $scope = null;

    /**
     * @param string|array<string, mixed>|null $dataSchema
     */
    public function __construct(string|array|null $dataSchema = null, ?string $scope = null)
    {
        if (\is_array($dataSchema)) {
            $this->dataSchema = $dataSchema['dataSchema'] ?? null;
            $this->scope = $dataSchema['scope'] ?? null;

            return;
        }

        $this->dataSchema = $dataSchema;
        $this->scope = $scope;
    }
}
