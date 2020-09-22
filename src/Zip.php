<?php declare(strict_types=1);
namespace PrivatePackagist\ComposerSplit;

class Zip
{
    public function zipSubDirectories(string $downloadPath): array
    {
        $zipFiles = [];
        $repo = new \DirectoryIterator($downloadPath);
        foreach ($repo as $file) {
            if (!$file->isDir() || $file->isDot()) {
                continue;
            }

            if (!file_exists($file->getPathname().'/composer.json')) {
                continue;
            }

            $zip = new \ZipArchive();
            $zipComposer = json_decode(file_get_contents($file->getPathname().'/composer.json'), true);
            $zipFileName = sys_get_temp_dir().'/'.$file->getFilename().'-'.$zipComposer['version'].'.zip';
            $zip->open($zipFileName,\ZipArchive::CREATE | \ZipArchive::OVERWRITE);

            $rootPath = $file->getPathname();
            /** @var \SplFileInfo[] $files */
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($rootPath),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $dirFiles) {
                if (!$dirFiles->isDir()) {
                    $filePath = $dirFiles->getRealPath();
                    $relativePath = substr($filePath, strlen($rootPath) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }

            $zip->close();

            $zipFiles[$zipComposer['name']] = $zipFileName;
        }

        return $zipFiles;
    }
}