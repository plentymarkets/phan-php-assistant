# 🐘 PHP 8.2 Compatibility Checker

This CLI tool statically analyzes PHP plugins to check for compatibility with **PHP 8.2**, using [Phan](https://github.com/phan/phan). It’s packaged as a lightweight Docker container for easy use in local development and CI environments.

---

## 📦 Features

- ✅ Detects removed functions, syntax errors, and deprecated features
- 🔍 Analyzes plugin source code (`src/`, `resources/`, etc.)
- 🧠 Uses the official **Plenty SDK** (via git clone) to resolve unknown class references
- 🪄 Automatically generates or updates `.phan/config.php` per plugin
- 🧩 Keeps user-defined Phan settings (merges custom config with required paths)
- 🐳 Runs in an isolated Docker environment
- 📄 Outputs detailed compatibility reports

---

## 📁 Expected Folder Structure


```Your local 'plugins/' folder should contain one or more plugins:
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



> Each plugin **must include** a `plugin.json` file to be considered valid.

---

## 🐳 Run the Compatibility Checker

First, make sure you pull the latest image:

```bash
docker pull ghcr.io/plentymarkets/phan-php-assistant:main
```
Then, from the parent folder of plugins/, run the compatibility check:
```bash
docker run --rm -v $(pwd)/plugins:/plugins php-compat-checker check:compatibility --path=/plugins
```
This will:

Clone the Plenty SDK (only once)
Link the SDK into each plugin for symbol resolution (not for compatibility check itself)
Merge .phan/config.php per plugin: preserving user config, adding required directory_list and file_list
Run static analysis using Phan
Display compatibility results per plugin

✅ Sample Output
```bash
==== [plugin-sdk-test] ====
❌ Incompatible
src/Controllers/TestController.php:10 PhanUndeclaredExtendedClass Class extends undeclared class \Plenty\Plugin\Controller
src/Providers/PluginRouteServiceProvider.php:16 PhanUndeclaredTypeThrowsType @throws type of map has undeclared type \Plenty\Plugin\Routing\Exceptions\RouteReservedException
```
Only actual plugin files (src/, resources/) are analyzed. SDK is used only for reference resolution, not scanned directly.

⚙️ Phan Config: How It Works

If .phan/config.php already exists in a plugin:
Its settings are preserved
directory_list and file_list are automatically extended with detected paths
Any redundant default use statements are stripped (e.g., unused use Phan\Issue)
If no config exists:
A fresh .phan/config.php will be generated based on config.sample.php, then updated with paths
You do not need to create directory_list.php or file_list.php manually.

📤 CI Integration (Optional)

- name: Check PHP 8.2 Compatibility
```bash
  run: |
    docker pull ghcr.io/plentymarkets/phan-php-assistant:main
    docker run --rm -v $(pwd)/plugins:/plugins php-compat-checker check:compatibility --path=/plugins
```

🧪 Troubleshooting

✅ Class not found? Ensure Plenty SDK is cloned (done automatically).
⚠️ PhanUndeclaredTypeThrowsType? You can stub the exception class if not present in SDK.
⛔ False positive? Check your config’s file_list and symbolic links.
