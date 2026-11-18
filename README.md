# Legacy Tools for Laravel

Independent VS Code tooling for Laravel 7 and 8 projects running PHP 7.4. The Apple Silicon preview was verified with PHP 7.4.33, Laravel 7.30.7, and Laravel 8.83.29.

- [Extension source and workspace settings](vs-code-extension/)
- [Language server source](lsp/)
- [Build and verification](vs-code-extension/CONTRIBUTING.md)
- [Upstream revisions and runtime notices](vs-code-extension/THIRD-PARTY.md)

The preview bundles a native language server when built, and uses the project's configured PHP command for indexing. Temporary indexing scripts are written under `storage/framework/cache/` and removed after indexing.
