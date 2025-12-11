<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function pdftr_extract_text_from_pdf( $pdf_path, &$error = null ) {
    $error = '';
    if ( ! file_exists( $pdf_path ) ) {
        $error = 'PDF file not found.';
        return false;
    }

    $pdftotext = trim( shell_exec( 'which pdftotext 2>/dev/null' ) );
    if ( ! empty( $pdftotext ) ) {
        $txt_path = $pdf_path . '.txt';
        $cmd = escapeshellcmd( $pdftotext ) . ' -layout ' . escapeshellarg( $pdf_path ) . ' ' . escapeshellarg( $txt_path ) . ' 2>&1';
        exec( $cmd, $output, $return_var );
        if ( $return_var !== 0 ) {
            $error = 'pdftotext failed: ' . implode( "\n", $output );
            return false;
        }
        if ( file_exists( $txt_path ) ) {
            $text = file_get_contents( $txt_path );
            @unlink( $txt_path );
            return $text;
        } else {
            $error = 'pdftotext did not produce output file.';
            return false;
        }
    }

    $content = file_get_contents( $pdf_path );
    if ( $content === false ) {
        $error = 'Failed to read PDF file.';
        return false;
    }

    $text = '';
    if ( preg_match_all( '/\((.*?)\)\s*Tj/s', $content, $matches ) ) {
        foreach ( $matches[1] as $m ) {
            $text .= preg_replace( '/\\\\([()])/', '$1', $m );
        }
    }

    $clean = preg_replace( '/[^\PC\s]/u', '', $text );
    if ( ! empty( $clean ) ) {
        return $clean;
    }

    $error = 'No pdftotext found and fallback extraction failed.';
    return false;
}
