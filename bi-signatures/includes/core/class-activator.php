<?php
namespace BI_Signatures\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Activator {

    public static function activate() {
        Post_Types::instance()->register();
        flush_rewrite_rules();

        if ( false === get_option( 'bi_sig_settings' ) ) {
            add_option( 'bi_sig_settings', array(
                'global_logo_url' => '',
                'from_email'      => get_option( 'admin_email' ),
                'from_name'       => get_option( 'blogname' ),
                'default_template' => 0,
            ) );
        }

        if ( false === get_option( 'bi_sig_import_mappings' ) ) {
            add_option( 'bi_sig_import_mappings', array() );
        }
    }
}
