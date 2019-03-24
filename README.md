# Formwork

[![GitHub Release Date](https://img.shields.io/github/release-date/giuscris/formwork.svg)](https://github.com/giuscris/formwork/releases/latest)
[![GitHub All Releases](https://img.shields.io/github/downloads/giuscris/formwork/total.svg)](https://github.com/giuscris/formwork/releases)
[![Packagist](https://img.shields.io/packagist/dt/giuscris/formwork.svg?color=%23f28d1a&label=Packagist%20downloads)](https://packagist.org/packages/giuscris/formwork)
[![GitHub code size in bytes](https://img.shields.io/github/languages/code-size/giuscris/formwork.svg)](#)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/giuscris/formwork.svg)](#requirements)

Formwork is a flat file-based Content Management System (CMS) to make and manage simple sites.

Latest version: [**0.10.1**](https://github.com/giuscris/formwork/releases/latest) | [**Changelog**](CHANGELOG.md)

## Features
 * ⚡️ Lightweight
 * 🗄 No database required!
 * 📦 Easy to [install](#installing)
 * ✨ Out-of-the-box Administration Panel

![](assets/images/formwork.png)

## Requirements
 * PHP 5.6.0 or higher
 * PHP `zip` extension
 * PHP `gd` extension

## Installing

### From GitHub releases
You can download a ready-to-use `.zip` archive from [GitHub releases page](https://github.com/giuscris/formwork/releases) and just extract it in your webroot of your server.

### With Composer
If you prefer to install the latest stable release of Formwork with [Composer](https://getcomposer.org/) you can use this command:

```
$ composer create-project giuscris/formwork
```

Composer will create a `formwork` folder with a fresh ready-to-use Formwork installation.

### Cloning from GitHub
If you want to get the currently worked master version, you can clone the GitHub repository and then install the dependencies with Composer.

1. Clone the repository in your webroot:

```
$ git clone https://github.com/giuscris/formwork.git
```

2. Navigate to `formwork` folder and install the dependencies:

```
$ cd formwork
$ composer install
```
