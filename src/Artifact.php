<?php declare(strict_types=1);
namespace PrivatePackagist\ComposerSplit;

use PrivatePackagist\ApiClient\Exception\ResourceNotFoundException;
use PrivatePackagist\ApiClient\Client;

class Artifact
{
    /** @var Client */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function createArtifact(string $fileName): int
    {
        $file = file_get_contents($fileName);
        $fileName = pathinfo($fileName, PATHINFO_FILENAME);

        $result = $this->client->packages()->artifacts()->create($file, 'application/zip', $fileName);
        return $result['id'];
    }

    public function createOrUpdatePackageArtifact(string $packageName, array $artifactPackageFileIds)
    {
        try {
            $this->editArtifactPackage($packageName, $artifactPackageFileIds);
        } catch (ResourceNotFoundException $exception) {
            $this->createArtifactPackage($artifactPackageFileIds);
        } catch (\Exception $exception) {
            throw $exception;
        }
    }

    private function createArtifactPackage(array $artifactPackageFileIds): void
    {
        $this->client->packages()->createArtifactPackage($artifactPackageFileIds);
    }

    private function editArtifactPackage(string $packageName, array $artifactPackageFileIds): void
    {
        $this->client->packages()->editArtifactPackage($packageName, $artifactPackageFileIds);
    }
}