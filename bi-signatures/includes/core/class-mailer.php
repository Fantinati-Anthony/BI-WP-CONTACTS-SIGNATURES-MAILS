<?php
namespace BI_Signatures\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Mailer {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function send_signature( $contact_id, $to_email = '', $subject = '' ) {
        $contact = Meta_Fields::get_contact( $contact_id );
        if ( ! $contact ) {
            return new \WP_Error( 'bi_sig_no_contact', __( 'Contact introuvable', 'bi-signatures' ) );
        }

        if ( empty( $to_email ) ) {
            $to_email = $contact['email'];
        }
        $to_email = sanitize_email( $to_email );
        if ( empty( $to_email ) || ! is_email( $to_email ) ) {
            return new \WP_Error( 'bi_sig_invalid_email', __( 'Adresse email invalide', 'bi-signatures' ) );
        }

        if ( empty( $subject ) ) {
            $subject = sprintf( __( 'Votre signature : %s', 'bi-signatures' ), $contact['full_name'] );
        }

        $html = Renderer::instance()->render_contact( $contact_id );
        $html = Renderer::instance()->safe_output( $html );

        $settings = get_option( 'bi_sig_settings', array() );
        $from_email = isset( $settings['from_email'] ) ? $settings['from_email'] : get_option( 'admin_email' );
        $from_name  = isset( $settings['from_name'] ) ? $settings['from_name'] : get_option( 'blogname' );

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            sprintf( 'From: %s <%s>', $from_name, $from_email ),
        );

        $body = '<html><body>' . $html . '</body></html>';

        return wp_mail( $to_email, $subject, $body, $headers );
    }
}
