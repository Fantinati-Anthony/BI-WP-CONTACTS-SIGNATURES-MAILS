<?php
namespace BI_Signatures\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Settings {

    const OPTION = 'bi_sig_settings';

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register_hooks() {
        add_action( 'admin_menu', array( $this, 'register_page' ), 20 );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function register_page() {
        add_submenu_page(
            'bi-signatures',
            __( 'Réglages', 'bi-signatures' ),
            __( 'Réglages', 'bi-signatures' ),
            'manage_options',
            'bi-signatures-settings',
            array( $this, 'render' )
        );
    }

    public function register_settings() {
        register_setting( 'bi_sig_settings_group', self::OPTION, array(
            'sanitize_callback' => array( $this, 'sanitize' ),
        ) );
    }

    public function sanitize( $input ) {
        $out = array();
        $out['global_logo_url']   = isset( $input['global_logo_url'] ) ? esc_url_raw( $input['global_logo_url'] ) : '';
        $out['from_email']        = isset( $input['from_email'] ) ? sanitize_email( $input['from_email'] ) : '';
        $out['from_name']         = isset( $input['from_name'] ) ? sanitize_text_field( $input['from_name'] ) : '';
        $out['default_template']  = isset( $input['default_template'] ) ? absint( $input['default_template'] ) : 0;
        return $out;
    }

    public function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $opts = get_option( self::OPTION, array() );
        $opts = wp_parse_args( $opts, array(
            'global_logo_url'  => '',
            'from_email'       => get_option( 'admin_email' ),
            'from_name'        => get_option( 'blogname' ),
            'default_template' => 0,
        ) );

        $templates = get_posts( array(
            'post_type'      => 'signature_template',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ) );

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Réglages BI Signatures', 'bi-signatures' ); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'bi_sig_settings_group' ); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="bi_sig_global_logo"><?php esc_html_e( 'Logo global (URL)', 'bi-signatures' ); ?></label></th>
                        <td>
                            <input type="url" class="regular-text bi-sig-media-field" id="bi_sig_global_logo" name="<?php echo esc_attr( self::OPTION ); ?>[global_logo_url]" value="<?php echo esc_attr( $opts['global_logo_url'] ); ?>" />
                            <button class="button bi-sig-media-btn" data-target="#bi_sig_global_logo" type="button"><?php esc_html_e( 'Choisir une image', 'bi-signatures' ); ?></button>
                            <p class="description"><?php esc_html_e( 'Priorité: contact.logo > global.logo', 'bi-signatures' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bi_sig_from_name"><?php esc_html_e( 'Expéditeur (nom)', 'bi-signatures' ); ?></label></th>
                        <td><input type="text" class="regular-text" id="bi_sig_from_name" name="<?php echo esc_attr( self::OPTION ); ?>[from_name]" value="<?php echo esc_attr( $opts['from_name'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bi_sig_from_email"><?php esc_html_e( 'Expéditeur (email)', 'bi-signatures' ); ?></label></th>
                        <td><input type="email" class="regular-text" id="bi_sig_from_email" name="<?php echo esc_attr( self::OPTION ); ?>[from_email]" value="<?php echo esc_attr( $opts['from_email'] ); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="bi_sig_default_template"><?php esc_html_e( 'Modèle par défaut', 'bi-signatures' ); ?></label></th>
                        <td>
                            <select id="bi_sig_default_template" name="<?php echo esc_attr( self::OPTION ); ?>[default_template]">
                                <option value="0"><?php esc_html_e( '— Modèle intégré —', 'bi-signatures' ); ?></option>
                                <?php foreach ( $templates as $t ) : ?>
                                    <option value="<?php echo esc_attr( $t->ID ); ?>" <?php selected( $opts['default_template'], $t->ID ); ?>><?php echo esc_html( $t->post_title ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
