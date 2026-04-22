<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'bi_sig_settings' );
delete_option( 'bi_sig_import_mappings' );

$post_types = array( 'contact_signature', 'signature_template', 'banner' );
foreach ( $post_types as $pt ) {
    $posts = get_posts( array(
        'post_type'      => $pt,
        'posts_per_page' => -1,
        'post_status'    => 'any',
        'fields'         => 'ids',
    ) );
    foreach ( $posts as $id ) {
        wp_delete_post( $id, true );
    }
}
