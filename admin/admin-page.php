<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function pdftr_add_admin_menu() {
    add_menu_page(
        __( 'PDF Text Reader', 'pdf-text-reader' ),
        'PDF Text Reader',
        'manage_options',
        'pdf-text-reader',
        'pdftr_admin_page_render',
        'dashicons-media-document',
        20
    );
}
add_action( 'admin_menu', 'pdftr_add_admin_menu' );

function pdftr_admin_page_render() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'Unauthorized', 'pdf-text-reader' ) );
    }

    $extracted_text = '';
    $message = '';

    if ( isset( $_POST['pdftr_submit'] ) ) {
        if ( ! isset( $_POST['pdftr_nonce'] ) || ! wp_verify_nonce( $_POST['pdftr_nonce'], 'pdftr_upload' ) ) {
            $message = '<div class="notice error">Nonce check failed.</div>';
        } else {
            if ( isset( $_FILES['pdftr_file'] ) && $_FILES['pdftr_file']['error'] === UPLOAD_ERR_OK ) {
                $file = $_FILES['pdftr_file'];

                $finfo = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
                if ( $finfo['ext'] !== 'pdf' && $finfo['type'] !== 'application/pdf' ) {
                    $message = '<div class="notice error">Please upload a valid PDF file.</div>';
                } else {
                    $uploads = wp_upload_dir();
                    $target_dir = trailingslashit( $uploads['basedir'] ) . 'pdftr-uploads/';
                    if ( ! file_exists( $target_dir ) ) {
                        wp_mkdir_p( $target_dir );
                    }
                    $target_file = $target_dir . wp_unique_filename( $target_dir, sanitize_file_name( $file['name'] ) );
                    if ( move_uploaded_file( $file['tmp_name'], $target_file ) ) {
                        $extracted_text = pdftr_extract_text_from_pdf( $target_file, $error );
                        if ( $extracted_text === false ) {
                            $message = '<div class="notice error">Extraction failed: ' . esc_html( $error ) . '</div>';
                        } else {
                            $message = '<div class="notice success">PDF uploaded and text extracted successfully.</div>';
                        }
                    } else {
                        $message = '<div class="notice error">Failed to move uploaded file.</div>';
                    }
                }
            } else {
                $message = '<div class="notice error">No file uploaded or upload error.</div>';
            }
        }
    }

    ?>
    <div class="wrap pdftr-wrap">
        <h1><?php esc_html_e( 'PDF Text Reader', 'pdf-text-reader' ); ?></h1>

        <?php echo $message; ?>

        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field( 'pdftr_upload', 'pdftr_nonce' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="pdftr_file">Upload PDF (Biodata)</label></th>
                    <td>
                        <input type="file" name="pdftr_file" id="pdftr_file" accept="application/pdf" required />
                        <p class="description">Choose a PDF file to extract text from.</p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="pdftr_submit" id="submit" class="button button-primary" value="Extract Text" />
            </p>
        </form>

        <?php if ( ! empty( $extracted_text ) ) : ?>
            <h2>Extracted Text</h2>
            <div class="pdftr-output"><pre><?php echo esc_html( $extracted_text ); ?></pre></div>
        <?php endif; ?>

    </div>
    <?php
}
