# WordPress Installation Tool

A command-line tool to download and install any version of WordPress with custom folder naming. Perfect for developers who need to quickly set up WordPress instances.

---

## Features

- **Custom Folder Names**: Specify a custom folder name or use the default version-based name.
- **Version-Specific Downloads**:
  - Download specific WordPress versions (`v=major`, `v=major.minor`, or `v=major.minor.path`).
  - Automatically constructs the correct download URL based on the version format.
- **Cross-Platform**: Works on both Windows and Unix-based systems.
- **Error Handling**: Validates folder names, version formats, and checks if the specified version exists.

---

## Installation


**Install via Composer**

```bash
composer global require amzil-ayoub/wp-cli
```

## Usage

**Basic Usage**

```bash
wp
```

This will:

- Fetch the latest WordPress version.

- Download and extract it into a folder named wordpress_{version}_{timestamp}.

**Custom Folder Name**

Specify a custom folder name:

```bash
wp my-project
```

This will:

- Fetch the latest WordPress version.

- Download and extract it into a folder named my-project.

**Specific WordPress Version**

Download a specific version of WordPress:

Major Version Only
```bash
wp v=6
```

Downloads: https://wordpress.org/wordpress-6.0.zip

Major and Minor Version

```bash
wp v=6.2
```

Downloads: https://wordpress.org/wordpress-6.2.zip

Full Version with Patch

```bash
wp v=6.2.1
```
Downloads: https://wordpress.org/wordpress-6.2.1.zip

**Custom Folder Name + Specific Version**

Combine a custom folder name with a specific version:

```bash
wp my-project v=6.2.1
```

This will:

- Download WordPress 6.2.1.

- Extract it into a folder named my-project.

## Examples

**Example 1: Default Behavior**

```bash
wp
```
**Output:**

```bash
================================
 WordPress Installation Tool
================================

Fetching latest WordPress version...
⬇Downloading WordPress 6.7.2...
Extracting files to: wordpress_6.7.2_20250317020433

Success! WordPress 6.7.2 installed at:
/path/to/wordpress_6.7.2_20250317020433
```

**Example 2: Custom Folder + Specific Version**

```bash
wp my-site v=6.2
```

**Output:**

```bash
================================
 WordPress Installation Tool
================================

⬇Downloading WordPress 6.2...
Extracting files to: my-site

Sccess! WordPress 6.2 installed at:
/path/to/my-site
```

**Example 3: Invalid Version**

```bash
wp v=6.2.99
```

**Output:**

```bash
================================
 WordPress Installation Tool
================================

Error: WordPress version 6.2.99 does not exist.
```

## Error Handling

The tool provides clear error messages for common issues:

- Invalid folder name: Only alphanumeric, hyphens, and underscores are allowed.

- Invalid version format: Must be major, major.minor, or major.minor.path.

- Version does not exist: Checks if the specified WordPress version is available for download.

- Directory already exists: Prevents overwriting existing directories.
- 
## Development

**Requirements**

- PHP 8.0+

- Composer

- ext-zip PHP extension

