## Changelog

### Version 1.1.0
- **Compatibility**: Added compatibility for `SoftCommerce_Core` module.

### Version 1.0.9
- **Enhancement**: New option to check if scheduler is enabled before processing a scheduled cron job. [#5]

### Version 1.0.8
- **Fix**: Apply a fix to url generation for products not visible individually [#4]

### Version 1.0.7
- **Enhancement**: An option to import products with status not visible individually.

### Version 1.0.6
- **Fix**: Cannot access offset of type string on string in ProductUrlRewrite.php at line: 256 [#3]

### Version 1.0.5
- **Feature**: Add cron schedule for product URL rewrite generator [#2]

### Version 1.0.4
- **Enhancement**: Reopened: Allow URL generation for all product visibility types. #1

### Version 1.0.3
- **Enhancement**: Allow URL generation for all product visibility types. #1

### Version 1.0.2
- **Enhancement**: Added an option to cache loaded data to URL category rewrite service provider: `SoftCommerce\UrlRewriteGenerator\Model\CategoryUrlRewrite`.

### Version 1.0.1
- **Feature**: Added an option to delete URL rewrites using CLI.

### Version 1.0.0
- **Feature**: New module that handles the generation of the URL Rewrites.
