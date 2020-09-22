<?php declare(strict_types=1);
namespace PrivatePackagist;


use PHPUnit\Framework\TestCase;
use PrivatePackagist\ComposerSplit\Zip;

class ZipTest extends TestCase
{
    public function testZipSubDirectories(): void
    {
        $zip = new Zip();
        $result = $zip->zipSubDirectories(__DIR__.'/Fixtures/repo');
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('acme/artifact', $result);
    }
}