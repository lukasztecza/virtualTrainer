<?php

namespace AppBundle\Asset\VersionStrategy;

use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;

class GulpBusterVersionStrategy implements VersionStrategyInterface
{
    /**
    * @var string
    */
    private $manifestPath;

    /**
    * @var string
    */
    private $format;

    /**
    * @var string[]
    */
    private $hashes;

    /**
    * @param string      $manifestPath
    * @param string|null $format
    */
    public function __construct($manifestPath, $format = null)
    {
        $this->manifestPath = $manifestPath;
        $this->format = $format ?: '%s?%s';
    }

    public function getVersion($path)
    {
        if (!is_array($this->hashes)) {
            $this->hashes = $this->loadManifest();
        }
        return isset($this->hashes['web/' . $path]) ? $this->hashes['web/' . $path] : '';
    }

    public function applyVersion($path)
    {
        $version = $this->getVersion($path);
        if ('' === $version) {
            return $path;
        }

        $versionized = sprintf($this->format, ltrim($path, '/'), $version);
        if ($path && '/' === $path[0]) {
            return '/'.$versionized;
        }

        return $versionized;
    }

    private function loadManifest()
    {
        return json_decode(file_get_contents($this->manifestPath), true);
    }
}
