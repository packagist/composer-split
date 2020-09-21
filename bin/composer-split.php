<?php
set_time_limit(60);


require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/../');
$dotenv->load();

$client = new \PrivatePackagist\ApiClient\Client(null, $_ENV['PIRVATE_PACKAGIST_URL']);
$client->authenticate($_ENV['PIRVATE_PACKAGIST_API_TOKEN'], $_ENV['PIRVATE_PACKAGIST_API_SECRET']);

$github = new \Github\Client();
$githubUsername = $_ENV['GITHUB_USERNAME'];
$githubRepo = $_ENV['GITHUB_REPO'];

$downloadPath = sys_get_temp_dir() . '/'.uniqid('dir').'-' . $githubRepo;
$zip = new \PrivatePackagist\ComposerSplit\Zip();

$githubDownloader = new \PrivatePackagist\ComposerSplit\GitHubDownloader($github);
$githubDownloader->authenticate($_ENV['GITHUB_USERNAME'], $_ENV['GITHUB_AUTH_HTTP_PASSWORD']);
$references = $githubDownloader->getAllRefs($githubUsername, $githubRepo);
$zipFiles = [];
$zipFilesRes = [];
foreach ($references as $ref) {
    $refName = explode('/', $ref)[2];
    echo "Downloading repo contents for reference: ".$refName." \n";
    $downloadRefPath = $downloadPath.'-'.$refName;
    $githubDownloader->downloadRepositoryContents($githubUsername, $githubRepo, $ref, $downloadRefPath);
    $zipFiles = $zip->zipSubDirectories($downloadRefPath);
    foreach ($zipFiles as $packageName => $zipFile) {
        $zipFilesRes[$packageName][] = $zipFile;
    }
}

$artifact = new \PrivatePackagist\ComposerSplit\Artifact($client);
foreach ($zipFilesRes as $packageName => $zipFileNames) {
    $artifactIds = [];
    foreach ($zipFileNames as $zip) {
        echo "Creating artifact for zip file: ".$zip." \n";
        $artifactIds[] = $artifact->createArtifact($zip);
    }
    echo "Creating package \"".$packageName."\"\n";
    $artifact->createOrUpdatePackageArtifact($packageName, $artifactIds);
}
echo "Done.";
