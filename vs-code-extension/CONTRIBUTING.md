# Develop Legacy Tools for Laravel

The [extension](https://github.com/laravel/vs-code-extension) and [language-server](https://github.com/laravel/lsp) sources are in `vs-code-extension/` and `lsp/`. Use Node 22 or newer for the extension and PHP 8.5 for the language server build.

## Build a VSIX on Apple Silicon

Run these commands at the repository root. Use PHP 8.5 and Composer on PATH for the language-server build.

```sh
mkdir -p lsp/builds vs-code-extension/server
(cd lsp && composer install && php -d phar.readonly=0 server app:build laravel-legacy-lsp --build-version=v0.1.4)
cp lsp/builds/laravel-legacy-lsp vs-code-extension/server/laravel-legacy-lsp.phar
(cd vs-code-extension && npm ci && npx --yes @vscode/vsce@4.1.1 package --no-dependencies --target darwin-arm64)
```

Install the VSIX from the VS Code Extensions menu. Set `LaravelLegacy.serverPhpPath` in User Settings to the PHP 8.5 executable that will run the server archive. Set `LaravelLegacy.phpCommand` in the project workspace to select the app's PHP 7.4 command.

## Custom build with a self-contained server

Build the language-server archive as above. Use the matching [StaticPHP micro archive](https://dl.static-php.dev/static-php-cli/common/) and [static-php-cli release](https://github.com/crazywhalecc/static-php-cli/releases) to combine the archive with a native PHP runtime. For Apple Silicon, pass the extracted macOS arm64 `micro.sfx` to this command from the repository root:

```sh
mkdir -p vs-code-extension/server/darwin-arm64
spc micro:combine lsp/builds/laravel-legacy-lsp --with-micro /absolute/path/to/micro.sfx -I memory_limit=512M -O vs-code-extension/server/darwin-arm64/laravel-legacy-lsp
(cd vs-code-extension && npx --yes @vscode/vsce@4.1.1 package --no-dependencies --target darwin-arm64)
```

The extension prefers the matching native server over the PHAR when both are packaged. For another platform, stage the corresponding binary under `server/<process.platform>-<process.arch>/laravel-legacy-lsp`, add `.exe` on Windows, and use the matching VS Code package target. Use `LARAVEL_LEGACY_LSP_BINARY_PATH` in the extension host's environment to test a custom executable or `.phar` directly.

See `THIRD-PARTY.md` before redistributing a self-contained build.

## Verify legacy projects

Run the smoke check with a bundled executable, a PHP 7.4 CLI, and two disposable stock Laravel applications with dependencies installed. The fixture preparation script adds test routes, Blade views and components, translations, config, and a model for the smoke check.

```sh
cd vs-code-extension
node scripts/prepare-legacy-fixtures.mjs /absolute/path/to/laravel7 /absolute/path/to/laravel8
node scripts/check-legacy.mjs server/darwin-arm64/laravel-legacy-lsp /absolute/path/to/php7.4 /absolute/path/to/laravel7 /absolute/path/to/laravel8
```

The application-side templates are in `lsp/app/Lsp/Data/Templates/`. Run `php -l` on those files with PHP 7.4 when changing them. Leave the language server itself on the modern runtime.

## GitHub releases

Attach the VSIX and its SHA-256 checksum to a GitHub release. The original SVG for the PNG icon is in `artwork/marketplace-icon.svg`.
