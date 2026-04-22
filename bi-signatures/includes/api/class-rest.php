<?php
namespace BI_Signatures\Api;

use BI_Signatures\Core\Renderer;
use BI_Signatures\Core\Mailer;
use BI_Signatures\Core\Meta_Fields;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Rest {

    const NAMESPACE_V1 = 'signature/v1';

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register_hooks() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route( self::NAMESPACE_V1, '/render', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'render' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'contact_id' => array(
                    'type'     => 'integer',
                    'required' => false,
                ),
                'template'   => array(
                    'type'     => 'integer',
                    'required' => false,
                ),
            ),
        ) );

        register_rest_route( self::NAMESPACE_V1, '/send', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'send' ),
            'permission_callback' => function () {
                return is_user_logged_in();
            },
            'args' => array(
                'contact_id' => array( 'type' => 'integer', 'required' => true ),
                'to'         => array( 'type' => 'string', 'required' => false ),
            ),
        ) );
    }

    public function render( \WP_REST_Request $request ) {
        $contact_id  = (int) $request->get_param( 'contact_id' );
        $template_id = (int) $request->get_param( 'template' );

        if ( $contact_id ) {
            $html = Renderer::instance()->render_contact( $contact_id, $template_id );
            if ( empty( $html ) ) {
                return new \WP_Error( 'bi_sig_not_found', __( 'Contact introuvable', 'bi-signatures' ), array( 'status' => 404 ) );
            }
        } else {
            $data = array(
                'first_name'  => $request->get_param( 'first_name' ),
                'last_name'   => $request->get_param( 'last_name' ),
                'email'       => $request->get_param( 'email' ),
                'phone'       => $request->get_param( 'phone' ),
                'job_title'   => $request->get_param( 'job_title' ),
                'company'     => $request->get_param( 'company' ),
                'logo'        => $request->get_param( 'logo' ),
                'banner_slug' => $request->get_param( 'banner_slug' ),
            );
            $html = Renderer::instance()->render_from_array( $data, $template_id );
        }

        $safe = Renderer::instance()->safe_output( $html );

        return rest_ensure_response( array(
            'html' => $safe,
        ) );
    }

    public function send( \WP_REST_Request $request ) {
        $contact_id = (int) $request->get_param( 'contact_id' );
        $to         = sanitize_email( (string) $request->get_param( 'to' ) );

        $result = Mailer::instance()->send_signature( $contact_id, $to );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        if ( ! $result ) {
            return new \WP_Error( 'bi_sig_send_failed', __( 'Envoi échoué', 'bi-signatures' ), array( 'status' => 500 ) );
        }
        return rest_ensure_response( array( 'sent' => true ) );
    }
}
