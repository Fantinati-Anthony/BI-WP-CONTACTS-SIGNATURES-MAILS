<?php
namespace BI_Signatures\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Plugin {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    public function boot() {
        load_plugin_textdomain( 'bi-signatures', false, dirname( BI_SIG_BASENAME ) . '/languages' );

        Post_Types::instance()->register_hooks();
        Meta_Fields::instance()->register_hooks();
        Banner_Router::instance()->register_hooks();
        Renderer::instance();
        Mailer::instance();

        if ( is_admin() ) {
            \BI_Signatures\Admin\Admin::instance()->register_hooks();
            \BI_Signatures\Admin\Settings::instance()->register_hooks();
            \BI_Signatures\Admin\Meta_Boxes::instance()->register_hooks();
            \BI_Signatures\Admin\Visual_Builder::instance()->register_hooks();
            \BI_Signatures\Import\Importer::instance()->register_hooks();
        }

        \BI_Signatures\Front\Shortcodes::instance()->register_hooks();
        \BI_Signatures\Front\Forms::instance()->register_hooks();
        \BI_Signatures\Front\Ajax::instance()->register_hooks();
        \BI_Signatures\Api\Rest::instance()->register_hooks();
    }
}
