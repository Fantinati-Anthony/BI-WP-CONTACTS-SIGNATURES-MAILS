<?php
namespace BI_Signatures\Admin;

use BI_Signatures\Core\Post_Types;
use BI_Signatures\Core\Meta_Fields;
use BI_Signatures\Core\Renderer;
use BI_Signatures\Core\Banner_Router;
use BI_Signatures\Core\Presets;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Meta_Boxes {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register_hooks() {
        add_action( 'add_meta_boxes', array( $this, 'register' ) );
        add_action( 'save_post', array( $this, 'save' ), 10, 2 );
    }

    public function register() {
        add_meta_box( 'bi_sig_contact_fields', __( 'Informations du contact', 'bi-signatures' ), array( $this, 'render_contact' ), Post_Types::CONTACT, 'normal', 'high' );
        add_meta_box( 'bi_sig_contact_preview', __( 'Aperçu signature', 'bi-signatures' ), array( $this, 'render_contact_preview' ), Post_Types::CONTACT, 'side', 'default' );
        add_meta_box( 'bi_sig_banner_fields', __( 'Image & slug de la bannière', 'bi-signatures' ), array( $this, 'render_banner' ), Post_Types::BANNER, 'normal', 'high' );
        add_meta_box( 'bi_sig_template_help', __( 'Variables disponibles', 'bi-signatures' ), array( $this, 'render_template_help' ), Post_Types::TEMPLATE, 'side', 'default' );
        add_meta_box( 'bi_sig_template_presets', __( 'Mises en forme préétablies', 'bi-signatures' ), array( $this, 'render_template_presets' ), Post_Types::TEMPLATE, 'normal', 'high' );
    }

    public function render_template_presets() {
        $presets = Presets::all();
        echo '<p>' . esc_html__( 'Cliquez sur un preset pour remplacer le contenu de l\'éditeur par le HTML correspondant.', 'bi-signatures' ) . '</p>';
        echo '<div class="bi-sig-presets">';
        foreach ( $presets as $id => $preset ) {
            echo '<button type="button" class="button bi-sig-preset-btn" data-preset="' . esc_attr( $id ) . '" title="' . esc_attr( $preset['description'] ) . '">' . esc_html( $preset['label'] ) . '</button> ';
        }
        echo '</div>';
    }

    public function render_contact( $post ) {
        wp_nonce_field( 'bi_sig_save_contact', 'bi_sig_contact_nonce' );

        $data = Meta_Fields::get_contact( $post->ID );
        $data = $data ? $data : array_fill_keys( array_keys( Meta_Fields::CONTACT_FIELDS ), '' );

        $banners = get_posts( array(
            'post_type'      => Post_Types::BANNER,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ) );

        $templates = get_posts( array(
            'post_type'      => Post_Types::TEMPLATE,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ) );

        $users = get_users( array( 'fields' => array( 'ID', 'display_name', 'user_email' ) ) );
        ?>
        <table class="form-table">
            <tr><th><label><?php esc_html_e( 'Prénom', 'bi-signatures' ); ?> *</label></th><td><input type="text" required class="regular-text" name="bi_sig[first_name]" value="<?php echo esc_attr( $data['first_name'] ); ?>" /></td></tr>
            <tr><th><label><?php esc_html_e( 'Nom', 'bi-signatures' ); ?> *</label></th><td><input type="text" required class="regular-text" name="bi_sig[last_name]" value="<?php echo esc_attr( $data['last_name'] ); ?>" /></td></tr>
            <tr><th><label><?php esc_html_e( 'Email', 'bi-signatures' ); ?> *</label></th><td><input type="email" required class="regular-text" name="bi_sig[email]" value="<?php echo esc_attr( $data['email'] ); ?>" /></td></tr>
            <tr><th><label><?php esc_html_e( 'Téléphone', 'bi-signatures' ); ?> *</label></th><td><input type="text" required class="regular-text" name="bi_sig[phone]" value="<?php echo esc_attr( $data['phone'] ); ?>" /></td></tr>
            <tr><th><label><?php esc_html_e( 'Fonction', 'bi-signatures' ); ?> *</label></th><td><input type="text" required class="regular-text" name="bi_sig[job_title]" value="<?php echo esc_attr( $data['job_title'] ); ?>" /></td></tr>
            <tr><th><label><?php esc_html_e( 'Société', 'bi-signatures' ); ?> *</label></th><td><input type="text" required class="regular-text" name="bi_sig[company]" value="<?php echo esc_attr( $data['company'] ); ?>" /></td></tr>
            <tr><th><label><?php esc_html_e( 'Logo (URL)', 'bi-signatures' ); ?></label></th><td>
                <input type="url" id="bi_sig_contact_logo" class="regular-text bi-sig-media-field" name="bi_sig[logo]" value="<?php echo esc_attr( $data['logo'] ); ?>" />
                <button class="button bi-sig-media-btn" data-target="#bi_sig_contact_logo" type="button"><?php esc_html_e( 'Choisir', 'bi-signatures' ); ?></button>
            </td></tr>
            <tr><th><label><?php esc_html_e( 'Bannière', 'bi-signatures' ); ?></label></th><td>
                <select name="bi_sig[banner_slug]">
                    <option value=""><?php esc_html_e( '— Aucune —', 'bi-signatures' ); ?></option>
                    <?php foreach ( $banners as $b ) :
                        $slug = get_post_meta( $b->ID, '_bi_sig_slug', true );
                        ?>
                        <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $data['banner_slug'], $slug ); ?>><?php echo esc_html( $b->post_title ); ?> (<?php echo esc_html( $slug ); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </td></tr>
            <tr><th><label><?php esc_html_e( 'Modèle', 'bi-signatures' ); ?></label></th><td>
                <select name="bi_sig[template_id]">
                    <option value="0"><?php esc_html_e( '— Par défaut —', 'bi-signatures' ); ?></option>
                    <?php foreach ( $templates as $t ) : ?>
                        <option value="<?php echo esc_attr( $t->ID ); ?>" <?php selected( (int) $data['template_id'], $t->ID ); ?>><?php echo esc_html( $t->post_title ); ?></option>
                    <?php endforeach; ?>
                </select>
            </td></tr>
            <tr><th><label><?php esc_html_e( 'Utilisateur WP', 'bi-signatures' ); ?></label></th><td>
                <select name="bi_sig[user_id]">
                    <option value="0"><?php esc_html_e( '— Aucun —', 'bi-signatures' ); ?></option>
                    <?php foreach ( $users as $u ) : ?>
                        <option value="<?php echo esc_attr( $u->ID ); ?>" <?php selected( (int) $data['user_id'], $u->ID ); ?>><?php echo esc_html( $u->display_name . ' (' . $u->user_email . ')' ); ?></option>
                    <?php endforeach; ?>
                </select>
            </td></tr>
            <tr><th><?php esc_html_e( 'Shortcode', 'bi-signatures' ); ?></th><td>
                <code>[signature contact_id="<?php echo esc_html( $post->ID ); ?>"]</code>
            </td></tr>
        </table>
        <?php
    }

    public function render_contact_preview( $post ) {
        $html = Renderer::instance()->render_contact( $post->ID );
        echo '<div class="bi-sig-preview">' . Renderer::instance()->safe_output( $html ) . '</div>';
    }

    public function render_banner( $post ) {
        wp_nonce_field( 'bi_sig_save_banner', 'bi_sig_banner_nonce' );

        $slug  = get_post_meta( $post->ID, '_bi_sig_slug', true );
        $image = get_post_meta( $post->ID, '_bi_sig_image_url', true );

        if ( empty( $slug ) && $post->post_title ) {
            $slug = sanitize_title( $post->post_title );
        }

        $public = ! empty( $slug ) ? Banner_Router::public_url( $slug, true ) : '';
        ?>
        <table class="form-table">
            <tr>
                <th><label for="bi_sig_banner_slug"><?php esc_html_e( 'Slug (unique, jamais modifié)', 'bi-signatures' ); ?></label></th>
                <td>
                    <input type="text" required id="bi_sig_banner_slug" name="bi_sig_banner[slug]" value="<?php echo esc_attr( $slug ); ?>" class="regular-text" />
                    <p class="description"><?php esc_html_e( 'Le slug définit l\'URL dynamique. Ne le changez pas après diffusion.', 'bi-signatures' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="bi_sig_banner_image"><?php esc_html_e( 'Image', 'bi-signatures' ); ?></label></th>
                <td>
                    <input type="url" id="bi_sig_banner_image" class="regular-text bi-sig-media-field" name="bi_sig_banner[image_url]" value="<?php echo esc_attr( $image ); ?>" />
                    <button class="button bi-sig-media-btn" data-target="#bi_sig_banner_image" type="button"><?php esc_html_e( 'Choisir / remplacer', 'bi-signatures' ); ?></button>
                    <?php if ( $image ) : ?>
                        <div style="margin-top:10px;"><img src="<?php echo esc_url( $image ); ?>" style="max-width:400px;height:auto;" /></div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if ( $public ) : ?>
            <tr>
                <th><?php esc_html_e( 'URL dynamique', 'bi-signatures' ); ?></th>
                <td><code><?php echo esc_html( $public ); ?></code></td>
            </tr>
            <?php endif; ?>
        </table>
        <?php
    }

    public function render_template_help() {
        echo '<p>' . esc_html__( 'Variables à utiliser dans le contenu HTML :', 'bi-signatures' ) . '</p>';
        echo '<ul style="font-family:monospace;">';
        foreach ( array( 'first_name', 'last_name', 'full_name', 'email', 'phone', 'job_title', 'company', 'logo', 'banner' ) as $v ) {
            echo '<li>{{' . esc_html( $v ) . '}}</li>';
        }
        echo '</ul>';
    }

    public function save( $post_id, $post ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( $post->post_type === Post_Types::CONTACT ) {
            if ( ! isset( $_POST['bi_sig_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bi_sig_contact_nonce'] ) ), 'bi_sig_save_contact' ) ) {
                return;
            }
            if ( isset( $_POST['bi_sig'] ) && is_array( $_POST['bi_sig'] ) ) {
                $values = wp_unslash( $_POST['bi_sig'] );
                Meta_Fields::save_contact( $post_id, $values );
            }
        }

        if ( $post->post_type === Post_Types::BANNER ) {
            if ( ! isset( $_POST['bi_sig_banner_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bi_sig_banner_nonce'] ) ), 'bi_sig_save_banner' ) ) {
                return;
            }
            if ( isset( $_POST['bi_sig_banner'] ) && is_array( $_POST['bi_sig_banner'] ) ) {
                $values = wp_unslash( $_POST['bi_sig_banner'] );
                $slug   = sanitize_title( isset( $values['slug'] ) ? $values['slug'] : $post->post_title );
                $image  = isset( $values['image_url'] ) ? esc_url_raw( $values['image_url'] ) : '';

                if ( empty( $slug ) ) {
                    $slug = sanitize_title( $post->post_title );
                }

                update_post_meta( $post_id, '_bi_sig_slug', $slug );
                update_post_meta( $post_id, '_bi_sig_image_url', $image );

                $image_id = attachment_url_to_postid( $image );
                if ( $image_id ) {
                    update_post_meta( $post_id, '_bi_sig_image_id', (int) $image_id );
                }
            }
        }
    }
}
