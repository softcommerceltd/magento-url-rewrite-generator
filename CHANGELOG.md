## Changelog

### Version 1.2.5
- **Feature**: Add an option to generate product url_key value by store scope [#12]

### Version 1.2.4
- **Feature**: Add an option to generate product url_key value based on the specified attribute [#11]

### Version 1.2.3
- **Enhancement**: Add custom URL persist storage interface [#10]

### Version 1.2.2
- **Enhancement**: Allow fully qualified domain names in the `request_path` and `target_path` fields for URL rewrite import. [#9]

### Version 1.2.1
- **Enhancement**: Add report for asynchronous bulk operations [#8]

### Version 1.2.0
- **Feature** New functionality to import URL rewrites from CSV file [#7]

### Version 1.1.3
- **Fix** Undefined constant SoftCommerce\UrlRewriteGenerator\Cron\Backend\ProductUrlGenerator::XML_PATH_IS_ACTIVE [#6]

### Version 1.1.2
- **Compatibility**: Added compatibility with `SoftCommerce_Core` module.

### Version 1.1.1
- **Enhancement**: New functionality to generate URL by attribute.

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
