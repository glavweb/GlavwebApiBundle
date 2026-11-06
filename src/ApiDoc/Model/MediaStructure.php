<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\ApiDoc\Model;

/**
 * OpenAPI DTO ответа upload-роутов GlavwebUploaderBundle.
 *
 * @author Sergey Zvyagintsev <nitron.ru@gmail.com>
 */
class MediaStructure
{
    /**
     * Secured Id.
     */
    public string $id;

    public string $name;

    public string $description;

    public string $thumbnail_path;

    public string $content_path;

    public string $content_type;

    public int $content_size;

    public int $width;

    public int $height;

    public string $provider_reference;
}
