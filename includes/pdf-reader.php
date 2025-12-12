<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function pdftr_extract_text_from_pdf( $pdf_path, &$error = null ) {
    $error = '';

    // Check if file exists
    if ( ! file_exists( $pdf_path ) ) {
        $error = 'PDF file not found.';
        return false;
    }

    // ------------------------------
    // 1️⃣ SAFE FALLBACK (NO shell_exec, NO vendor)
    // ------------------------------

    $content = file_get_contents( $pdf_path );
    if ( $content === false ) {
        $error = 'Failed to read PDF file.';
        return false;
    }

    // extract text using simple PDF pattern fallback (works for many PDFs)
    $text = '';

    if ( preg_match_all('/\((.*?)\)\s*Tj/s', $content, $matches) ) {
        foreach ( $matches[1] as $m ) {
            // Unescape escaped characters
            $text .= preg_replace('/\\\\([()])/', '$1', $m);
        }
    }

    // Clean unwanted characters
    $clean = preg_replace('/[^\PC\s]/u', '', $text);

    if ( ! empty( $clean ) ) {
        return $clean;
    }

    // ------------------------------
    // 2️⃣ If extraction failed
    // ------------------------------
    $error = 'Text extraction failed: No compatible parser available on this server.';
    return false;
}
