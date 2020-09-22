<?php declare(strict_types=1);
namespace PrivatePackagist;

use PHPUnit\Framework\TestCase;
use PrivatePackagist\ComposerSplit\Artifact;
use PrivatePackagist\ComposerSplit\GitHubDownloader;
use PrivatePackagist\ComposerSplit\Split;
use PrivatePackagist\ComposerSplit\Zip;

class SplitTest extends TestCase
{
    public function testSplit(): void
    {
        $githubDownloader = $this->createMock(GitHubDownloader::class);
        $githubDownloader->expects($this->once())
            ->method('downloadRepositoryContents');
        $githubDownloader->expects($this->once())
            ->method('getAllRefs')
            ->with('repo')
            ->willReturn(['refs/tags/v0.1']);

        $zip = $this->createMock(Zip::class);
        $zip->expects($this->once())
            ->method('zipSubDirectories')
            ->willReturn(['acme/artifact' => '/tmp/file1.zip']);

        $artifact = $this->createMock(Artifact::class);
        $artifact->expects($this->once())
            ->method('createArtifact')
            ->with( '/tmp/file1.zip')
            ->willReturn(10);
        $artifact->expects($this->once())
            ->method('createOrUpdatePackageArtifact')
            ->with( 'acme/artifact', [10]);

        $split = new Split($artifact, $githubDownloader, $zip);
        $split->split('repo');
    }
}