<?php declare(strict_types=1);
namespace PrivatePackagist\ComposerSplit;

use Github\Api\GitData;
use Github\Api\Repo;

class GitHubDownloader
{
    /** @var \Github\Client */
    private $client;
    /** @var string */
    private $username;
    /** @var string */
    private $token;

    public function __construct(\Github\Client $client, string $username, string $token)
    {
        $this->client = $client;
        $this->username = $username;
        $this->token = $token;
    }

    public function authenticate(): void
    {
        $this->client->authenticate($this->username, $this->token, \Github\Client::AUTH_HTTP_PASSWORD);
    }

    public function getAllRefs(string $repo): array
    {
        /** @var GitData $gitData */
        $gitData = $this->client->api('gitData');
        /** @var GitData\References $references */
        $references = $gitData->references();
        $branches = $references->branches($this->username, $repo);
        $tags = $references->tags($this->username, $repo);
        return array_merge(array_column($branches, 'ref'), array_column($tags, 'ref'));
    }

    public function downloadRepositoryContents(string $repo, string $reference, string $downloadPath): void
    {
        /** @var GitData $gitData */
        $gitData = $this->client->api('gitData');
        $resource = $gitData->trees()->show($this->username, $repo, $reference, true);
        $files = array_map(function (array $entry) {
            return [
                'name' => basename($entry['path']),
                'path' => $entry['path'],
                'type' => $entry['type'],
            ];
        }, $resource['tree']);
        $directories = array_filter($files, function(array $entry){
            return  $entry['type'] === 'tree';
        });
        $paths = array_column($files, 'path');
        $directoriesNamesWithComposer = [];
        foreach ($directories as $dir) {
            if (in_array($dir['path'].'/composer.json', $paths, true)) {
                $directoriesNamesWithComposer[] = $dir['name'];
            }
        }

        $downloads = [];
        foreach ($files as $file) {
            $dirName = pathinfo($file['path'], PATHINFO_DIRNAME);
            if (in_array($dirName, $directoriesNamesWithComposer, true)) {
                $downloads[$dirName][] = $file;
            }
        }
        if (!mkdir($concurrentDirectory = $downloadPath) && !is_dir($concurrentDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }

        foreach ($downloads as $dirName => $dirFiles) {
            foreach ($dirFiles as $file) {

                $fileInfo = $this->client->api('repo')->contents()->show($this->username, $repo, $file['path'], $reference);
                $fileDir = pathinfo($downloadPath.'/'.$file['path'], PATHINFO_DIRNAME);
                if (!file_exists($fileDir)) {
                    if (!mkdir($fileDir) && !is_dir($fileDir)) {
                        throw new \RuntimeException(sprintf('Directory "%s" was not created', $fileDir));
                    }
                }

                if ($fileInfo['content']) {
                    file_put_contents($downloadPath.'/'.$file['path'], base64_decode($fileInfo['content'], true));
                }
            }
        }

        // add a version entry to composer.json
        foreach ($downloads as $dirName => $dirFiles) {
            $composerJsonPath = $downloadPath.'/'.$dirName.'/composer.json';
            if (file_exists($composerJsonPath)) {
                $composerJson = json_decode(file_get_contents($composerJsonPath), true);
                if (!isset($composerJson['version'])) {
                    $refParts = explode('/', $reference);
                    if ($refParts[1] === 'heads') {
                        $version = 'dev-'.$refParts[2];
                    } else {
                        $version = $refParts[2];
                    }

                    $composerJson['version'] = $version;
                    file_put_contents($composerJsonPath, json_encode($composerJson));
                }
            }
        }
    }
}