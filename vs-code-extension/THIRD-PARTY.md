# Third-party notices

- The editor extension is based on [Laravel's VS Code extension](https://github.com/laravel/vs-code-extension/commit/952d31f187fc3515e7e6d99ba24b7f82e8e12af8) and distributed with its MIT license in `LICENSE.md`.
- The language server is based on [Laravel LSP](https://github.com/laravel/lsp/commit/a174d3fb1e738728b7c5f17f330c06f876f613d3). Laravel LSP declares the MIT license in its Composer metadata. Composer dependencies retain their license files inside the server PHAR.
- Versions and notices for the libraries embedded in the extension's JavaScript bundle are in `licenses/npm/`.

## Optional runtime for custom native builds

A self-contained Apple Silicon build can use [StaticPHP's PHP 8.5.8 macOS arm64 micro archive](https://dl.static-php.dev/static-php-cli/common/php-8.5.8-micro-macos-aarch64.tar.gz). The PHP source notice is in `PHP-LICENSE.txt`. The archive SHA-256 is `141cc12c5f2bfbe2862669545ea18be1f5b5b33ae95b7b8ad767afe75e6e9f59`. Its upstream source is [StaticPHP](https://github.com/crazywhalecc/static-php-cli).

The repository includes the [notices StaticPHP publishes for its common builds](https://dl.static-php.dev/static-php-cli/common/license/) in `licenses/static-php-common/`. Those notices cover builds across platforms. A probe of this exact binary identified GNU libiconv and GMP 6.3.0. It links both statically. Review the library-specific redistribution terms and any source or relinking requirements for the exact runtime before public distribution.
