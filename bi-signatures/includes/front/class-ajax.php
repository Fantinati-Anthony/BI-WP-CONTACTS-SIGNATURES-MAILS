<?php
namespace BI_Signatures\Front;

use BI_Signatures\Core\Renderer;
use BI_Signatures\Core\Mailer;
use BI_Signatures\Core\Meta_Fields;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Ajax {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register_hooks() {
        add_action( 'wp_ajax_bi_sig_send_email', array( $this, 'send_email' ) );
        add_action( 'wp_ajax_bi_sig_render', array( $this, 'render_signature' ) );
        add_action( 'wp_ajax_bi_sig_preview', array( $this, 'preview' ) );
    }

    private function check_nonce() {
        check_ajax_referer( 'bi_sig_front', 'nonce' );
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'Non autorisé.', 'bi-signatures' ) ), 403 );
        }
    }

    public function render_signature() {
        $this->check_nonce();
        $id = isset( $_POST['contact_id'] ) ? absint( wp_unslash( $_POST['contact_id'] ) ) : 0;
        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'Contact manquant.', 'bi-signatures' ) ) );
        }
        $html = Renderer::instance()->render_contact( $id );
        wp_send_json_success( array( 'html' => Renderer::instance()->safe_output( $html ) ) );
    }

    public function send_email() {
        $this->check_nonce();
        $id = isset( $_POST['contact_id'] ) ? absint( wp_unslash( $_POST['contact_id'] ) ) : 0;
        $to = isset( $_POST['to'] ) ? sanitize_email( wp_unslash( $_POST['to'] ) ) : '';

        if ( ! $id ) {
            wp_send_json_error( array( 'message' => __( 'Contact manquant.', 'bi-signatures' ) ) );
        }

        if ( ! Shortcodes::instance()->can_edit_contact( $id ) && ! current_user_can( 'read' ) ) {
            wp_send_json_error( array( 'message' => __( 'Non autorisé.', 'bi-signatures' ) ), 403 );
        }

        $result = Mailer::instance()->send_signature( $id, $to );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }
        if ( ! $result ) {
            wp_send_json_error( array( 'message' => __( 'Envoi échoué.', 'bi-signatures' ) ) );
        }
        wp_send_json_success( array( 'message' => __( 'Email envoyé.', 'bi-signatures' ) ) );
    }

    public function preview() {
        $this->check_nonce();
        $data = isset( $_POST['data'] ) && is_array( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : array();
        $template_id = isset( $_POST['template_id'] ) ? absint( wp_unslash( $_POST['template_id'] ) ) : 0;

        $clean = array();
        foreach ( array( 'first_name', 'last_name', 'email', 'phone', 'job_title', 'company', 'banner_slug' ) as $k ) {
            $clean[ $k ] = isset( $data[ $k ] ) ? sanitize_text_field( $data[ $k ] ) : '';
        }
        $clean['email'] = isset( $data['email'] ) ? sanitize_email( $data['email'] ) : '';
        $clean['logo']  = isset( $data['logo'] ) ? esc_url_raw( $data['logo'] ) : '';

        $html = Renderer::instance()->render_from_array( $clean, $template_id );
        wp_send_json_success( array( 'html' => Renderer::instance()->safe_output( $html ) ) );
    }
}
