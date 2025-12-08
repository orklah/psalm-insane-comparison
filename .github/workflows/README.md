# GitHub Actions CI

This directory contains GitHub Actions workflows for automated testing.

## Workflows

### tests.yml
Runs the PHPUnit test suite on multiple PHP versions (7.3, 7.4, 8.0, 8.1, 8.2, 8.3) on every push and pull request to the master branch.

**What it does:**
- Sets up PHP with the required extensions
- Validates composer.json
- Installs dependencies with caching
- Runs the full test suite

**Status:** The workflow will run automatically on:
- Push to master branch
- Pull requests targeting master branch

You can view the test results in the "Actions" tab of the GitHub repository.
