# <img src="panel/assets/images/icon.svg" height="28"> Formwork

[![Discord](https://img.shields.io/discord/637658168754831380?color=%235865f2&label=chat&logo=discord&logoColor=%23fff)](https://discord.gg/5Q3BmNY)
[![GitHub Release Date](https://img.shields.io/github/release-date/getformwork/formwork.svg)](https://github.com/getformwork/formwork/releases/latest)
[![GitHub All Releases](https://img.shields.io/github/downloads/getformwork/formwork/total.svg)](https://github.com/getformwork/formwork/releases)
[![Packagist](https://img.shields.io/packagist/dt/getformwork/formwork.svg?color=%23f28d1a&label=Packagist%20downloads)](https://packagist.org/packages/getformwork/formwork)
[![GitHub code size in bytes](https://img.shields.io/github/languages/code-size/getformwork/formwork.svg)]()
[![PHP from Packagist](https://img.shields.io/packagist/php-v/getformwork/formwork.svg?version=2.x-dev)](#requirements)

🏗 Formwork is a flat file-based Content Management System (CMS) to build and manage simple sites.

⚠️ **This is the `README` file for the [`2.x`](https://github.com/getformwork/formwork/tree/2.x) branch, currently in development and NOT STABLE.**

👉 **See the [`1.x`](https://github.com/getformwork/formwork/tree/1.x) branch for the latest stable version.**

## Features
- ⚡️ Lightweight
- 🗄 No database required!
- 📦 Easy to [install](#installing)
- ✨ Out-of-the-box Administration Panel

![](site/content/index/formwork.png)

## Requirements
- PHP **8.2** or higher
- PHP extensions `dom`, `fileinfo`, `gd`, `mbstring`, `openssl` and `zip`

## Installing

### From GitHub releases
You can download a ready-to-use `.zip` archive from [GitHub releases page](https://github.com/getformwork/formwork/releases) and just extract it in the webroot of your server.

### With Composer
If you prefer to install the latest stable release of Formwork with [Composer](https://getcomposer.org/) you can use this command:

```shell
composer create-project getformwork/formwork
```

Composer will create a `formwork` folder with a fresh ready-to-use Formwork installation.

### Cloning from GitHub
If you want to get the currently worked master version, you can clone the GitHub repository and then install the dependencies with Composer.

1. Clone the repository in your webroot:

```shell
git clone https://github.com/getformwork/formwork.git
```

2. Navigate to `formwork` folder and install the dependencies:

```shell
cd formwork
composer install
```

## Running Formwork server

You can test Formwork right away with the `serve` command, a customized wrapper of the [PHP Built-in web server](https://www.php.net/manual/en/features.commandline.webserver.php).

> [!IMPORTANT]
> As with PHP CLI web server, Formwork server is intended for **testing purposes** and not for production environments.

Navigate to the `formwork` folder and run the following command:

```shell
php bin/serve
```

If you prefer you can run the Formwork server through Composer:

```shell
composer serve
```
