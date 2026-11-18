import assert from "node:assert/strict";
import { mkdirSync, readFileSync, writeFileSync } from "node:fs";
import { dirname, resolve } from "node:path";

const projects = process.argv.slice(2);
assert.ok(
    projects.length > 0,
    "Provide the paths to disposable Laravel 7 or 8 projects.",
);

for (const directory of projects) {
    const root = resolve(directory);
    const manifest = JSON.parse(
        readFileSync(resolve(root, "composer.json"), "utf8"),
    );
    const framework = manifest.require["laravel/framework"] ?? "";
    const major = framework.match(/^\^?([78])\./)?.[1];
    assert.ok(major, `Expected a Laravel 7 or 8 fixture at ${root}`);

    const ownedBy = major === "7" ? "User" : "Models\\User";
    const namespace = major === "7" ? "" : "App\\Http\\Controllers\\";
    const files = {
        "routes/legacy-controller-actions.php": `<?php

use Illuminate\\Support\\Facades\\Route;

Route::namespace('${namespace}Admin')->prefix('dashboard')->group(function () {
    Route::get('/registered-client-lists', 'LegacyListController@index');
});

Route::namespace('${namespace}Reports')->group(function () {
    Route::get('/client-reports', 'LegacyListController@index');
});
`,
        "config/legacy.php": "<?php\nreturn ['label' => 'Legacy'];\n",
        "resources/views/legacy-editor.blade.php": "<x-legacy-panel />\n",
        "resources/views/components/legacy-panel.blade.php":
            "@props(['title' => 'Panel'])\n<div>{{ $title }}</div>\n",
        "resources/lang/en/legacy.php":
            "<?php\nreturn ['editor' => 'Legacy editor'];\n",
        "legacy-probe.php":
            "<?php\nroute('legacy.');\nview('legacy-editor');\n",
        "app/LegacyEntry.php": `<?php
namespace App;

use Illuminate\\Database\\Eloquent\\Model;

class LegacyEntry extends Model
{
    protected $fillable = ['legacy_title'];
    protected $casts = ['visible' => 'bool'];

    public function scopeVisible($query, bool $active = true)
    {
        return $query->where('visible', $active);
    }

    public function owner()
    {
        return $this->belongsTo(${ownedBy}::class);
    }
}
`,
        "app/Http/Controllers/LegacyEditorController.php": `<?php
namespace App\\Http\\Controllers;

class LegacyEditorController extends Controller
{
    public function show()
    {
        return view('legacy-editor');
    }
}
`,
        "app/View/Components/LegacyBadge.php": `<?php
namespace App\\View\\Components;

use Illuminate\\View\\Component;

class LegacyBadge extends Component
{
    public string $title = 'Default badge';

    public function render()
    {
        return '<span>{{ $title }}</span>';
    }
}
`,
    };
    for (const section of ["Admin", "Reports"]) {
        files[`app/Http/Controllers/${section}/LegacyListController.php`] =
            `<?php

namespace App\\Http\\Controllers\\${section};

use App\\Http\\Controllers\\Controller;

class LegacyListController extends Controller
{
    public function index()
    {
        return view('legacy-editor');
    }
}
`;
    }
    for (const [name, content] of Object.entries(files)) {
        const file = resolve(root, name);
        mkdirSync(dirname(file), { recursive: true });
        writeFileSync(file, content);
    }

    const routeFile = resolve(root, "routes/web.php");
    let routes = readFileSync(routeFile, "utf8");
    if (!routes.includes("->name('legacy.editor')")) {
        routes +=
            "\n\\Illuminate\\Support\\Facades\\Route::get('/legacy-editor', function () { return view('legacy-editor'); })->name('legacy.editor');\n";
    }

    if (!routes.includes("->name('legacy.controller')")) {
        routes +=
            "\n\\Illuminate\\Support\\Facades\\Route::get('/legacy-controller', [\\App\\Http\\Controllers\\LegacyEditorController::class, 'show'])->name('legacy.controller');\n";
    }

    const include = "require __DIR__ . '/legacy-controller-actions.php';";
    if (!routes.includes(include)) {
        routes += "\n" + include + "\n";
    }

    writeFileSync(routeFile, routes);
}
