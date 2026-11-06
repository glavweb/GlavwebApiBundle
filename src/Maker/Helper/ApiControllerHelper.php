<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Maker\Helper;

use Symfony\Bundle\MakerBundle\Util\ClassNameDetails;

/**
 * Class ApiControllerHelper.
 *
 * @author Andrey Nilov <nilov@glavweb.ru>
 */
class ApiControllerHelper extends Helper
{
    public function getRoute(ClassNameDetails $modelClass, ?string $suffix = null): string
    {
        $relativeName = $modelClass->getRelativeName();
        $relativeName = str_replace('\\', '/', $relativeName);

        return $this->plural($this->lowerDash($relativeName)).($suffix ? '/'.$suffix : '');
    }

    public function getRouteName(ClassNameDetails $modelClass, string $actionName, ?string $suffix = null): string
    {
        $relativeName = $modelClass->getRelativeName();
        $relativeName = str_replace('\\', '_', $relativeName);

        return
            'api_'.$actionName.'_'
            .$this->plural($this->tableize($relativeName))
            .($suffix ? '_'.$suffix : '')
        ;
    }
}
