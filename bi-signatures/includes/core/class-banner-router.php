<?php
namespace BI_Signatures\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Banner_Router {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register_hooks() {
        add_filter( 'query_vars', array( $this, 'query_vars' ) );
        add_action( 'template_redirect', array( $this, 'maybe_serve_banner' ) );
    }

    public function query_vars( $vars ) {
        $vars[] = 'bi_sig_banner';
        $vars[] = 'bi_sig_signature';
        return $vars;
    }

    public function maybe_serve_banner() {
        $slug = get_query_var( 'bi_sig_banner' );
        if ( empty( $slug ) ) {
            return;
        }

        $banner = Meta_Fields::get_banner_by_slug( $slug );
        if ( ! $banner || empty( $banner['image_url'] ) ) {
            status_header( 404 );
            nocache_headers();
            exit;
        }

        $path = $this->url_to_path( $banner['image_url'] );

        nocache_headers();
        header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        if ( $path && file_exists( $path ) ) {
            $mime = $this->guess_mime( $path );
            header( 'Content-Type: ' . $mime );
            header( 'Content-Length: ' . filesize( $path ) );
            header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', filemtime( $path ) ) . ' GMT' );
            readfile( $path );
            exit;
        }

        wp_redirect( esc_url_raw( $banner['image_url'] ), 302 );
        exit;
    }

    public static function public_url( $slug, $cache_buster = false ) {
        $url = home_url( '/signature-banner/' . sanitize_title( $slug ) );
        if ( $cache_buster ) {
            $banner = Meta_Fields::get_banner_by_slug( $slug );
            $v = $banner ? $banner['updated'] : time();
            $url = add_query_arg( 'v', $v, $url );
        }
        return $url;
    }

    private function url_to_path( $url ) {
        $uploads = wp_get_upload_dir();
        if ( ! empty( $uploads['baseurl'] ) && strpos( $url, $uploads['baseurl'] ) === 0 ) {
            return str_replace( $uploads['baseurl'], $uploads['basedir'], $url );
        }
        $site_url = site_url();
        if ( strpos( $url, $site_url ) === 0 ) {
            return str_replace( $site_url, untrailingslashit( ABSPATH ), $url );
        }
        return null;
    }

    private function guess_mime( $path ) {
        $ext = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
        $map = array(
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'svg'  => 'image/svg+xml',
        );
        return isset( $map[ $ext ] ) ? $map[ $ext ] : 'application/octet-stream';
    }
}
