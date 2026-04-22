<?php
/**
 * Plugin Name: BI Contacts & Signatures Mails
 * Plugin URI: https://example.com/bi-signatures
 * Description: Gestion complète de contacts, modèles de signatures email dynamiques, bannières dynamiques, import CSV/XLS et envoi par email.
 * Version: 1.0.0
 * Author: BI
 * License: GPL-2.0+
 * Text Domain: bi-signatures
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'BI_SIG_VERSION', '1.0.0' );
define( 'BI_SIG_FILE', __FILE__ );
define( 'BI_SIG_PATH', plugin_dir_path( __FILE__ ) );
define( 'BI_SIG_URL', plugin_dir_url( __FILE__ ) );
define( 'BI_SIG_BASENAME', plugin_basename( __FILE__ ) );

require_once BI_SIG_PATH . 'includes/class-autoloader.php';

BI_Signatures\Autoloader::register();

register_activation_hook( __FILE__, array( 'BI_Signatures\\Core\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'BI_Signatures\\Core\\Deactivator', 'deactivate' ) );

add_action( 'plugins_loaded', function () {
    \BI_Signatures\Core\Plugin::instance()->boot();
} );
