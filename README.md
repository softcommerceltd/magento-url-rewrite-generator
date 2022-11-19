# URL Rewrite Regeneration extension for Magento 2.
The core purpose of this extension is to provide an easy way of regenerating URL rewrites for Magento 2 using CLI.

## Features
- Generates URL rewrites for Category
- Generates URL rewrites for Product
- Generates URL for all active stores
- Supports Search Engine Optimization
- Compatible with product URL that use category paths
- Compatible with both Category and Product URL Suffix
- Compatible with Product URL Suffix
- Create Permanent Redirect for URLs if URL Key Changed

## Compatibility
- Open Source >= 2.4.0
- Commerce On Prem (EE) >= 2.4.0
- Commerce On Cloud (ECE) >= 2.4.0

## Installation
Using composer

```
composer require softcommerceltd/module-url-rewrite-generator
```

## Post Installation

In production mode:
```
bin/magento deploy:mode:set production
```

In development mode:
```
bin/magento setup:di:compile
```

## Usage

### Generate URL rewrites for Category

Command options:

``
bin/magento url_rewrites:category:generate [id|-i]
``

Example:

```sh
# Regenerate URL rewrites for all categories:
bin/magento url_rewrites:category:generate

# Generate URL rewrites for particular categories with IDs 25 & 26:
bin/magento url_rewrites:category:generate -i 25,26
```

### Generate URL rewrites for Product

> Please note, products with visibility *__Not Visible Individually__* [id: 1] are excluded from URL rewrite generation.

Command options:

``
bin/magento url_rewrites:product:generate [id|-i]
``

```sh
# Regenerate URL rewrites for all products:
bin/magento url_rewrites:product:generate

# Generate URL rewrites for particular products with IDs 25 & 26:
bin/magento url_rewrites:product:generate -i 25,26
```

## Support
Soft Commerce Ltd <br />
support@softcommerce.io

## License
Each source file included in this package is licensed under OSL 3.0.

[Open Software License (OSL 3.0)](https://opensource.org/licenses/osl-3.0.php).
Please see `LICENSE.txt` for full details of the OSL 3.0 license.

## Thanks for dropping by

<p align="center">
    <a href="https://magento.com">
        <img src="https://softcommerce.co.uk/pub/media/banner/logo.svg" width="200" alt="Soft Commerce Ltd" />
    </a>
    <br />
    <a href="https://softcommerce.io/">https://softcommerce.io/</a>
</p>
