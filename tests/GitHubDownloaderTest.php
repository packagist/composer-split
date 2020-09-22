<?php declare(strict_types=1);
namespace PrivatePackagist\ComposerSplit;

use Github\Api\GitData;
use Github\Client;
use PHPUnit\Framework\TestCase;

class GitHubDownloaderTest extends TestCase
{
    public function testGetAllRefs(): void
    {
        $references = $this->createMock(GitData\References::class);
        $branches = [
            [
                'ref' => "refs/heads/dev-test",
                'node_id' => "MDM6UmVmMjk2MjYzNDQyOnJlZnMvaGVhZHMvZGV2LXRlc3Q=",
                'url' => "https://api.github.com/repos/wissem/demo-artifact/git/refs/heads/dev-test",
                'object' => [
                    'sha' => "ffa4e75bd0a20f96ee41fe4c7f2f98effb40cbc8",
                    'type' => "commit",
                    'url' => "https://api.github.com/repos/wissem/demo-artifact/git/commits/ffa4e75bd0a20f96ee41fe4c7f2f98effb40cbc8",
                ]
            ]
        ];
        $references->expects($this->once())
            ->method('branches')
            ->with('username', 'repo')
            ->willReturn($branches);

        $tags = [
            [
                'ref' => "refs/tags/v0.1",
                'node_id' => "MDM6UmVmMjk2MjYzNDQyOnJlZnMvdGFncy92MC4x=",
                'url' => "https://api.github.com/repos/wissem/demo-artifact/git/refs/tags/v0.1",
                'object' => [
                    'sha' => "901910aec2a5fe851add778a1b2bf006abd1e9bd",
                    'type' => "tag",
                    'url' => "https://api.github.com/repos/wissem/demo-artifact/git/tags/901910aec2a5fe851add778a1b2bf006abd1e9bd",
                ]
            ]
        ];
        $references->expects($this->once())
            ->method('tags')
            ->with('username', 'repo')
            ->willReturn($tags);

        $gitData = $this->createMock(GitData::class);
        $gitData->expects($this->once())
            ->method('references')
            ->willReturn($references);
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('api')
            ->with('gitData')
            ->willReturn($gitData)
        ;

        $githubDownloader = new GitHubDownloader($client, 'username', 'token');

        $this->assertEquals([
            'refs/heads/dev-test',
            'refs/tags/v0.1',
        ], $githubDownloader->getAllRefs('repo'));
    }

    public function testDownloadRepositoryContents(): void
    {
        $trees = $this->createMock(GitData\Trees::class);
        $tree = [
            'sha' => "ffa4e75bd0a20f96ee41fe4c7f2f98effb40cbc8",
            'url' => "https://api.github.com/repos/wissem/demo-artifact/git/trees/ffa4e75bd0a20f96ee41fe4c7f2f98effb40cbc8",
            "truncated" => false,
            "tree" => [
                [
                    'path' => "LICENSE",
                    'mode' => "100644",
                    'type' => "blob",
                    'sha' => "261eeb9e9f8b2b4b0d119366dda99c6fd7d35c64",
                    'size' => 11357,
                    'url' => "https://api.github.com/repos/wissem/demo-artifact/git/blobs/261eeb9e9f8b2b4b0d119366dda99c6fd7d35c64",
                ],
                [
                    'path' => "README",
                    'mode' => "100755",
                    'type' => "blob",
                    'sha' => "6148cc19ae913008b34fe6679c04fa8a881ea5ce",
                    'size' => 66,
                    'url' => "https://api.github.com/repos/wissem/demo-artifact/git/blobs/6148cc19ae913008b34fe6679c04fa8a881ea5ce",
                ]
            ]
        ];
        $trees->expects($this->once())
            ->method('show')
            ->with('username', 'repo', 'refs/tags/v0.1', true)
            ->willReturn($tree);

        $gitData = $this->createMock(GitData::class);
        $gitData->expects($this->once())
            ->method('trees')
            ->willReturn($trees);
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('api')
            ->with('gitData')
            ->willReturn($gitData)
        ;

        $githubDownloader = new GitHubDownloader($client, 'username', 'token');
        $githubDownloader->downloadRepositoryContents('repo', 'refs/tags/v0.1', sys_get_temp_dir().'/test-artifact-'.uniqid('test-artifact', true));
    }
}