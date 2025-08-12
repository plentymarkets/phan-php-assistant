# 🐘 PHP 8.2 Compatibility Checker

This CLI tool statically analyzes PHP plugins to check for compatibility with **PHP 8.2**, using [Phan](https://github.com/phan/phan) and optionally provides refactor suggestions via [Rector](https://github.com/rectorphp/rector). It’s packaged as a lightweight Docker container for easy use in local development and CI environments.

---

## 📦 Features

- ✅ Detects removed functions, syntax errors, and deprecated features using **Phan**
- 🛠️ Provides automatic upgrade suggestions with **Rector** (e.g. readonly properties, type declarations)
- 🔍 Analyzes plugin source code (`src/`, `resources/`, etc.)
- 🧠 Uses the official **Plenty SDK** (via git clone) to resolve unknown class references
- 🪄 Automatically generates or updates `.phan/config.php` per plugin
- 🧩 Merges custom user-defined Phan settings with required config
- 🐳 Runs inside an isolated Docker container
- 📄 Outputs detailed compatibility and refactor reports

---

## 📁 Expected Folder Structure

```text
plugins/
├── plugin-1/
│   ├── plugin.json
│   ├── composer.json
│   ├── .phan/
│   │   └── config.php 
│   └── src/
├── plugin-2/
│   └── ...
```
Each plugin must include a plugin.json file to be considered valid.
🐳 Run the Compatibility Checker


```bash
# Standard pull
docker pull ghcr.io/plentymarkets/phan-php-assistant:main
```
If you get:
```bash 
no matching manifest for linux/amd64 in the manifest list entries
```
pull the ARM64 variant explicitly:
```bash 
docker pull --platform=linux/arm64 ghcr.io/plentymarkets/phan-php-assistant:main
```
First, pull the latest image:
```bash
docker pull ghcr.io/plentymarkets/phan-php-assistant:main
Then, run the compatibility check by mounting your plugin folder into the container at /plugins.
```
✅ Option 1 – You're in the project root, and plugins/ is a subfolder:
```bash
docker run --rm -v $(pwd)/plugins:/plugins ghcr.io/plentymarkets/phan-php-assistant:main php artisan check:compatibility
```
✅ Option 2 – You’re anywhere on disk and want to mount a full absolute path:
```bash
docker run --rm -v /absolute/path/to/plugins:/plugins ghcr.io/plentymarkets/phan-php-assistant:main php artisan check:compatibility
```
📌 Important: The path on the left (host) can be relative or absolute,
but the container always expects the plugins/ directory to be available at /plugins.
➕ To also run Rector after Phan passes:
```bash
docker run --rm -v /absolute/path/to/plugins:/plugins ghcr.io/plentymarkets/phan-php-assistant:main php artisan check:compatibility --withRector
```
🔁 Or run only Rector analysis directly:
```bash
docker run --rm -v /absolute/path/to/plugins:/plugins ghcr.io/plentymarkets/phan-php-assistant:main php artisan check:rector
```
✅ Sample Output
```bash
==== [plugin-sdk-test] ====
❌ Incompatible
src/Controllers/TestController.php:10 PhanUndeclaredExtendedClass Class extends undeclared class \Plenty\Plugin\Controller
src/Providers/PluginRouteServiceProvider.php:16 PhanUndeclaredTypeThrowsType @throws type of map has undeclared type \Plenty\Plugin\Routing\Exceptions\RouteReservedException

==== [plugin-sdk-test] Rector ====
✅ Rector completed successfully

1 file with changes
--------------------
src/Controllers/TestController.php
- Added readonly property suggestion
Only actual plugin files (e.g., src/, resources/) are analyzed.
SDK is used for symbol resolution only.
```
⚙️ Phan Config: How It Works

If .phan/config.php already exists:

Your custom settings are preserved

directory_list and file_list are updated automatically

If .phan/config.php is missing:

A new config is generated and updated automatically

✅ You do not need to manually create directory_list.php or file_list.php.

---

⚙️ Rector Config: How It Works

Triggered with --withRector or check:rector

Scans src/ and resources/ in each plugin

Uses shared rector.php config from the app root

📤 CI Integration (Optional)

Example GitHub Actions step:
```bash
- name: Check PHP 8.2 Compatibility
  run: |
    docker pull ghcr.io/plentymarkets/phan-php-assistant:main
    docker run --rm -v $(pwd)/plugins:/plugins ghcr.io/plentymarkets/phan-php-assistant:main php artisan check:compatibility
```

---

🧪 Troubleshooting

Issue	Solution

✅ Class not found	SDK is auto-cloned; ensure correct plugin structure

⚠️ Undeclared exception type	Add a stub class or extend the SDK

🔄 Want Rector to auto-fix files?	Remove --dry-run in RectorRefactorService

❌ Command not found	Always use php artisan inside the container
