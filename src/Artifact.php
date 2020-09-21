<?php
namespace PrivatePackagist\ComposerSplit;

use PrivatePackagist\ApiClient\Exception\ResourceNotFoundException;

class Artifact
{
    /** @var \PrivatePackagist\ApiClient\Client */
    private $client;

    /**
     * Artifact constructor.
     * @param $client
     */
    public function __construct($client)
    {
        $this->client = $client;
    }

    public function createArtifact(string $fileName): int
    {
        $file = file_get_contents($fileName);
        $fileName = pathinfo($fileName, PATHINFO_FILENAME);

        echo "Creating artifact for ".$fileName. ". \n";
        $result = $this->client->packages()->artifacts()->create($file, 'application/zip', $fileName);
        echo "Artifact created for ".$fileName. ". \n";

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