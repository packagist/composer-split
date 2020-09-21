# Private Packagist Composer Split
Demo for automating the publication  of packages in subdirectories of a single GitHub repository. 

## Requirements
* PHP 7.0+

## Installation
1. Clone this repo
```
git clone git@github.com:packagist/composer-split.git
```
2. Edit `.env`
```
cp .env.dist .env
```
Then edit `.env` with your Private Packagist API credentials and GitHub parameters.

The GitHub repo configured in `.env` needs to have a composer.json in each subdirecotry in order to publish that as a package in Private Packagist.
## Basic usage

Host this repo somewhere and add `bin/composer-split.php in a GitHub webhook. This will update your packages in Private Packagist each time you push a change to GitHub.

## License

`private-packagist/composer-split` is licensed under the MIT License
