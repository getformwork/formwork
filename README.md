# Formwork
Formwork is a flat file-based Content Management System (CMS) to make and manage simple sites.

Latest version: [**0.10.1**](https://github.com/giuscris/formwork/releases/tag/0.10.1) | [**Changelog**](CHANGELOG.md)

## Features
 * ⚡️ Lightweight Core (~350 kB dependencies included)
 * 🗄 No database required!
 * 📑 Simple file-based Cache system
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
