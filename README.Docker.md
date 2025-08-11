# Docker Development Environment

This document explains how to use the provided `Dockerfile` to set up a PHP 8.2 development and testing environment for the DDD Foundation package.

## Overview

The `Dockerfile` creates a containerized environment with:
- **PHP 8.2 CLI** - Latest stable PHP version with CLI interface
- **Composer** - PHP dependency manager
- **Required Extensions** - ZIP extension for handling compressed archives
- **Development Dependencies** - All dev dependencies including testing frameworks

## Dockerfile Breakdown

```dockerfile
FROM php:8.2-cli
```
Uses the official PHP 8.2 CLI image as the base. This provides a lightweight PHP environment perfect for running tests and development tasks.

```dockerfile
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
```
Installs the latest version of Composer from the official Composer Docker image using multi-stage build approach.

```dockerfile
COPY . /app
WORKDIR /app
```
Copies the entire project into the `/app` directory and sets it as the working directory.

```dockerfile
RUN apt-get update -y \ 
    && apt-get install -y \
    libzip-dev \
    unzip \
    && docker-php-ext-install zip \
    && composer install --dev
```
- Updates the package list
- Installs ZIP development libraries and unzip utility
- Enables PHP ZIP extension (required by Composer and many packages)
- Installs all project dependencies including development dependencies

```dockerfile
CMD ["php", "-a"]
```
Sets the default command to start PHP in interactive mode.

## Building the Docker Image

### Basic Build

Build the Docker image from the project root:

```bash
docker build -t ddd-foundation:latest .
```

### Build with Custom Tag

```bash
docker build -t ddd-foundation:php8.2 .
```

### Build with Build Arguments (if needed)

```bash
docker build --build-arg PHP_VERSION=8.2 -t ddd-foundation:latest .
```

## Running the Test Suite

### Run All Tests with Pest

```bash
docker run --rm -v $(pwd):/app ddd-foundation:latest vendor/bin/pest
```

### Run Tests with PHPUnit

```bash
docker run --rm -v $(pwd):/app ddd-foundation:latest vendor/bin/phpunit
```

### Run Tests with Coverage

```bash
docker run --rm -v $(pwd):/app ddd-foundation:latest vendor/bin/pest --coverage
```

### Run Specific Test Files

```bash
# Run a specific test file
docker run --rm -v $(pwd):/app ddd-foundation:latest vendor/bin/pest tests/Unit/ValueObjects/IdTest.php

# Run tests in a specific directory
docker run --rm -v $(pwd):/app ddd-foundation:latest vendor/bin/pest tests/Unit/
```

## Code Quality Tools

### Static Analysis with PHPStan

```bash
docker run --rm -v $(pwd):/app ddd-foundation:latest vendor/bin/phpstan analyse
```

### Code Formatting with Laravel Pint

```bash
# Check code style
docker run --rm -v $(pwd):/app ddd-foundation:latest vendor/bin/pint --test

# Fix code style
docker run --rm -v $(pwd):/app ddd-foundation:latest vendor/bin/pint
```

### Architecture Testing

```bash
docker run --rm -v $(pwd):/app ddd-foundation:latest vendor/bin/pest --filter=Architecture
```

## Interactive Development

### Start Interactive PHP Shell

```bash
docker run --rm -it -v $(pwd):/app ddd-foundation:latest php -a
```

### Start Container with Shell Access

```bash
docker run --rm -it -v $(pwd):/app ddd-foundation:latest bash
```

### Run Custom Commands

```bash
# Install additional dependencies
docker run --rm -v $(pwd):/app ddd-foundation:latest composer require --dev package/name

# Update dependencies
docker run --rm -v $(pwd):/app ddd-foundation:latest composer update

# Dump autoload
docker run --rm -v $(pwd):/app ddd-foundation:latest composer dump-autoload
```

## Docker Compose Alternative

For more complex setups, create a `docker-compose.yml`:

```yaml
version: '3.8'

services:
  ddd-foundation:
    build: .
    volumes:
      - .:/app
    working_dir: /app
    command: tail -f /dev/null  # Keep container running
    
  test:
    build: .
    volumes:
      - .:/app
    working_dir: /app
    command: vendor/bin/pest
    
  phpstan:
    build: .
    volumes:
      - .:/app
    working_dir: /app
    command: vendor/bin/phpstan analyse
```

Then run:

```bash
# Run tests
docker-compose run --rm test

# Run static analysis
docker-compose run --rm phpstan

# Interactive development
docker-compose run --rm ddd-foundation bash
```

## Continuous Integration

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Build Docker image
      run: docker build -t ddd-foundation .
      
    - name: Run tests
      run: docker run --rm -v ${{ github.workspace }}:/app ddd-foundation vendor/bin/pest
      
    - name: Run static analysis
      run: docker run --rm -v ${{ github.workspace }}:/app ddd-foundation vendor/bin/phpstan analyse
```

## Development Workflow

### 1. Build the Image

```bash
docker build -t ddd-foundation .
```

### 2. Run Tests During Development

```bash
# Watch for changes and run tests (if you have a file watcher)
docker run --rm -v $(pwd):/app ddd-foundation:latest vendor/bin/pest --watch
```

### 3. Validate Code Quality

```bash
# Run all quality checks
docker run --rm -v $(pwd):/app ddd-foundation:latest bash -c "
  vendor/bin/pest && 
  vendor/bin/phpstan analyse && 
  vendor/bin/pint --test
"
```

## Troubleshooting

### Permission Issues

If you encounter permission issues with volumes:

```bash
# Fix ownership (Linux/macOS)
docker run --rm -v $(pwd):/app ddd-foundation:latest chown -R $(id -u):$(id -g) /app
```

### Memory Issues

If tests fail due to memory limits:

```bash
docker run --rm -v $(pwd):/app ddd-foundation:latest php -d memory_limit=512M vendor/bin/pest
```

### Debugging

Enable Xdebug for debugging:

```bash
docker run --rm -v $(pwd):/app -e XDEBUG_MODE=debug ddd-foundation:latest vendor/bin/pest
```

## Performance Optimization

### Using Docker Layer Caching

To optimize build times, create a `.dockerignore` file:

```
vendor/
node_modules/
.git/
.github/
tests/coverage/
*.md
.env*
```

### Multi-stage Build (Advanced)

For production optimization, consider a multi-stage build:

```dockerfile
# Development stage
FROM php:8.2-cli as development
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
RUN apt-get update && apt-get install -y libzip-dev unzip
RUN docker-php-ext-install zip
COPY . /app
WORKDIR /app
RUN composer install --dev

# Production stage
FROM php:8.2-cli as production
COPY --from=development /app/vendor /app/vendor
COPY . /app
WORKDIR /app
```

## Environment Variables

You can customize the environment using variables:

```bash
# Set PHP configuration
docker run --rm -v $(pwd):/app -e PHP_MEMORY_LIMIT=256M ddd-foundation:latest vendor/bin/pest

# Set application environment
docker run --rm -v $(pwd):/app -e APP_ENV=testing ddd-foundation:latest vendor/bin/pest
```

This Docker setup provides a consistent, isolated environment for developing and testing the DDD Foundation package across different systems and CI/CD pipelines.