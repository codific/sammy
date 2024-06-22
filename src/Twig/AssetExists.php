<?php
declare(strict_types=1);

namespace App\Twig;

use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AssetExists extends AbstractExtension
{
    public function __construct(private readonly KernelInterface $kernel)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('asset_exists', $this->assetExists(...)),
            new TwigFunction('asset_dir_exists', $this->assetDirExists(...)),
        ];
    }

    public function assetExists($path): bool
    {
        $webRoot = realpath($this->kernel->getProjectDir().'/public');
        $toCheck = realpath($webRoot.'/'.$path);

        // check if the file exists
        if (!is_file($toCheck)) {
            return false;
        }

        // check if file is well contained in web/ directory (prevents ../ in paths)
        if (strncmp($webRoot, $toCheck, strlen($webRoot)) !== 0) {
            return false;
        }

        return true;
    }

    public function assetDirExists($path): bool
    {
        $webRoot = realpath($this->kernel->getProjectDir().'/public');
        $toCheck = realpath($webRoot.'/'.$path);

        // check if the file exists
        if (!is_dir($toCheck)) {
            return false;
        }

        // check if file is well contained in web/ directory (prevents ../ in paths)
        if (strncmp($webRoot, $toCheck, strlen($webRoot)) !== 0) {
            return false;
        }

        return true;
    }
}
