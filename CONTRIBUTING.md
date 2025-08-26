# Contributing to NotifyManager

Thank you for considering contributing to NotifyManager! We welcome contributions from everyone.

## Code of Conduct

This project and everyone participating in it is governed by our Code of Conduct. By participating, you are expected to uphold this code.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the existing issues to avoid duplicates. When you are creating a bug report, please include as many details as possible:

- **Use a clear and descriptive title**
- **Describe the exact steps to reproduce the problem**
- **Provide specific examples**
- **Describe the behavior you observed and what behavior you expected**
- **Include environment details** (PHP version, Laravel version, etc.)

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion, please include:

- **Use a clear and descriptive title**
- **Provide a step-by-step description of the suggested enhancement**
- **Provide specific examples to demonstrate the enhancement**
- **Explain why this enhancement would be useful**

### Pull Requests

- Fill in the required template
- Do not include issue numbers in the PR title
- Include screenshots and animated GIFs when appropriate
- Follow the PHP and Laravel coding standards
- Include thoughtfully-worded, well-structured Pest tests
- Document new code based on the Documentation Styleguide
- End all files with a newline

## Development Setup

1. Fork the repository
2. Clone your fork: `git clone https://github.com/your-username/notify-manager.git`
3. Install dependencies: `composer install`
4. Run tests: `composer test`
5. Check code style: `composer format`

## Coding Standards

This project follows Laravel coding standards:

- Use Laravel Pint for code formatting: `vendor/bin/pint`
- Write tests for new features using Pest PHP
- Follow PSR-12 coding standards
- Use modern PHP 8.3+ features where appropriate
- Document public methods and classes

## Testing

- Run the test suite: `vendor/bin/pest`
- Run tests with coverage: `composer test-coverage`
- Write tests for new functionality
- Ensure all tests pass before submitting PRs

## Git Commit Messages

- Use the present tense ("Add feature" not "Added feature")
- Use the imperative mood ("Move cursor to..." not "Moves cursor to...")
- Limit the first line to 72 characters or less
- Reference issues and pull requests liberally after the first line

## Documentation

- Update the README.md if needed
- Update inline documentation for new methods
- Update configuration examples if adding new config options
- Keep the CHANGELOG.md updated

## Release Process

Releases are managed by the maintainers:

1. Update CHANGELOG.md
2. Update version in composer.json
3. Create a new Git tag
4. Create a GitHub release

Thank you for contributing to NotifyManager! 🎉
