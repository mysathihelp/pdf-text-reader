# PDF Text Reader (WordPress plugin)

Simple testing/demo plugin to upload a PDF (biodata) from WP admin, extract text and display it.

## Features
- Upload PDF via admin menu
- Extracts text using `pdftotext` (Poppler)
- Fallback basic extraction if pdftotext not available
- Designed for learning GitHub + WordPress plugin workflow

## Installation
1. Upload folder `pdf-text-reader` into `wp-content/plugins/`
2. Activate plugin from Admin → Plugins
3. Go to Admin → PDF Text Reader → Upload Biodata
4. Upload PDF → Extract text → Displayed instantly

## Requirements
- PHP 7.4+
- WordPress 5.0+
- Optional (recommended): poppler-utils (`pdftotext`)

## License  
GPL2
