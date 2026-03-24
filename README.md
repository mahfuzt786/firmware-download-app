# BimmerTech Firmware Download Manager

A Symfony 6.4 web application for managing and distributing firmware updates for BimmerTech CarPlay / Android Auto MMI products. Replaces the previous static JSON-based system with a database-backed admin panel.

The application will be available at:
- **Customer page**: [http://localhost:8000/](http://localhost:8000/)
- **Admin panel**: [http://localhost:8000/admin/software-versions](http://localhost:8000/admin/software-versions)

## Table of Contents

- [Features](#features)
- [System Requirements](#system-requirements)
- [Installation & Setup](#installation--setup)
- [SQL Equivalent of data.db](#sql-equivalent-of-datadb)
- [Running the Application](#running-the-application)
- [Usage Guide](#usage-guide)
  - [Customer-Facing Page](#customer-facing-page)
  - [Admin Panel](#admin-panel)
- [Managing Software Versions](#managing-software-versions)
  - [Adding a New Version](#adding-a-new-version)
  - [Updating the Latest Version](#updating-the-latest-version)
  - [Editing a Version](#editing-a-version)
  - [Deleting a Version](#deleting-a-version)
- [API Reference](#api-reference)
- [Product & Hardware Reference](#product--hardware-reference)
- [Project Structure](#project-structure)
- [Troubleshooting](#troubleshooting)

---

## Features

- **Customer Page**: Public page where customers enter their software and hardware versions to check for firmware updates
- **Admin Panel**: Password-protected dashboard for managing all software versions without touching code
- **Database Storage**: All version data stored in SQLite (no external database server required)
- **Identical Behavior**: API responses match the original system exactly (same messages, same download link logic)
- **Grouped Display**: Admin panel organizes versions by product name with collapsible sections
- **Safety Features**: Delete confirmations, CSRF protection, automatic "latest" flag management

---

## System Requirements

- **PHP** 8.1 or higher
- **Composer** 2.x (PHP dependency manager)
- **SQLite** PHP extension (`pdo_sqlite`) — usually enabled by default
- **PHP extensions**: `ctype`, `iconv`, `mbstring`, `xml`, `tokenizer`

On Ubuntu/Debian, install requirements with:
```bash
sudo apt update
sudo apt install php8.1 php8.1-cli php8.1-sqlite3 php8.1-xml php8.1-mbstring php8.1-intl composer
```

---

## Installation & Setup

### 1. Clone or copy the project

```bash
cd /path/to/your/projects
# Copy the firmware-download-app directory to your desired location
```

### 2. Install PHP dependencies

```bash
cd firmware-download-app
composer install
```

### 3. Create the database and schema

```bash
# Create the SQLite database file and tables
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
```

### 4. Load the existing software versions (seed data)

This populates the database with all existing firmware versions from the original JSON file:

```bash
php bin/console doctrine:fixtures:load --no-interaction
```

You should see: `Loaded 116 software version entries.`

### 5. Verify the setup

```bash
# Quick check that the database has data
php bin/console dbal:run-sql "SELECT COUNT(*) as total FROM software_versions"
```

---

## SQL Equivalent of data.db

If you need raw SQL syntax equivalent to `firmware-download-app/var/data.db`, use:

- `data/software_versions_seed.sql`

This file contains:
- `CREATE TABLE software_versions (...)`
- all `INSERT INTO software_versions ...` statements (116 rows)
- index creation statements

### Import into SQLite

```bash
sqlite3 var/data.db < data/software_versions_seed.sql
```

### Import into MySQL/MariaDB (adapt `AUTOINCREMENT` to `AUTO_INCREMENT` if needed)

```bash
mysql -u your_user -p your_database < data/software_versions_seed.sql
```

### Import into PostgreSQL (adapt auto-increment syntax/boolean type if needed)

```bash
psql -U your_user -d your_database -f data/software_versions_seed.sql
```

---

## Running the Application

### Using Symfony's built-in web server (recommended for local development)

```bash
php -S localhost:8000 -t public
```

The application will be available at:
- **Customer page**: [http://localhost:8000/](http://localhost:8000/)
- **Admin panel**: [http://localhost:8000/admin/software-versions](http://localhost:8000/admin/software-versions)

### Using Apache (e.g., with XAMPP)

Point your Apache document root to the `public/` directory, or create a virtual host:

```apache
<VirtualHost *:80>
    ServerName firmware.local
    DocumentRoot /path/to/firmware-download-app/public

    <Directory /path/to/firmware-download-app/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Create a `.htaccess` file in `public/` if it doesn't exist:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
```

---

## Usage Guide

### Customer-Facing Page

**URL**: `http://localhost:8000/`

This is the public page customers use to check for firmware updates:

1. Enter their **Software Version** (e.g., `v3.3.5.mmipri.c` or `3.3.5.mmipri.c`)
2. Enter their **HW Version** (e.g., `CPAA_0123.45.67` or `B_C_0123.45.67`)
3. Click **Check**
4. The system shows:
   - **Download links** if an update is available (ST and/or GD variants)
   - **"Your system is upto date!"** if they have the latest version
   - **Error message** if the version or hardware cannot be identified

### Admin Panel

**URL**: `http://localhost:8000/admin/software-versions`

Navigating to the admin panel redirects to a dedicated login page at `/admin/login`.

**Login credentials**:
- **Username**: `admin`
- **Password**: `admin123`

> ⚠️ **Important**: Change these credentials in `config/packages/security.yaml` before deploying to production.

After logging in, the admin panel shows:
- Summary statistics (total products, versions, latest versions marked)
- All versions grouped by product name in collapsible cards
- LCI products are highlighted with a blue header
- Each version has Edit and Delete buttons
- A **Logout** button in the top navigation bar to end the session

---

## Managing Software Versions

### Adding a New Version

1. Go to the admin panel
2. Click **"Add New Version"**
3. Fill in the form:
   - **Product Name**: Select from the dropdown (e.g., "MMI Prime CIC")
   - **System Version**: Full version with `v` prefix (e.g., `v3.3.8.mmipri.c`)
   - **System Version (Alt)**: Same without `v` prefix (e.g., `3.3.8.mmipri.c`)
   - **General Download Link**: Google Drive folder URL (optional)
   - **ST Firmware Link**: Download link for ST hardware (see table below)
   - **GD Firmware Link**: Download link for GD hardware (see table below)
   - **Is Latest**: Check this if this is the newest version
4. Click **"Create Version"**

### Updating the Latest Version

When a new firmware release comes out:

1. **Add the new version** (see above) and check **"This is the latest version"**
   - The system automatically unmarks the previous latest version for that product
2. **Update the old latest version** (optional): Edit it to add download links if they were previously empty
3. **Repeat** for each product group that received the update (e.g., if v3.3.8 was released for all standard products, you need to add it for MMI Prime CIC, MMI Prime NBT, MMI Prime EVO, MMI PRO CIC, MMI PRO NBT, and MMI PRO EVO)

### Editing a Version

1. Find the version in the admin panel
2. Click the **pencil icon** (✏️) next to it
3. Modify the fields as needed
4. Click **"Save Changes"**

### Deleting a Version

1. Find the version in the admin panel
2. Click the **trash icon** (🗑️) next to it
3. Confirm the deletion in the popup dialog

> ⚠️ If you delete a version marked as "latest", no version will be marked as latest for that product until you mark another one.

---

## API Reference

### POST `/api/software/version`

Check a software version and get firmware download information.

**Request body** (form data):

| Parameter    | Required | Description                           |
|-------------|----------|---------------------------------------|
| `version`   | Yes      | Customer's current software version   |
| `mcuVersion`| No       | MCU version (currently unused)        |
| `hwVersion` | Yes      | Customer's hardware version string    |

**Response** (JSON, always HTTP 200):

```json
// Update available
{
    "versionExist": true,
    "msg": "The latest version of software is v3.3.7 ",
    "link": "https://drive.google.com/...",
    "st": "https://drive.google.com/...",
    "gd": "https://drive.google.com/..."
}

// Already up to date
{
    "versionExist": true,
    "msg": "Your system is upto date!",
    "link": "",
    "st": "",
    "gd": ""
}

// Version not found or invalid hardware
{
    "versionExist": false,
    "msg": "There was a problem identifying your software. Contact us for help.",
    "link": "",
    "st": "",
    "gd": ""
}

// Missing required field
{
    "msg": "Version is required"
}
```

---

## Product & Hardware Reference

### Product Groups

| Product Name        | Hardware Family | HW Type |
|--------------------|----------------|---------|
| MMI Prime CIC      | Standard       | CIC     |
| MMI Prime NBT      | Standard       | NBT     |
| MMI Prime EVO      | Standard       | EVO     |
| MMI PRO CIC        | Standard       | CIC     |
| MMI PRO NBT        | Standard       | NBT     |
| MMI PRO EVO        | Standard       | EVO     |
| LCI MMI Prime CIC  | LCI            | CIC     |
| LCI MMI Prime NBT  | LCI            | NBT     |
| LCI MMI Prime EVO  | LCI            | EVO     |
| LCI MMI PRO CIC    | LCI            | CIC     |
| LCI MMI PRO NBT    | LCI            | NBT     |
| LCI MMI PRO EVO    | LCI            | EVO     |

### HW Version Patterns

| Pattern                                  | Hardware Type       | Download Links |
|-----------------------------------------|--------------------:|----------------|
| `CPAA_XXXX.XX.XX` (optional `_SUFFIX`)  | Standard ST         | ST only        |
| `CPAA_G_XXXX.XX.XX` (optional `_SUFFIX`)| Standard GD         | GD only        |
| `B_C_XXXX.XX.XX`                        | LCI CIC             | ST only        |
| `B_N_G_XXXX.XX.XX`                      | LCI NBT             | GD only        |
| `B_E_G_XXXX.XX.XX`                      | LCI EVO             | GD only        |

### Which Download Links to Provide

| Product Type              | General Link | ST Link | GD Link |
|--------------------------|:------------:|:-------:|:-------:|
| Standard CIC              | ✅           | ✅      | ❌      |
| Standard NBT              | ✅           | ✅      | ✅      |
| Standard EVO              | ✅           | ✅      | ✅      |
| LCI CIC                   | ❌           | ✅      | ❌      |
| LCI NBT                   | ❌           | ❌      | ✅      |
| LCI EVO                   | ❌           | ❌      | ✅      |
| Latest version (any)      | ❌           | ❌      | ❌      |

### Version String Format

```
v{major}.{minor}.{patch}.{product_code}.{hw_suffix}

Examples:
  v3.3.7.mmipri.c     -> MMI Prime CIC, version 3.3.7
  v3.3.7.mmipri.b     -> MMI Prime NBT, version 3.3.7
  v3.3.7.mmipri.e     -> MMI Prime EVO, version 3.3.7
  v3.3.7.mmipro.c     -> MMI PRO CIC, version 3.3.7
  v3.4.4.mmiprilci    -> LCI MMI Prime (all HW types), version 3.4.4
  v3.4.4.mmiprolci    -> LCI MMI PRO (all HW types), version 3.4.4
```

---

## Project Structure

```
firmware-download-app/
├── config/
│   ├── bundles.php              # Registered Symfony bundles
│   ├── routes.yaml              # Route configuration (uses PHP attributes)
│   └── packages/
│       ├── doctrine.yaml        # Database configuration
│       ├── framework.yaml       # Symfony framework settings
│       ├── routing.yaml         # Router configuration
│       ├── security.yaml        # Admin authentication (credentials here)
│       ├── twig.yaml            # Template engine configuration
│       └── validator.yaml       # Form validation settings
├── data/
│   └── softwareversions.json    # Seed data (all original versions)
├── migrations/                  # Doctrine migrations (empty - using schema:create)
├── public/
│   └── index.php                # Application entry point
├── src/
│   ├── Controller/
│   │   ├── Admin/
│   │   │   └── SoftwareVersionController.php  # Admin CRUD operations
│   │   ├── ApiController.php                  # API endpoint (version check)
│   │   └── SoftwareDownloadController.php     # Public page controller
│   ├── DataFixtures/
│   │   └── SoftwareVersionFixtures.php        # Database seeder
│   ├── Entity/
│   │   └── SoftwareVersion.php                # Database entity definition
│   ├── Form/
│   │   └── SoftwareVersionType.php            # Admin form definition
│   ├── Repository/
│   │   └── SoftwareVersionRepository.php      # Database query methods
│   └── Kernel.php                             # Application kernel
├── templates/
│   ├── admin/
│   │   ├── base.html.twig                     # Admin layout template
│   │   └── software_version/
│   │       ├── form.html.twig                 # Create/Edit form template
│   │       └── index.html.twig                # Version listing template
│   ├── base.html.twig                         # Base HTML layout
│   └── software_download/
│       └── index.html.twig                    # Customer-facing page
├── .env                         # Environment configuration
├── .gitignore                   # Git ignore rules
├── composer.json                # PHP dependencies
└── README.md                    # This file
```

---

## Troubleshooting

### "Class not found" errors after installation
```bash
composer dump-autoload
php bin/console cache:clear
```

### Database file permission errors
```bash
chmod 777 var/
chmod 666 var/data.db
```

### "No such table: software_versions"
The schema hasn't been created yet:
```bash
php bin/console doctrine:schema:create
php bin/console doctrine:fixtures:load --no-interaction
```

### Resetting the database completely
```bash
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:schema:create
php bin/console doctrine:fixtures:load --no-interaction
```

### Changing admin credentials
Edit `config/packages/security.yaml` and update the `users` section:
```yaml
users:
    your_username:
        password: 'your_new_password'
        roles: ['ROLE_ADMIN']
```

### API returns empty or unexpected results
1. Verify the database has data: `php bin/console dbal:run-sql "SELECT COUNT(*) FROM software_versions"`
2. Check that the software version string matches exactly (case-insensitive)
3. Verify the HW version matches one of the supported patterns (see table above)
