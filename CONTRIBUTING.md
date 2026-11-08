# Contributing to Formwork

👋 Thank you for being interested in Formwork! Issues and pull requests are always welcome.

➡️ Please take a moment to read these guidelines. The aim of this document is to keep contributors input always effective and manage the project as easily as possible.

This project follows a [Code of Conduct](CODE_OF_CONDUCT.md). By participating you are expected to uphold it.

## Asking questions

The [**Issues tracker**](https://github.com/getformwork/formwork/issues) is for bug reports and feature requests only.

➡️ **For support, questions and general ideas please use [GitHub Discussions](https://github.com/getformwork/formwork/discussions) or the Formwork channel on the [Discord Chat](https://discord.gg/5Q3BmNY).**

🔒 **Security vulnerabilities must not be reported in public issues.** Follow the [security policy](SECURITY.md) instead.

## Submitting issues

Always search for similar issues before opening a new one.

### Bug reports

🐛 Good bug reports are extremely helpful. Please update Formwork before reporting, to be sure the bug has not already been fixed in a recent release. Always give context and instructions to replicate the problem: Formwork and PHP versions, web server, steps to reproduce, expected and actual behavior.

### Feature requests

🌟 Feature requests are welcome, but take a moment to find out whether your idea fits with the scope of Formwork. **It's always up to you** convincing of the merits of the feature you're proposing. Consider opening a thread in the *Ideas* category of [Discussions](https://github.com/getformwork/formwork/discussions/categories/ideas) first.

### Translations

🌍 If you want to translate Formwork in another language or improve an existing translation, submit a [**pull request**](#submitting-pull-requests) instead of an issue.

## Submitting pull requests

Good pull requests such as patches, translations, improvements and new features are a valuable help. They should remain focused and avoid containing unrelated commits. Also remember they have to fit with the aims of the project.

➡️ **Please ask before** embarking on any significant pull request (e.g. implementing features, refactoring code), otherwise you risk spending a lot of time working on something that might not be merged into Formwork.

### Development setup

Fork and clone the repository, then install the dependencies (see the [README](README.md#installing) for details):

```shell
composer install
cd panel && pnpm install
```

Open pull requests against the `2.x` branch.

### Before submitting

Make sure the checks run by CI pass locally:

```shell
composer rector:check  # automated refactoring rules
composer fix:check     # coding style (use `composer fix` to fix it)
composer phpstan       # static analysis
composer test          # PHPUnit tests
```

If you changed the panel sources, also run:

```shell
cd panel
pnpm check             # lint TypeScript and CSS (use `pnpm lint` to fix)
pnpm build             # build the assets
```

Add or update tests for the code you change whenever possible, and describe what the pull request does and why.

## License

By contributing to Formwork you agree that your contributions will be licensed under the [MIT License](LICENSE).
