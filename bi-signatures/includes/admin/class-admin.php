<?php
namespace BI_Signatures\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register_hooks() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function register_menu() {
        add_menu_page(
            __( 'Signatures', 'bi-signatures' ),
            __( 'Signatures', 'bi-signatures' ),
            'manage_options',
            'bi-signatures',
            array( $this, 'render_dashboard' ),
            'dashicons-email-alt',
            30
        );
    }

    public function render_dashboard() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'BI Signatures', 'bi-signatures' ) . '</h1>';
        echo '<p>' . esc_html__( 'Gérez vos contacts, modèles de signatures, bannières et imports depuis les sous-menus.', 'bi-signatures' ) . '</p>';
        echo '<ul class="ul-disc">';
        echo '<li><a href="' . esc_url( admin_url( 'edit.php?post_type=contact_signature' ) ) . '">' . esc_html__( 'Contacts', 'bi-signatures' ) . '</a></li>';
        echo '<li><a href="' . esc_url( admin_url( 'edit.php?post_type=signature_template' ) ) . '">' . esc_html__( 'Modèles de signature', 'bi-signatures' ) . '</a></li>';
        echo '<li><a href="' . esc_url( admin_url( 'edit.php?post_type=banner' ) ) . '">' . esc_html__( 'Bannières', 'bi-signatures' ) . '</a></li>';
        echo '<li><a href="' . esc_url( admin_url( 'admin.php?page=bi-signatures-import' ) ) . '">' . esc_html__( 'Import CSV / XLS', 'bi-signatures' ) . '</a></li>';
        echo '<li><a href="' . esc_url( admin_url( 'admin.php?page=bi-signatures-settings' ) ) . '">' . esc_html__( 'Réglages', 'bi-signatures' ) . '</a></li>';
        echo '</ul>';
        echo '</div>';
    }

    public function enqueue_assets( $hook ) {
        $screen = get_current_screen();
        if ( ! $screen ) {
            return;
        }
        $types = array( 'contact_signature', 'signature_template', 'banner' );
        $load  = false;
        if ( isset( $screen->post_type ) && in_array( $screen->post_type, $types, true ) ) {
            $load = true;
        }
        if ( isset( $_GET['page'] ) && strpos( sanitize_key( wp_unslash( $_GET['page'] ) ), 'bi-signatures' ) === 0 ) {
            $load = true;
        }
        if ( ! $load ) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style( 'bi-sig-admin', BI_SIG_URL . 'assets/css/admin.css', array(), BI_SIG_VERSION );
        wp_enqueue_script( 'bi-sig-admin', BI_SIG_URL . 'assets/js/admin.js', array( 'jquery' ), BI_SIG_VERSION, true );
        wp_localize_script( 'bi-sig-admin', 'BISigAdmin', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'bi_sig_admin' ),
            'i18n'     => array(
                'pick_logo'   => __( 'Choisir un logo', 'bi-signatures' ),
                'pick_banner' => __( 'Choisir une image de bannière', 'bi-signatures' ),
                'use_image'   => __( 'Utiliser cette image', 'bi-signatures' ),
            ),
        ) );
    }
}
