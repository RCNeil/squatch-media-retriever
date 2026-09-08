# Squatch Media Retriever

Retrieves featured images from a CSV exported by Squatch Post Exporter and saves them to the WordPress uploads directory.

## Features

- Downloads images from the `Featured Image URL` column.
- Preserves the original `/wp-content/uploads/` directory structure.
- Skips files that already exist.
- Does not add files to the WordPress Media Library.
- Processes images in AJAX batches.
- Displays download progress and results.
- Supports optional WebP conversion.
- Uses GD for WebP conversion when available.
- Falls back to Imagick when available.
- Keeps the original image when creating a WebP version.
- Gracefully falls back to the original image if WebP conversion fails.

## Options

### CSV File

Select a CSV exported by Squatch Post Exporter.

The CSV must contain a:

Featured Image URL

column.

### WebP Conversion

When enabled, the plugin creates a `.webp` version of supported images.

WebP conversion uses:

1. GD, if available
2. Imagick, if available

The original image is always retained.

## Requirements

- WordPress
- PHP
- CSV exported by Squatch Post Exporter

WebP conversion requires either GD or Imagick with WebP support.

## Version

1.005

## Author

Squatch Creative
https://squatchcreative.com
