<?php
namespace BI_Signatures\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Meta_Fields {

    private static $instance = null;

    const CONTACT_FIELDS = array(
        'first_name'  => 'sanitize_text_field',
        'last_name'   => 'sanitize_text_field',
        'email'       => 'sanitize_email',
        'phone'       => 'sanitize_text_field',
        'job_title'   => 'sanitize_text_field',
        'company'     => 'sanitize_text_field',
        'logo'        => 'esc_url_raw',
        'banner_slug' => 'sanitize_title',
        'user_id'     => 'absint',
        'template_id' => 'absint',
    );

    const BANNER_FIELDS = array(
        'slug'      => 'sanitize_title',
        'image_url' => 'esc_url_raw',
        'image_id'  => 'absint',
    );

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register_hooks() {
        add_action( 'init', array( $this, 'register_meta' ) );
    }

    public function register_meta() {
        foreach ( self::CONTACT_FIELDS as $key => $sanitize ) {
            register_post_meta( Post_Types::CONTACT, '_bi_sig_' . $key, array(
                'single'            => true,
                'type'              => 'string',
                'sanitize_callback' => $sanitize,
                'show_in_rest'      => false,
                'auth_callback'     => function () {
                    return current_user_can( 'edit_posts' );
                },
            ) );
        }

        foreach ( self::BANNER_FIELDS as $key => $sanitize ) {
            register_post_meta( Post_Types::BANNER, '_bi_sig_' . $key, array(
                'single'            => true,
                'type'              => 'string',
                'sanitize_callback' => $sanitize,
                'show_in_rest'      => false,
                'auth_callback'     => function () {
                    return current_user_can( 'edit_posts' );
                },
            ) );
        }
    }

    public static function get_contact( $post_id ) {
        $post_id = absint( $post_id );
        if ( ! $post_id || get_post_type( $post_id ) !== Post_Types::CONTACT ) {
            return null;
        }

        $data = array( 'id' => $post_id );
        foreach ( array_keys( self::CONTACT_FIELDS ) as $key ) {
            $data[ $key ] = get_post_meta( $post_id, '_bi_sig_' . $key, true );
        }
        $data['full_name'] = trim( $data['first_name'] . ' ' . $data['last_name'] );
        return $data;
    }

    public static function save_contact( $post_id, $values ) {
        foreach ( self::CONTACT_FIELDS as $key => $sanitize ) {
            if ( ! array_key_exists( $key, $values ) ) {
                continue;
            }
            $value = call_user_func( $sanitize, $values[ $key ] );
            update_post_meta( $post_id, '_bi_sig_' . $key, $value );
        }

        $first = get_post_meta( $post_id, '_bi_sig_first_name', true );
        $last  = get_post_meta( $post_id, '_bi_sig_last_name', true );
        $title = trim( $first . ' ' . $last );
        if ( ! empty( $title ) ) {
            wp_update_post( array(
                'ID'         => $post_id,
                'post_title' => $title,
            ) );
        }
    }

    public static function get_banner_by_slug( $slug ) {
        $slug = sanitize_title( $slug );
        if ( empty( $slug ) ) {
            return null;
        }

        $query = new \WP_Query( array(
            'post_type'      => Post_Types::BANNER,
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'meta_query'     => array(
                array(
                    'key'   => '_bi_sig_slug',
                    'value' => $slug,
                ),
            ),
            'no_found_rows'  => true,
        ) );

        if ( ! $query->have_posts() ) {
            return null;
        }

        $post = $query->posts[0];
        return array(
            'id'        => $post->ID,
            'name'      => $post->post_title,
            'slug'      => $slug,
            'image_url' => get_post_meta( $post->ID, '_bi_sig_image_url', true ),
            'image_id'  => (int) get_post_meta( $post->ID, '_bi_sig_image_id', true ),
            'updated'   => get_post_modified_time( 'U', true, $post ),
        );
    }
}
