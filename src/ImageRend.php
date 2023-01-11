<?php

namespace Alishahidi\ImageRendPhp;

use Intervention\Image\Gd\Font;
use Intervention\Image\ImageManager as Manager;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class ImageRend
{

    private $driver;

    private $directory;
    private $dateFormat = false;

    public function make($name, $directory = '', $dateFormat = false)
    {
        $file = $_FILES[$name]['tmp_name'];

        if (!$file) {
            $file = $name;
        }
        $filesystem = new Filesystem();
        if (isset($directory)) {
            $directory = trim($directory, "\/") . '/';
        }
        if ($dateFormat) {
            $directory .= date('Y/M/d/');
            $this->dateFormat = $dateFormat;
        }
        $this->directory = $directory;
        $filesystem->mkdir(Path::normalize($directory));
        $this->driver = (new Manager(['driver' => 'gd']))->make($file);

        return $this;
    }

    public function resize($width, $height)
    {
        $this->driver->resize($width, $height);

        return $this;
    }

    public function fit($width, $height)
    {
        $this->driver->fit($width, $height);

        return $this;
    }

    public function watermark($path, $width, $height, $pos = 'bottom-right', $x = 20, $y = 20)
    {
        $_driver = (new Manager(['driver' => 'gd']))->make($path);
        $_driver->resize($width, $height);
        $this->driver->insert($_driver, $pos, $x, $y);

        return $this;
    }

    public function text($text, $x = 20, $y = 20, $fontFile = 'fonts/Roboto-Regular.ttf', $size = 24, $color = '#ffffff', $pos = 'bottom-right', $angle = 0)
    {
        $bbox = imagettfbbox($size, $angle, $fontFile, $text);
        $width = abs($bbox[2] - $bbox[0]) - 30;
        $height = abs($bbox[7] - $bbox[1]);
        $font = new Font($text);
        $font->file($fontFile);
        $font->size($size);
        $font->color($color);
        $font->valign('top');
        $font->angle($angle);
        $_driver = (new Manager(['driver' => 'gd']));
        $imageText = $_driver->canvas($width, $height);
        $font->applyToImage($imageText);
        $this->driver->insert($imageText, $pos, $x, $y);

        return $this;
    }

    public function encode($quality = 42, $format = 'jpg')
    {
        return $this->driver->encode($format, $quality);
    }

    public function save($name = '', $quality = 42, $format = 'jpg', $unique = false)
    {
        $name = explode('.', $name)[0];
        if ($this->dateFormat) {
            $name .= date('Y_m_d_M_i_s');
        }
        if ($unique) {
            $name .= '_' . Uuid::uuid4()->toString();
        }
        $this->driver->save($this->directory . $name . '.' . $format, $quality, $format);

        return '/' . $this->directory . $name . '.' . $format;
    }
}
