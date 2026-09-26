# URL Rewrite Generator for Magento 2

Regenerate Magento's category and product URL rewrites from the CLI — fast, scriptable, and safe to run on stores with large catalogs. When rewrites drift after imports, category moves, or a botched migration, this module rebuilds them without touching the admin or reindexing the whole store.

Everything runs through `bin/magento`, so it slots straight into deploy pipelines and cron.

## Why another URL rewrite tool

Magento regenerates rewrites lazily and offers no first-class "rebuild these now" command. This module fills the gap:

- **Targeted regeneration** — rebuild rewrites for the whole catalog, a single store, or a specific list of entity IDs, instead of an all-or-nothing reindex.
- **Category-path product URLs** — honours products whose URLs include their category path, so canonical paths survive a rebuild.
- **Unique-URL mode** — optionally pin each product to a single canonical path even when it's assigned to many categories (configurable in admin), avoiding duplicate-content URLs.
- **Batched & schedulable** — an optional cron worker drains the queue in configurable batches, so a full rebuild doesn't block a request or exhaust memory.
- **Cleanup command** — delete stale rewrites by entity type and/or store before regenerating.

## Features

### Generation
- Regenerate URL rewrites for **categories** — all, or a specific set of IDs
- Regenerate URL rewrites for **products** — all, per store, or a specific set of IDs
- Generate across **all active stores** in one pass, or scope to chosen store IDs
- Backfill / regenerate the product **`url_key`** attribute value (optionally from any source attribute)
- Respects both category and product **URL suffix** settings
- Compatible with **category-path** product URLs

### Cleanup
- Delete URL rewrites filtered by **entity type** (`category`, `product`) and/or **store ID**
- Accepts comma-separated lists for bulk operations

### Configuration (admin)

`Stores → Configuration → URL Rewrite Generator → Product Entity Settings`

- **Include Invisible Product** — include products with visibility *Not Visible Individually* in generation (default: **No**)
- **Enable Unique Category/Product URL** — restrict each product to one canonical path even when assigned to multiple categories, with selectable path-resolution options
- **Enable Scheduler** — run generation unattended via cron, with a configurable **cron schedule** (default `03:15`) and **batch size**

## Compatibility

- Open Source (CE) >= 2.4.0
- Commerce On Prem (EE) >= 2.4.0
- Commerce On Cloud (ECE) >= 2.4.0
- PHP 8.1 – 8.4

## Installation

```bash
composer require softcommerce/module-url-rewrite-generator
bin/magento module:enable SoftCommerce_UrlRewriteGenerator
bin/magento setup:upgrade
bin/magento setup:di:compile
```

In production mode, redeploy static content after compilation:

```bash
bin/magento deploy:mode:set production
```

## Usage

### Generate category URL rewrites

```bash
bin/magento url_rewrite:generate:category [-i <ids>]
```

```bash
# All categories
bin/magento url_rewrite:generate:category

# Only categories 25 and 26
bin/magento url_rewrite:generate:category -i 25,26
```

### Generate product URL rewrites

> Products with visibility *Not Visible Individually* are excluded unless **Include Invisible Product** is enabled in configuration.

```bash
bin/magento url:generate:product [-i <ids>] [-s <store_ids>]
```

```bash
# All products, all stores
bin/magento url:generate:product

# Products 25 and 26 for store IDs 1 and 2
bin/magento url:generate:product -i 25,26 -s 1,2
```

### Generate the product `url_key` attribute

Backfills or rebuilds the `url_key` value — useful before regenerating rewrites, or to derive keys from another attribute.

```bash
bin/magento url:product_url_key:generate [-c <attribute_code>] [-i <product_ids>] [-s <store_ids>]
```

```bash
# Regenerate url_key from product name for all products
bin/magento url:product_url_key:generate

# Derive url_key from a custom attribute for products 25 and 26 on store 1
bin/magento url:product_url_key:generate -c my_attribute -i 25,26 -s 1
```

### Delete URL rewrites

```bash
bin/magento url_rewrite:delete -e <entities> -s <store_ids>
```

```bash
# Delete product rewrites for stores 1 and 2
bin/magento url_rewrite:delete -e product -s 1,2

# Delete product and category rewrites for stores 1, 2 and 3
bin/magento url_rewrite:delete -e product,category -s 1,2,3
```

## Support

Byte8 Ltd — support@byte8.io

## License

Each source file in this package is licensed under OSL 3.0 / AFL 3.0 — see [`LICENSE.txt`](LICENSE.txt) for full details.

[Open Software License (OSL 3.0)](https://opensource.org/licenses/osl-3.0.php)

## Thanks for dropping by

<p align="center">
    <a href="https://byte8.io" target="_blank">
        <img src="https://byte8.io/pub/media/banner/logo.svg" width="200" alt="Byte8 Ltd" />
    </a>
    <br />
    <a href="https://byte8.io/" target="_blank">https://byte8.io/</a>
</p>
