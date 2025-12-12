<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * pdftr_extract_text_from_pdf
 *
 * Tries multiple strategies (no shell_exec / no vendor):
 * 1) Text-based extraction via PDF content heuristics (Tj, TJ, BT/ET, etc.)
 * 2) If fail and Imagick available -> convert pages to images and run OCR
 * 3) OCR uses Google Vision REST API if API key defined via constant PDFTR_GOOGLE_VISION_API_KEY
 *
 * Returns extracted text string on success, or false on failure and $error message filled.
 *
 * NOTE: For scanned PDFs you must provide a Google Vision API key (or enable server-side tesseract, not recommended on shared hosting).
 */

function pdftr_extract_text_from_pdf( $pdf_path, &$error = null ) {
    $error = '';

    // 1) basic checks
    if ( ! file_exists( $pdf_path ) ) {
        $error = 'PDF file not found.';
        return false;
    }

    // Try fast heuristics: many text-based PDFs store text objects with Tj / TJ operators.
    $content = @file_get_contents( $pdf_path );
    if ( $content === false ) {
        $error = 'Failed to open PDF file.';
        return false;
    }

    // --- Attempt 1: extract using several regex heuristics for PDF content streams ---
    $extracted = '';

    // pattern 1: (text) Tj
    if ( preg_match_all('/\((.*?)\)\s*Tj/s', $content, $m1) ) {
        foreach ( $m1[1] as $t ) {
            $extracted .= preg_replace('/\\\\([()\\\\])/', '$1', $t) . ' ';
        }
    }

    // pattern 2: [(... ) 123 ...] TJ  (array of strings and positions)
    if ( preg_match_all('/\[\s*(?:\((.*?)\)|\s*-?\d+)\s*(?:\s*\(\s*(.*?)\s*\)\s*)*\]\s*TJ/s', $content, $m2) ) {
        foreach ( $m2[0] as $item ) {
            // extract every (...) inside the array
            if ( preg_match_all('/\((.*?)\)/s', $item, $sub) ) {
                foreach ( $sub[1] as $s ) {
                    $extracted .= preg_replace('/\\\\([()\\\\])/', '$1', $s) . ' ';
                }
            }
        }
    }

    // pattern 3: BT ... ET blocks (text objects; try to capture strings inside)
    if ( preg_match_all('/BT(.*?)ET/s', $content, $blocks) ) {
        foreach ( $blocks[1] as $blk ) {
            if ( preg_match_all('/\((.*?)\)/s', $blk, $sub2) ) {
                foreach ( $sub2[1] as $s2 ) {
                    $extracted .= preg_replace('/\\\\([()\\\\])/', '$1', $s2) . ' ';
                }
            }
        }
    }

    // Clean result: remove binary garbage, keep printable characters and whitespace
    $clean = preg_replace('/[^\PC\s]/u', '', $extracted);
    $clean = trim(preg_replace('/\s{2,}/', ' ', $clean));

    if ( ! empty( $clean ) ) {
        return $clean;
    }

    // --- Attempt 2: If heuristics fail, try image conversion + OCR ---
    // Check for Imagick extension to create images from PDF pages
    if ( class_exists('Imagick') ) {

        try {
            $imagick = new Imagick();
            // Read PDF (may require Ghostscript available on server for Imagick->readImage)
            // Limit pages to a reasonable number to avoid timeouts (e.g., first 10 pages)
            $max_pages = 10;
            $page = 0;
            $all_text = '';

            // Use a loop to read pages one by one
            while ( true ) {
                try {
                    $imagick->clear();
                    $imagick->setResolution(150, 150); // reasonable DPI
                    $imagick->readImage( $pdf_path . '[' . $page . ']' );
                } catch ( Exception $e ) {
                    break; // no more pages or cannot read
                }

                // convert the page to JPEG/PNG blob
                $imagick->setImageFormat('jpeg');
                $imagick->setImageCompressionQuality(80);
                $image_blob = $imagick->getImageBlob();

                // run OCR on blob (uses Google Vision if API key present)
                $ocr_text = pdftr_ocr_image_blob( $image_blob, $error );
                if ( $ocr_text !== false ) {
                    $all_text .= $ocr_text . "\n";
                }

                $page++;
                if ( $page >= $max_pages ) break;
            }

            if ( ! empty( trim( $all_text ) ) ) {
                return trim( $all_text );
            } else {
                $error = 'PDF appears to be scanned but OCR produced no text. ' . ( $error ? $error : '' );
                return false;
            }

        } catch ( Exception $e ) {
            $error = 'Imagick error: ' . $e->getMessage();
            return false;
        }

    } else {
        // Imagick not available
        $error = 'Text extraction failed: No compatible parser available on this server. ' .
                 'Imagick extension not found for OCR. To enable OCR either: ' .
                 '1) install/enable Imagick on server AND provide Google Vision API key via define(\"PDFTR_GOOGLE_VISION_API_KEY\",\"YOUR_KEY\"), or ' .
                 '2) upload vendor-based PHP PDF parser to plugin (composer install smalot/pdfparser).';
        return false;
    }
}

/**
 * pdftr_ocr_image_blob
 *
 * Takes an image blob (binary) and returns OCR text using Google Vision REST API if API key defined.
 * Returns text string on success, or false and sets $error.
 *
 * NOTE: If you want to use another OCR provider you can replace this function's implementation.
 */
function pdftr_ocr_image_blob( $image_blob, &$error = null ) {
    $error = '';

    // Check if Google Vision API key is provided (simple configuration method)
    if ( defined('PDFTR_GOOGLE_VISION_API_KEY') && PDFTR_GOOGLE_VISION_API_KEY ) {
        $api_key = PDFTR_GOOGLE_VISION_API_KEY;
        $img_base64 = base64_encode( $image_blob );

        $request_body = array(
            "requests" => array(
                array(
                    "image" => array("content" => $img_base64),
                    "features" => array(
                        array("type" => "TEXT_DETECTION", "maxResults" => 1)
                    )
                )
            )
        );

        $url = "https://vision.googleapis.com/v1/images:annotate?key=" . rawurlencode($api_key);
        $json = json_encode( $request_body );

        // Use curl to call the API
        $ch = curl_init( $url );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $json );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 30 );
        $resp = curl_exec( $ch );
        $curl_err = curl_error( $ch );
        $status = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
        curl_close( $ch );

        if ( $resp === false ) {
            $error = 'cURL error while calling Google Vision: ' . $curl_err;
            return false;
        }

        $data = json_decode( $resp, true );
        if ( $status !== 200 || ! $data ) {
            $error = 'Google Vision API error: HTTP ' . $status . ' response: ' . substr( $resp, 0, 400 );
            return false;
        }

        // parse response for text
        if ( isset( $data['responses'][0]['fullTextAnnotation']['text'] ) ) {
            return $data['responses'][0]['fullTextAnnotation']['text'];
        } elseif ( isset( $data['responses'][0]['textAnnotations'][0]['description'] ) ) {
            return $data['responses'][0]['textAnnotations'][0]['description'];
        } else {
            $error = 'Google Vision returned no text.';
            return false;
        }

    } else {
        $error = 'Google Vision API key is not configured. Define PDFTR_GOOGLE_VISION_API_KEY in wp-config.php or plugin main file.';
        return false;
    }
}
