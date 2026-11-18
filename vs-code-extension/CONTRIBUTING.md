# Develop Legacy Tools for Laravel

The [extension](https://github.com/laravel/vs-code-extension) and [language-server](https://github.com/laravel/lsp) sources are in `vs-code-extension/` and `lsp/`. Use Node 22 or newer for the extension and PHP 8.5 for the language server build.

## Build and package on Apple Silicon

Run the commands from the repository root. The first command builds the server PHAR with the repository's locked Composer dependencies.

```sh
(cd lsp && composer install && php -d phar.readonly=0 server app:build laravel-legacy-lsp --build-version=v0.1.3)
(cd vs-code-extension && npm ci)
mkdir -p vs-code-extension/server/darwin-arm64
spc micro:combine lsp/builds/laravel-legacy-lsp --with-micro /absolute/path/to/micro.sfx -I memory_limit=512M -O vs-code-extension/server/darwin-arm64/laravel-legacy-lsp
(cd vs-code-extension && npx --yes @vscode/vsce@4.1.1 package --pre-release --no-dependencies --target darwin-arm64)
```

Use the PHP 8.5.8 macOS arm64 [static runtime](https://dl.static-php.dev/static-php-cli/common/php-8.5.8-micro-macos-aarch64.tar.gz) and [static-php-cli](https://github.com/crazywhalecc/static-php-cli/releases) to make the bundled executable. For another platform, stage the equivalent executable under `server/<process.platform>-<process.arch>/laravel-legacy-lsp`, add `.exe` on Windows, and select the matching VS Code target when packaging.

Use `LARAVEL_LEGACY_LSP_BINARY_PATH` in the extension host's environment to point a development session at a custom executable.

## Verify legacy projects

Run the smoke check with a bundled executable, a PHP 7.4 CLI, and two disposable stock Laravel applications with dependencies installed. The fixture preparation script adds test routes, Blade views and components, translations, config, and a model for the smoke check.

```sh
cd vs-code-extension
node scripts/prepare-legacy-fixtures.mjs /absolute/path/to/laravel7 /absolute/path/to/laravel8
node scripts/check-legacy.mjs server/darwin-arm64/laravel-legacy-lsp /absolute/path/to/php7.4 /absolute/path/to/laravel7 /absolute/path/to/laravel8
```

The application-side templates are in `lsp/app/Lsp/Data/Templates/`. Run `php -l` on those files with PHP 7.4 when changing them. Leave the language server itself on the modern runtime.


## Marketplace pre-release

The development manifest uses `local-preview` as the publisher. Set `publisher` to the registered Marketplace publisher ID. The extension ID for the published build is `<publisher>.legacy-tools-for-laravel`.

Review the bundled runtime's redistribution terms in `THIRD-PARTY.md`. Build the final VSIX with `--pre-release --target darwin-arm64` and verify it in a clean editor profile before uploading. The original SVG for the PNG icon is in `artwork/marketplace-icon.svg`.
