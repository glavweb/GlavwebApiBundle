<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Attribute;

use Attribute;

/**
 * Class ErrorCodesDescription.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
#[Attribute(Attribute::TARGET_METHOD)]
class ErrorCodesDescription
{
    /** @var list<int|string> */
    public array $errorCodes = [];

    /** @var array<int|string, string> */
    public array $messages = [];

    /**
     * @param list<int|string>          $errorCodes
     * @param array<int|string, string> $messages
     */
    public function __construct(array $errorCodes = [], array $messages = [])
    {
        $this->errorCodes = $errorCodes;
        $this->messages = $messages;
    }
}
