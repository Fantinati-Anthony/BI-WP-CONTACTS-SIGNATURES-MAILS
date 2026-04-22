<?php
namespace BI_Signatures\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Post_Types {

    const CONTACT  = 'contact_signature';
    const TEMPLATE = 'signature_template';
    const BANNER   = 'banner';

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register_hooks() {
        add_action( 'init', array( $this, 'register' ) );
    }

    public function register() {
        register_post_type( self::CONTACT, array(
            'labels' => array(
                'name'          => __( 'Contacts', 'bi-signatures' ),
                'singular_name' => __( 'Contact', 'bi-signatures' ),
                'add_new'       => __( 'Ajouter un contact', 'bi-signatures' ),
                'add_new_item'  => __( 'Ajouter un contact', 'bi-signatures' ),
                'edit_item'     => __( 'Modifier le contact', 'bi-signatures' ),
                'new_item'      => __( 'Nouveau contact', 'bi-signatures' ),
                'view_item'     => __( 'Voir le contact', 'bi-signatures' ),
                'search_items'  => __( 'Rechercher contact', 'bi-signatures' ),
                'not_found'     => __( 'Aucun contact', 'bi-signatures' ),
                'menu_name'     => __( 'Contacts Signatures', 'bi-signatures' ),
            ),
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => 'bi-signatures',
            'supports'     => array( 'title' ),
            'map_meta_cap' => true,
            'capability_type' => 'post',
        ) );

        register_post_type( self::TEMPLATE, array(
            'labels' => array(
                'name'          => __( 'Modèles de signature', 'bi-signatures' ),
                'singular_name' => __( 'Modèle', 'bi-signatures' ),
                'add_new'       => __( 'Ajouter un modèle', 'bi-signatures' ),
                'add_new_item'  => __( 'Ajouter un modèle', 'bi-signatures' ),
                'edit_item'     => __( 'Modifier le modèle', 'bi-signatures' ),
                'menu_name'     => __( 'Modèles', 'bi-signatures' ),
            ),
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => 'bi-signatures',
            'supports'     => array( 'title', 'editor' ),
        ) );

        register_post_type( self::BANNER, array(
            'labels' => array(
                'name'          => __( 'Bannières', 'bi-signatures' ),
                'singular_name' => __( 'Bannière', 'bi-signatures' ),
                'add_new'       => __( 'Ajouter une bannière', 'bi-signatures' ),
                'add_new_item'  => __( 'Ajouter une bannière', 'bi-signatures' ),
                'edit_item'     => __( 'Modifier la bannière', 'bi-signatures' ),
                'menu_name'     => __( 'Bannières', 'bi-signatures' ),
            ),
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => 'bi-signatures',
            'supports'     => array( 'title' ),
        ) );

        add_rewrite_rule(
            '^signature-banner/([^/]+)/?$',
            'index.php?bi_sig_banner=$matches[1]',
            'top'
        );

        add_rewrite_rule(
            '^signature/?$',
            'index.php?bi_sig_signature=1',
            'top'
        );

        add_rewrite_tag( '%bi_sig_banner%', '([^&]+)' );
        add_rewrite_tag( '%bi_sig_signature%', '([^&]+)' );
    }
}
