# Legacy Tools for Laravel

VS Code tooling for Laravel 7 and 8 projects on PHP 7.4. Verified on Apple Silicon with PHP 7.4.33, Laravel 7.30.7, and Laravel 8.83.29.

## Install the GitHub preview

1. Download the `darwin-arm64.vsix` asset from [GitHub Releases](https://github.com/thuraaung2493/legacy-tools-for-laravel/releases).
2. In VS Code, run **Extensions: Install from VSIX…** and select the download.
3. In VS Code User Settings, set `LaravelLegacy.serverPhpPath` to your PHP 8.5 executable. The GitHub package contains a language-server archive that runs on this PHP installation.
4. Open the Laravel project root. If needed, set `LaravelLegacy.phpCommand` in the workspace to an array with the project's PHP 7.4 command.

Example User Settings on a Mac using Homebrew PHP 8.5:

```json
{
    "LaravelLegacy.serverPhpPath": "/opt/homebrew/opt/php@8.5/bin/php"
}
```

Example project `.vscode/settings.json`:

```json
{
    "LaravelLegacy.phpCommand": ["/absolute/path/to/php7.4"]
}
```

Enable one Laravel extension per legacy workspace to avoid duplicate completions and diagnostics. The language server writes temporary indexing scripts under `storage/framework/cache/` and removes them after indexing.

## Build from source

- [Build a VSIX with the language-server archive or a self-contained native server](vs-code-extension/CONTRIBUTING.md)
- [Extension source](vs-code-extension/) and [language-server source](lsp/)
- [Upstream revisions and third-party notices](vs-code-extension/THIRD-PARTY.md)
