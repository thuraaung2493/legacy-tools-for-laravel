import assert from "node:assert/strict";
import { spawn, execFileSync } from "node:child_process";
import { readFileSync } from "node:fs";
import { resolve } from "node:path";
import { pathToFileURL } from "node:url";
import {
    createMessageConnection,
    StreamMessageReader,
    StreamMessageWriter,
} from "vscode-jsonrpc/node.js";

const [serverPath, projectPhpPath, ...projects] = process.argv.slice(2);

if (!serverPath || !projectPhpPath || projects.length === 0) {
    throw new Error(
        "Usage: node scripts/check-legacy.mjs <language-server> <php-7.4> <fixture> [fixture...]",
    );
}

const server = resolve(serverPath);
const php = resolve(projectPhpPath);

for (const directory of projects) {
    await checkProject(resolve(directory));
}

async function checkProject(cwd) {
    const version = execFileSync(php, ["artisan", "--version"], {
        cwd,
        encoding: "utf8",
    }).trim();
    const rootUri = pathToFileURL(cwd).href;
    const child = spawn(server, ["lsp"], {
        cwd,
        stdio: ["pipe", "pipe", "inherit"],
    });
    const connection = createMessageConnection(
        new StreamMessageReader(child.stdout),
        new StreamMessageWriter(child.stdin),
    );

    connection.onRequest("client/registerCapability", () => null);
    const controllerFixture = resolve(
        cwd,
        "routes/legacy-controller-actions.php",
    );
    const controllerUri = pathToFileURL(controllerFixture).href;
    const controllerDiagnostics = Promise.withResolvers();
    connection.onNotification("textDocument/publishDiagnostics", (event) => {
        if (event.uri === controllerUri) {
            controllerDiagnostics.resolve(event.diagnostics);
        }
    });
    connection.listen();

    const timeout = setTimeout(() => {
        child.kill();
        connection.dispose();
    }, 30000);

    try {
        const initialized = await connection.sendRequest("initialize", {
            rootUri,
            processId: process.pid,
            capabilities: {},
            initializationOptions: {
                phpCommand: [php],
                pestGenerateDocBlocks: false,
            },
        });
        assert.deepEqual(initialized.laravel.phpCommand, [php]);
        assert.equal(initialized.capabilities.definitionProvider, true);
        await connection.sendNotification("initialized", {});

        const data = (name) => connection.sendRequest("laravel/data", { name });

        for (const [name, expected] of Object.entries({
            configs: "legacy.label",
            views: "legacy-editor.blade.php",
            bladeComponents: "legacy-panel",
            translations: "legacy.editor",
            auth: "User",
            middleware: "Authenticate",
            paths: "lang_path",
            appBindings: "router",
            tests: "ExampleTest.php",
        })) {
            assert.ok(
                JSON.stringify(await data(name)).includes(expected),
                name + " should contain " + expected,
            );
        }

        const routes = await data("routes");
        const controllerRoute = routes.find(
            ({ name }) => name === "legacy.controller",
        );
        assert.equal(
            controllerRoute.filename,
            "app/Http/Controllers/LegacyEditorController.php",
        );

        const models = await data("models");
        const entry = models["App\\LegacyEntry"];
        assert.ok(entry.attributes.some(({ name }) => name === "legacy_title"));
        assert.ok(entry.scopes.some(({ name }) => name === "visible"));
        assert.ok(
            entry.relations.some(
                ({ name, type }) => name === "owner" && type === "BelongsTo",
            ),
        );

        const probe = pathToFileURL(resolve(cwd, "legacy-probe.php")).href;
        const text = "<?php\nroute('legacy.');\nview('legacy-editor');\n";

        await connection.sendNotification("textDocument/didOpen", {
            textDocument: { uri: probe, languageId: "php", version: 1, text },
        });
        const complete = await connection.sendRequest(
            "textDocument/completion",
            {
                textDocument: { uri: probe },
                position: { line: 1, character: 14 },
            },
        );
        assert.ok(complete.some(({ label }) => label === "legacy.editor"));

        const definitions = await connection.sendRequest(
            "textDocument/definition",
            {
                textDocument: { uri: probe },
                position: { line: 2, character: 10 },
            },
        );
        assert.ok(
            definitions.some(({ targetUri }) =>
                targetUri.endsWith("/resources/views/legacy-editor.blade.php"),
            ),
        );

        const controllerText =
            readFileSync(controllerFixture, "utf8") +
            "\nRoute::get('/missing-controller', 'LegacyListController@missing');\n";
        await connection.sendNotification("textDocument/didOpen", {
            textDocument: {
                uri: controllerUri,
                languageId: "php",
                version: 1,
                text: controllerText,
            },
        });
        const diagnosticTimeout = setTimeout(
            () =>
                controllerDiagnostics.reject(
                    new Error("Controller diagnostics timed out"),
                ),
            10000,
        );
        try {
            const diagnostics = (await controllerDiagnostics.promise).filter(
                ({ code }) => code === "controllerAction",
            );
            assert.deepEqual(
                diagnostics.map(({ message, source }) => ({ message, source })),
                [
                    {
                        message:
                            "Controller/Method [LegacyListController@missing] not found.",
                        source: "Legacy Tools for Laravel",
                    },
                ],
            );
        } finally {
            clearTimeout(diagnosticTimeout);
        }

        const actionLines = controllerText.split("\n");
        for (const [path, section] of [
            ["/registered-client-lists", "Admin"],
            ["/client-reports", "Reports"],
        ]) {
            const line = actionLines.findIndex((text) => text.includes(path));
            const character =
                actionLines[line].indexOf("LegacyListController") + 3;
            const links = await connection.sendRequest(
                "textDocument/definition",
                {
                    textDocument: { uri: controllerUri },
                    position: { line, character },
                },
            );
            assert.deepEqual(
                links.map(({ targetUri }) => targetUri),
                [
                    pathToFileURL(
                        resolve(
                            cwd,
                            `app/Http/Controllers/${section}/LegacyListController.php`,
                        ),
                    ).href,
                ],
            );
        }

        await connection.sendRequest("shutdown");
        await connection.sendNotification("exit");
        console.log(
            version +
                " passed indexing, completion, definitions, and controller diagnostics",
        );
    } finally {
        clearTimeout(timeout);
        connection.dispose();
        child.kill();
    }
}
