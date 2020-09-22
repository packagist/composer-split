<?php declare(strict_types=1);
namespace PrivatePackagist\ComposerSplit;

class Split
{
    /** @var Artifact */
    private $artifact;
    /** @var GitHubDownloader */
    private $githubDownloader;
    /** @var Zip */
    private $zip;

    public function __construct(Artifact $artifact, GitHubDownloader $githubDownloader, Zip $zip)
    {
        $this->artifact = $artifact;
        $this->githubDownloader = $githubDownloader;
        $this->zip = $zip;
    }

    public function split(string $githubRepo): void
    {
        $downloadPath = sys_get_temp_dir() . '/'.uniqid('dir', true).'-' . $githubRepo;
        $references = $this->githubDownloader->getAllRefs($githubRepo);
        $zipFilesRes = [];
        foreach ($references as $ref) {
            $refName = explode('/', $ref)[2];
            $downloadRefPath = $downloadPath.'-'.$refName;
            $this->githubDownloader->downloadRepositoryContents($githubRepo, $ref, $downloadRefPath);
            $zipFiles = $this->zip->zipSubDirectories($downloadRefPath);
            foreach ($zipFiles as $packageName => $zipFile) {
                $zipFilesRes[$packageName][] = $zipFile;
            }
        }

        foreach ($zipFilesRes as $packageName => $zipFileNames) {
            $artifactIds = [];
            foreach ($zipFileNames as $zip) {
                $artifactIds[] = $this->artifact->createArtifact($zip);
            }
            $this->artifact->createOrUpdatePackageArtifact($packageName, $artifactIds);
        }
    }
}