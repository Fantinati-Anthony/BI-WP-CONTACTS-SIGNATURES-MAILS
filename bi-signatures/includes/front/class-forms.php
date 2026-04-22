<?php
namespace BI_Signatures\Front;

use BI_Signatures\Core\Post_Types;
use BI_Signatures\Core\Meta_Fields;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Forms {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register_hooks() {
        add_action( 'template_redirect', array( $this, 'handle' ), 20 );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function enqueue_assets() {
        wp_register_style( 'bi-sig-front', BI_SIG_URL . 'assets/css/front.css', array(), BI_SIG_VERSION );
        wp_register_script( 'bi-sig-front', BI_SIG_URL . 'assets/js/front.js', array( 'jquery' ), BI_SIG_VERSION, true );

        wp_localize_script( 'bi-sig-front', 'BISigFront', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'bi_sig_front' ),
            'i18n'     => array(
                'copied'       => __( 'Signature copiée !', 'bi-signatures' ),
                'copy_failed'  => __( 'Impossible de copier.', 'bi-signatures' ),
                'send_success' => __( 'Email envoyé.', 'bi-signatures' ),
                'send_failed'  => __( 'Erreur lors de l\'envoi.', 'bi-signatures' ),
                'confirm_del'     => __( 'Supprimer définitivement ?', 'bi-signatures' ),
                'confirm_replace' => __( 'Remplacer le contenu actuel ?', 'bi-signatures' ),
            ),
        ) );

        wp_enqueue_style( 'bi-sig-front' );
        wp_enqueue_script( 'bi-sig-front' );
    }

    public function handle() {
        if ( ! is_user_logged_in() ) {
            return;
        }

        if ( isset( $_GET['bi_sig_delete'], $_GET['_wpnonce'] ) ) {
            $id = absint( wp_unslash( $_GET['bi_sig_delete'] ) );
            $nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );
            if ( $id && wp_verify_nonce( $nonce, 'bi_sig_delete_' . $id ) ) {
                if ( Shortcodes::instance()->can_edit_contact( $id ) ) {
                    wp_delete_post( $id, true );
                    wp_safe_redirect( remove_query_arg( array( 'bi_sig_delete', '_wpnonce' ) ) );
                    exit;
                }
            }
        }

        if ( empty( $_POST['bi_sig_action'] ) ) {
            return;
        }

        $action = sanitize_key( wp_unslash( $_POST['bi_sig_action'] ) );

        if ( 'save_contact' === $action ) {
            $this->save_contact();
        } elseif ( 'save_template' === $action ) {
            $this->save_template();
        }
    }

    private function save_contact() {
        if ( ! isset( $_POST['bi_sig_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bi_sig_nonce'] ) ), 'bi_sig_front_save_contact' ) ) {
            return;
        }
        if ( ! current_user_can( 'read' ) ) {
            return;
        }

        $id = isset( $_POST['contact_id'] ) ? absint( wp_unslash( $_POST['contact_id'] ) ) : 0;

        $values = array();
        foreach ( array_keys( Meta_Fields::CONTACT_FIELDS ) as $key ) {
            if ( isset( $_POST[ $key ] ) ) {
                $values[ $key ] = wp_unslash( $_POST[ $key ] );
            }
        }

        foreach ( array( 'first_name', 'last_name', 'email', 'phone', 'job_title', 'company' ) as $req ) {
            if ( empty( $values[ $req ] ) ) {
                return;
            }
        }

        $title = trim( $values['first_name'] . ' ' . $values['last_name'] );

        if ( $id ) {
            if ( ! Shortcodes::instance()->can_edit_contact( $id ) ) {
                return;
            }
            wp_update_post( array(
                'ID'         => $id,
                'post_title' => $title,
            ) );
        } else {
            $id = wp_insert_post( array(
                'post_type'   => Post_Types::CONTACT,
                'post_status' => 'publish',
                'post_title'  => $title,
                'post_author' => get_current_user_id(),
            ) );
            if ( ! $id || is_wp_error( $id ) ) {
                return;
            }
        }

        Meta_Fields::save_contact( $id, $values );

        wp_safe_redirect( add_query_arg( array( 'bi_sig_saved' => 1, 'bi_sig_edit' => $id ) ) );
        exit;
    }

    private function save_template() {
        if ( ! isset( $_POST['bi_sig_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bi_sig_nonce'] ) ), 'bi_sig_front_save_template' ) ) {
            return;
        }
        if ( ! current_user_can( 'edit_posts' ) ) {
            return;
        }

        $id      = isset( $_POST['template_id'] ) ? absint( wp_unslash( $_POST['template_id'] ) ) : 0;
        $title   = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
        $content = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';

        if ( empty( $title ) ) {
            return;
        }

        $data = array(
            'post_type'    => Post_Types::TEMPLATE,
            'post_status'  => 'publish',
            'post_title'   => $title,
            'post_content' => $content,
        );

        if ( $id ) {
            $data['ID'] = $id;
            wp_update_post( $data );
        } else {
            $id = wp_insert_post( $data );
        }

        wp_safe_redirect( add_query_arg( array( 'bi_sig_saved' => 1, 'bi_sig_template' => $id ) ) );
        exit;
    }
}
