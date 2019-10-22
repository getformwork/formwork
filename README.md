# Formwork

[![GitHub Release Date](https://img.shields.io/github/release-date/getformwork/formwork.svg)](https://github.com/getformwork/formwork/releases/latest)
[![GitHub All Releases](https://img.shields.io/github/downloads/getformwork/formwork/total.svg)](https://github.com/getformwork/formwork/releases)
[![Packagist](https://img.shields.io/packagist/dt/getformwork/formwork.svg?color=%23f28d1a&label=Packagist%20downloads)](https://packagist.org/packages/getformwork/formwork)
[![GitHub code size in bytes](https://img.shields.io/github/languages/code-size/getformwork/formwork.svg)]()
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/8c39728de81141ca9258acf46af50d51)](https://www.codacy.com/app/getformwork/formwork)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/getformwork/formwork.svg)](#requirements)

Formwork is a flat file-based Content Management System (CMS) to make and manage simple sites.

Latest version: [**1.2.1**](https://github.com/getformwork/formwork/releases/latest) | [**Changelog**](CHANGELOG.md)

## Features
- ⚡️ Lightweight
- 🗄 No database required!
- 📦 Easy to [install](#installing)
- ✨ Out-of-the-box Administration Panel

![](assets/images/formwork.png)

## Requirements
- PHP 7.1.3 or higher
- PHP `zip` extension
- PHP `gd` extension

## Installing

### From GitHub releases
You can download a ready-to-use `.zip` archive from [GitHub releases page](https://github.com/getformwork/formwork/releases) and just extract it in the webroot of your server.

### With Composer
If you prefer to install the latest stable release of Formwork with [Composer](https://getcomposer.org/) you can use this command:

```
$ composer create-project getformwork/formwork
```

Composer will create a `formwork` folder with a fresh ready-to-use Formwork installation.

### Cloning from GitHub
If you want to get the currently worked master version, you can clone the GitHub repository and then install the dependencies with Composer.

1. Clone the repository in your webroot:

```
$ git clone https://github.com/getformwork/formwork.git
```

2. Navigate to `formwork` folder and install the dependencies:

```
$ cd formwork
$ composer install
```
