<?php
namespace BI_Signatures;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Autoloader {

    public static function register() {
        spl_autoload_register( array( __CLASS__, 'load' ) );
    }

    public static function load( $class ) {
        if ( strpos( $class, 'BI_Signatures\\' ) !== 0 ) {
            return;
        }

        $relative = substr( $class, strlen( 'BI_Signatures\\' ) );
        $parts    = explode( '\\', $relative );
        $class_name = array_pop( $parts );
        $sub_path = strtolower( implode( DIRECTORY_SEPARATOR, $parts ) );

        $file_name = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';

        $candidates = array(
            BI_SIG_PATH . 'includes' . DIRECTORY_SEPARATOR . $sub_path . DIRECTORY_SEPARATOR . $file_name,
            BI_SIG_PATH . 'includes' . DIRECTORY_SEPARATOR . $file_name,
        );

        foreach ( $candidates as $candidate ) {
            if ( file_exists( $candidate ) ) {
                require_once $candidate;
                return;
            }
        }
    }
}
