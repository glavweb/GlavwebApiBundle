<?php

declare(strict_types=1);

namespace Glavweb\ApiBundle\Imagine;

use Imagine\Image\BoxInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Metadata\MetadataReaderInterface;
use Imagine\Imagick\Imagine as ImagickImagine;

/**
 * Обёртка над драйвером imagine, которая для анимированного gif загружает только первый кадр.
 *
 * Проблема исходного пути: \Imagine\Imagick\Imagine::load() вызывает Imagick::readImageBlob(),
 * который декодирует ВСЕ кадры анимированного gif сразу. Для gif с сотнями кадров крупного
 * размера (например 1905x956 x 251 кадр) это раскрывает сотни растров в память ImageMagick,
 * ведёт к скачку ресурсов и падению процесса по памяти (OOM, exit 137) ещё до фильтров.
 *
 * Здесь для gif пишем содержимое во временный файл и читаем через селектор "[0]" —
 * \Imagick($file."[0]") декодирует только первый кадр. Всё остальное делегируется
 * внутреннему драйверу (сохраняются палитра, метаданные).
 */
class FirstFrameImagine implements ImagineInterface
{
    public function __construct(
        private ImagickImagine $imagine,
    ) {
    }

    public function create(BoxInterface $size, $color = null)
    {
        return $this->imagine->create($size, $color);
    }

    public function open($path)
    {
        return $this->imagine->open($path);
    }

    public function load($string)
    {
        if ($this->isGif($string)) {
            return $this->loadFirstFrameFromBlob($string);
        }

        return $this->imagine->load($string);
    }

    public function read($resource)
    {
        return $this->imagine->read($resource);
    }

    public function font($file, $size, $color)
    {
        return $this->imagine->font($file, $size, $color);
    }

    public function setMetadataReader(MetadataReaderInterface $metadataReader)
    {
        $this->imagine->setMetadataReader($metadataReader);

        return $this;
    }

    public function getMetadataReader()
    {
        return $this->imagine->getMetadataReader();
    }

    public function setClassFactory(\Imagine\Factory\ClassFactoryInterface $classFactory)
    {
        $this->imagine->setClassFactory($classFactory);

        return $this;
    }

    public function getClassFactory()
    {
        return $this->imagine->getClassFactory();
    }

    private function isGif(string $string): bool
    {
        return str_starts_with($string, 'GIF8');
    }

    /**
     * Загружает только первый кадр gif из blob.
     */
    private function loadFirstFrameFromBlob(string $string)
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'imagick_firstframe_');
        if (false === $tmpFile) {
            throw new \RuntimeException('Не удалось создать временный файл для чтения первого кадра gif');
        }

        try {
            file_put_contents($tmpFile, $string);

            $imagick = new \Imagick($tmpFile.'[0]');
            $imagick->setImageFormat('gif');
            file_put_contents($tmpFile, $imagick->getImagesBlob());
            $imagick->clear();
            $imagick->destroy();

            return $this->imagine->open($tmpFile);
        } catch (\ImagickException $e) {
            throw new \RuntimeException('Не удалось прочитать первый кадр gif: '.$e->getMessage(), 0, $e);
        } finally {
            @unlink($tmpFile);
        }
    }
}