<?php
/*
Plugin Name: PDF Text Reader
Plugin URI:  https://github.com/yourusername/pdf-text-reader
Description: Upload a biodata PDF in admin and extract & display its text below the upload form (testing/demo plugin).
Version:     1.1.1
Author:      Smart Technologies
Author URI:  https://github.com/mysathihelp
License:     GPL2
Text Domain: pdf-text-reader
*/

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'PDFTR_DIR', plugin_dir_path( __FILE__ ) );
define( 'PDFTR_URL', plugin_dir_url( __FILE__ ) );

require_once PDFTR_DIR . 'includes/pdf-reader.php';
require_once PDFTR_DIR . 'admin/admin-page.php';

function pdftr_enqueue_admin_assets( $hook ) {
    if ( $hook !== 'toplevel_page_pdf-text-reader' ) return;
    wp_enqueue_style( 'pdftr-admin-style', PDFTR_URL . 'assets/style.css' );
}
add_action( 'admin_enqueue_scripts', 'pdftr_enqueue_admin_assets' );
