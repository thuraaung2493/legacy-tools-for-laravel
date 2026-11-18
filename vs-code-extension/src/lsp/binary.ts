import * as fs from "fs";
import * as vscode from "vscode";

let lspBinaryPath: string | undefined;

export function getLspBinaryPath(): string | undefined {
    return lspBinaryPath;
}

export function setLspBinaryPath(context: vscode.ExtensionContext): void {
    const target = `${process.platform}-${process.arch}`;
    const suffix = process.platform === "win32" ? ".exe" : "";
    const override = process.env.LARAVEL_LEGACY_LSP_BINARY_PATH;
    const binary =
        override ||
        context.asAbsolutePath(`server/${target}/laravel-legacy-lsp${suffix}`);

    if (!fs.existsSync(binary)) {
        throw new Error(
            override
                ? `Server executable does not exist at ${override}.`
                : `This VSIX has no server executable for ${target}.`,
        );
    }

    lspBinaryPath = binary;
}
