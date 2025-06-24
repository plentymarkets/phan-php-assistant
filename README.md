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
Your local 'plugins/' folder should contain one or more plugins:
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

First, pull the latest image:
```bash
docker pull ghcr.io/plentymarkets/phan-php-assistant:main
```
Then, from the parent folder of plugins/, run the compatibility check:
```bash
docker run --rm -v $(pwd)/plugins:/plugins php-compat-checker php artisan check:compatibility --path=/plugins
```
To also run Rector after Phan succeeds:
```bash
docker run --rm -v $(pwd)/plugins:/plugins php-compat-checker php artisan check:compatibility --path=/plugins --withRector
```
Or run Rector analysis directly:
```bash
docker run --rm -v $(pwd)/plugins:/plugins php-compat-checker php artisan check:refactor --path=/plugins
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
Only actual plugin files (e.g., src/, resources/) are analyzed. SDK is used for symbol resolution only.
```

⚙️ Phan Config: How It Works

If .phan/config.php already exists:

Your custom settings are preserved
directory_list and file_list are updated automatically
Unused default use statements are removed
If .phan/config.php is missing:

A new config is generated from a template and updated automatically
You do not need to create directory_list.php or file_list.php manually.

⚙️ Rector Config: How It Works

If you pass --withRector or run check:refactor:

Rector scans src/ and resources/ for each plugin
Uses a shared rector.php config from the app root
Does not modify files (uses --dry-run mode by default)
To enable file modifications, change the service to remove --dry-run.

📤 CI Integration (Optional)

Example GitHub Actions step:
```bash
- name: Check PHP 8.2 Compatibility
  run: |
    docker pull ghcr.io/plentymarkets/phan-php-assistant:main
    docker run --rm -v $(pwd)/plugins:/plugins php-compat-checker php artisan check:compatibility --path=/plugins
```
🧪 Troubleshooting

✅ Class not found? → SDK is cloned automatically; ensure plugin paths are correct

⚠️ Undeclared exception type? → Stub it in your plugin or SDK fork

🔄 Want Rector to auto-fix files? → Remove --dry-run in the Rector service command

❌ Command not found? → Always prefix with php artisan inside the container

