# Legacy Tools for Laravel

PHP and Blade editor support for Laravel 7 and 8 projects running PHP 7.4. This independent community fork provides completions, Go to Definition, hover information, and diagnostics for routes, views, Blade components, configuration, translations, middleware, and Eloquent models.

## Compatibility

| Component | Verified in the preview |
| --- | --- |
| VS Code extension host | macOS on Apple Silicon |
| Project PHP | 7.4.33 |
| Laravel | 7.30.7 and 8.83.29 |

Download the Apple Silicon VSIX from [GitHub Releases](https://github.com/thuraaung2493/legacy-tools-for-laravel/releases). It includes a language-server archive that runs on PHP 8.5. Set `LaravelLegacy.serverPhpPath` in VS Code User Settings to your PHP 8.5 executable. The server indexes the application using the project's PHP command. Open the Laravel project root with its Composer dependencies installed. If detection selects another PHP version, configure your PHP 7.4 command in the workspace settings.

```json
{
    "LaravelLegacy.phpCommand": ["/absolute/path/to/php7.4"]
}
```

For Sail, use a command array such as `["./vendor/bin/sail", "php"]`. The application must be able to boot using the selected command. For each legacy workspace, enable one Laravel extension to avoid duplicate completions and diagnostics.

Artisan commands and optional integrations depend on the packages the application has installed. Pint commands invoke the project's Pint binary, which has its own PHP requirements.

## Source and attribution

This extension and its language server are based on the [Laravel VS Code extension](https://github.com/laravel/vs-code-extension) and [Laravel LSP](https://github.com/laravel/lsp). See the included `LICENSE.md` and `THIRD-PARTY.md` for notices.
