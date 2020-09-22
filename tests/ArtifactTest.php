<?php declare(strict_types=1);
namespace PrivatePackagist;

use PHPUnit\Framework\TestCase;
use PrivatePackagist\ApiClient\Api\Packages;
use PrivatePackagist\ApiClient\Client;
use PrivatePackagist\ApiClient\Exception\ResourceNotFoundException;
use PrivatePackagist\ComposerSplit\Artifact;

class ArtifactTest extends TestCase
{
    public function testCreateArtifact(): void
    {
        $artifacts = $this->createMock(Packages\Artifacts::class);
        $artifacts->expects($this->once())
            ->method('create')
            ->willReturn(['id' => 10]);

        $packages = $this->createMock(Packages::class);
        $packages->expects($this->once())
            ->method('artifacts')
            ->willReturn($artifacts);

        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('packages')
            ->willReturn($packages);

        $artifact = new Artifact($client);
        $this->assertEquals(10, $artifact->createArtifact(__DIR__.'/Fixtures/file.zip'));
    }

    public function testCreateOrUpdatePackageArtifact(): void
    {
        $packages = $this->createMock(Packages::class);
        $packages->expects($this->once())
            ->method('editArtifactPackage')
            ->with('acme/artifact', [10])
            ->willThrowException(new ResourceNotFoundException());

        $packages->expects($this->once())
            ->method('createArtifactPackage')
            ->with([10]);

        $client = $this->createMock(Client::class);
        $client->expects($this->atMost(2))
            ->method('packages')
            ->willReturn($packages);

        $artifact = new Artifact($client);
        $artifact->createOrUpdatePackageArtifact('acme/artifact', [10]);
    }
}