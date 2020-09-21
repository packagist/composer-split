<?php
namespace PrivatePackagist\ComposerSplit;

class GitHubDownloader
{
    /**
     * @var \Github\Client
     */
    private $client;

    /**
     * GitHubDownloader constructor.
     * @param \Github\Client $client
     */
    public function __construct(\Github\Client $client)
    {
        $this->client = $client;
    }

    public function authenticate(string $username, string $token)
    {
        $this->client->authenticate($username, $token, \Github\Client::AUTH_HTTP_PASSWORD);
    }

    public function getAllRefs(string $username, string $repo): array
    {
        $branches = $this->client->api('gitData')->references()->branches($username, $repo);
        $tags = $this->client->api('gitData')->references()->tags($username, $repo);

        return array_merge(array_column($branches, 'ref'), array_column($tags, 'ref'));
    }

    public function downloadRepositoryContents(string $username, string $repo, string $reference, string $downloadPath)
    {
        $resource = $this->client->api('gitData')->trees()->show($username, $repo, $reference, true);
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

                $fileInfo = $this->client->api('repo')->contents()->show($username, $repo, $file['path'], $reference);
                $fileDir = pathinfo($downloadPath.'/'.$file['path'], PATHINFO_DIRNAME);
                if (!file_exists($fileDir)) {
                    if (!mkdir($fileDir) && !is_dir($fileDir)) {
                        throw new \RuntimeException(sprintf('Directory "%s" was not created', $fileDir));
                    }
                }

                file_put_contents($downloadPath.'/'.$file['path'], base64_decode($fileInfo['content']));
            }
        }

        // add version entry to composer.json
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