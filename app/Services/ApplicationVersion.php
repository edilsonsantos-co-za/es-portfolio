<?php

namespace App\Services;

use RuntimeException;

class ApplicationVersion
{
    private string $version;

    public function __construct(string $versionFilePath)
    {
        if (!is_file($versionFilePath)) {
            throw new RuntimeException(sprintf('Version file not found: %s', $versionFilePath));
        }

        $rawVersion = trim((string) file_get_contents($versionFilePath));

        if ($rawVersion === '') {
            throw new RuntimeException(sprintf('Version file is empty: %s', $versionFilePath));
        }

        if (!preg_match('/^\d+\.\d+\.\d+$/', $rawVersion)) {
            throw new RuntimeException(sprintf(
                'Version "%s" must use semantic version format X.Y.Z',
                $rawVersion
            ));
        }

        $this->version = $rawVersion;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function releaseTag(): string
    {
        return sprintf('v%s', $this->version);
    }
}
