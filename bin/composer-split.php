<?php

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__.'/../');
$dotenv->load();

$apiClient = new \PrivatePackagist\ApiClient\Client(null, $_ENV['PIRVATE_PACKAGIST_URL']);
$apiClient->authenticate($_ENV['PIRVATE_PACKAGIST_API_TOKEN'], $_ENV['PIRVATE_PACKAGIST_API_SECRET']);

$zip = new \PrivatePackagist\ComposerSplit\Zip();
$githubDownloader = new \PrivatePackagist\ComposerSplit\GitHubDownloader( new \Github\Client(), $_ENV['GITHUB_USERNAME'], $_ENV['GITHUB_AUTH_HTTP_PASSWORD']);
$githubDownloader->authenticate();
$artifact = new \PrivatePackagist\ComposerSplit\Artifact($apiClient);

$composerSplit = new \PrivatePackagist\ComposerSplit\Split($artifact, $githubDownloader, $zip);
$composerSplit->split($_ENV['GITHUB_REPO']);


