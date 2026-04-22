<?php
namespace BI_Signatures\Front;

use BI_Signatures\Core\Renderer;
use BI_Signatures\Core\Post_Types;
use BI_Signatures\Core\Meta_Fields;
use BI_Signatures\Core\Presets;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Shortcodes {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register_hooks() {
        add_action( 'init', array( $this, 'register' ) );
        add_action( 'template_redirect', array( $this, 'maybe_render_query' ), 5 );
    }

    public function register() {
        add_shortcode( 'signature', array( $this, 'shortcode_signature' ) );
        add_shortcode( 'bi_signature_list', array( $this, 'shortcode_list' ) );
        add_shortcode( 'bi_signature_form', array( $this, 'shortcode_form' ) );
        add_shortcode( 'bi_template_form', array( $this, 'shortcode_template_form' ) );
    }

    public function shortcode_signature( $atts ) {
        $atts = shortcode_atts( array(
            'contact_id' => 0,
            'template'   => 0,
            'user'       => '',
        ), $atts, 'signature' );

        $contact_id = absint( $atts['contact_id'] );

        if ( ! $contact_id && 'current' === $atts['user'] && is_user_logged_in() ) {
            $contact_id = $this->find_contact_for_user( get_current_user_id() );
        } elseif ( ! $contact_id && ! empty( $atts['user'] ) ) {
            $contact_id = $this->find_contact_for_user( absint( $atts['user'] ) );
        }

        if ( ! $contact_id ) {
            return '';
        }

        $html = Renderer::instance()->render_contact( $contact_id, absint( $atts['template'] ) );
        $html = Renderer::instance()->safe_output( $html );

        $wrap = '<div class="bi-sig-signature" data-contact="' . esc_attr( $contact_id ) . '">' . $html . '</div>';

        if ( is_user_logged_in() ) {
            $wrap .= $this->render_actions( $contact_id );
        }

        return $wrap;
    }

    private function render_actions( $contact_id ) {
        $nonce = wp_create_nonce( 'bi_sig_front' );
        ob_start();
        ?>
        <div class="bi-sig-actions" data-contact="<?php echo esc_attr( $contact_id ); ?>" data-nonce="<?php echo esc_attr( $nonce ); ?>">
            <button type="button" class="bi-sig-copy"><?php esc_html_e( 'Copier la signature', 'bi-signatures' ); ?></button>
            <button type="button" class="bi-sig-send"><?php esc_html_e( 'Envoyer par email', 'bi-signatures' ); ?></button>
            <input type="email" class="bi-sig-send-to" placeholder="<?php esc_attr_e( 'Adresse destinataire (optionnel)', 'bi-signatures' ); ?>" />
            <span class="bi-sig-feedback" aria-live="polite"></span>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_list( $atts ) {
        if ( ! is_user_logged_in() ) {
            return '<p>' . esc_html__( 'Veuillez vous connecter.', 'bi-signatures' ) . '</p>';
        }

        $atts = shortcode_atts( array(
            'limit' => 50,
            'mine'  => 0,
        ), $atts, 'bi_signature_list' );

        $args = array(
            'post_type'      => Post_Types::CONTACT,
            'posts_per_page' => max( 1, absint( $atts['limit'] ) ),
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        );

        if ( ! current_user_can( 'edit_others_posts' ) || ! empty( $atts['mine'] ) ) {
            $args['author'] = get_current_user_id();
        }

        $query = new \WP_Query( $args );

        ob_start();
        echo '<div class="bi-sig-list">';
        if ( ! $query->have_posts() ) {
            echo '<p>' . esc_html__( 'Aucun contact.', 'bi-signatures' ) . '</p>';
        } else {
            echo '<table class="bi-sig-table"><thead><tr>';
            echo '<th>' . esc_html__( 'Nom', 'bi-signatures' ) . '</th>';
            echo '<th>' . esc_html__( 'Email', 'bi-signatures' ) . '</th>';
            echo '<th>' . esc_html__( 'Fonction', 'bi-signatures' ) . '</th>';
            echo '<th>' . esc_html__( 'Société', 'bi-signatures' ) . '</th>';
            echo '<th>' . esc_html__( 'Actions', 'bi-signatures' ) . '</th>';
            echo '</tr></thead><tbody>';
            while ( $query->have_posts() ) {
                $query->the_post();
                $c = Meta_Fields::get_contact( get_the_ID() );
                if ( ! $c ) continue;
                $edit_url = add_query_arg( array(
                    'bi_sig_edit' => $c['id'],
                ), get_permalink() );
                $del_nonce = wp_create_nonce( 'bi_sig_delete_' . $c['id'] );
                $del_url = add_query_arg( array(
                    'bi_sig_delete' => $c['id'],
                    '_wpnonce'      => $del_nonce,
                ), get_permalink() );
                echo '<tr>';
                echo '<td>' . esc_html( $c['full_name'] ) . '</td>';
                echo '<td>' . esc_html( $c['email'] ) . '</td>';
                echo '<td>' . esc_html( $c['job_title'] ) . '</td>';
                echo '<td>' . esc_html( $c['company'] ) . '</td>';
                echo '<td><a href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Éditer', 'bi-signatures' ) . '</a> | ';
                echo '<a href="' . esc_url( $del_url ) . '" onclick="return confirm(\'' . esc_js( __( 'Supprimer ?', 'bi-signatures' ) ) . '\');">' . esc_html__( 'Supprimer', 'bi-signatures' ) . '</a></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
            wp_reset_postdata();
        }
        echo '</div>';
        return ob_get_clean();
    }

    public function shortcode_form( $atts ) {
        if ( ! is_user_logged_in() ) {
            return '<p>' . esc_html__( 'Veuillez vous connecter.', 'bi-signatures' ) . '</p>';
        }

        $atts = shortcode_atts( array( 'id' => 0 ), $atts, 'bi_signature_form' );
        $id   = absint( $atts['id'] );

        if ( ! $id && isset( $_GET['bi_sig_edit'] ) ) {
            $id = absint( wp_unslash( $_GET['bi_sig_edit'] ) );
        }

        $data = $id ? Meta_Fields::get_contact( $id ) : null;
        if ( ! $data ) {
            $data = array_fill_keys( array_keys( Meta_Fields::CONTACT_FIELDS ), '' );
            $data['id'] = 0;
        }

        if ( $id && ! $this->can_edit_contact( $id ) ) {
            return '<p>' . esc_html__( 'Accès refusé.', 'bi-signatures' ) . '</p>';
        }

        $templates = get_posts( array(
            'post_type'      => Post_Types::TEMPLATE,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ) );

        $banners = get_posts( array(
            'post_type'      => Post_Types::BANNER,
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        ) );

        ob_start();
        ?>
        <form method="post" class="bi-sig-form">
            <?php wp_nonce_field( 'bi_sig_front_save_contact', 'bi_sig_nonce' ); ?>
            <input type="hidden" name="bi_sig_action" value="save_contact" />
            <input type="hidden" name="contact_id" value="<?php echo esc_attr( (int) ( isset( $data['id'] ) ? $data['id'] : 0 ) ); ?>" />
            <p><label><?php esc_html_e( 'Prénom', 'bi-signatures' ); ?> *<br><input type="text" name="first_name" required value="<?php echo esc_attr( $data['first_name'] ); ?>" /></label></p>
            <p><label><?php esc_html_e( 'Nom', 'bi-signatures' ); ?> *<br><input type="text" name="last_name" required value="<?php echo esc_attr( $data['last_name'] ); ?>" /></label></p>
            <p><label><?php esc_html_e( 'Email', 'bi-signatures' ); ?> *<br><input type="email" name="email" required value="<?php echo esc_attr( $data['email'] ); ?>" /></label></p>
            <p><label><?php esc_html_e( 'Téléphone', 'bi-signatures' ); ?> *<br><input type="text" name="phone" required value="<?php echo esc_attr( $data['phone'] ); ?>" /></label></p>
            <p><label><?php esc_html_e( 'Fonction', 'bi-signatures' ); ?> *<br><input type="text" name="job_title" required value="<?php echo esc_attr( $data['job_title'] ); ?>" /></label></p>
            <p><label><?php esc_html_e( 'Société', 'bi-signatures' ); ?> *<br><input type="text" name="company" required value="<?php echo esc_attr( $data['company'] ); ?>" /></label></p>
            <p><label><?php esc_html_e( 'Logo (URL)', 'bi-signatures' ); ?><br><input type="url" name="logo" value="<?php echo esc_attr( $data['logo'] ); ?>" /></label></p>
            <p><label><?php esc_html_e( 'Bannière', 'bi-signatures' ); ?><br>
                <select name="banner_slug">
                    <option value=""><?php esc_html_e( '— Aucune —', 'bi-signatures' ); ?></option>
                    <?php foreach ( $banners as $b ) :
                        $slug = get_post_meta( $b->ID, '_bi_sig_slug', true ); ?>
                        <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $data['banner_slug'], $slug ); ?>><?php echo esc_html( $b->post_title ); ?></option>
                    <?php endforeach; ?>
                </select>
            </label></p>
            <p><label><?php esc_html_e( 'Modèle', 'bi-signatures' ); ?><br>
                <select name="template_id">
                    <option value="0"><?php esc_html_e( '— Par défaut —', 'bi-signatures' ); ?></option>
                    <?php foreach ( $templates as $t ) : ?>
                        <option value="<?php echo esc_attr( $t->ID ); ?>" <?php selected( (int) $data['template_id'], $t->ID ); ?>><?php echo esc_html( $t->post_title ); ?></option>
                    <?php endforeach; ?>
                </select>
            </label></p>
            <p><button type="submit" class="bi-sig-submit"><?php esc_html_e( 'Enregistrer', 'bi-signatures' ); ?></button></p>
            <div class="bi-sig-live-preview" data-preview></div>
        </form>
        <?php
        return ob_get_clean();
    }

    public function shortcode_template_form( $atts ) {
        if ( ! is_user_logged_in() || ! current_user_can( 'edit_posts' ) ) {
            return '<p>' . esc_html__( 'Accès refusé.', 'bi-signatures' ) . '</p>';
        }

        $atts = shortcode_atts( array( 'id' => 0 ), $atts, 'bi_template_form' );
        $id   = absint( $atts['id'] );

        if ( ! $id && isset( $_GET['bi_sig_template'] ) ) {
            $id = absint( wp_unslash( $_GET['bi_sig_template'] ) );
        }

        $title = '';
        $content = '';
        if ( $id ) {
            $p = get_post( $id );
            if ( $p && $p->post_type === Post_Types::TEMPLATE ) {
                $title   = $p->post_title;
                $content = $p->post_content;
            } else {
                $id = 0;
            }
        }

        $presets = Presets::all();

        ob_start();
        ?>
        <form method="post" class="bi-sig-template-form">
            <?php wp_nonce_field( 'bi_sig_front_save_template', 'bi_sig_nonce' ); ?>
            <input type="hidden" name="bi_sig_action" value="save_template" />
            <input type="hidden" name="template_id" value="<?php echo esc_attr( $id ); ?>" />
            <p><label><?php esc_html_e( 'Nom du modèle', 'bi-signatures' ); ?> *<br><input type="text" name="title" required value="<?php echo esc_attr( $title ); ?>" /></label></p>

            <div class="bi-sig-presets">
                <strong><?php esc_html_e( 'Mises en forme préétablies :', 'bi-signatures' ); ?></strong><br>
                <?php foreach ( $presets as $pid => $preset ) : ?>
                    <button type="button" class="bi-sig-preset-btn" data-preset="<?php echo esc_attr( $pid ); ?>" data-html="<?php echo esc_attr( $preset['html'] ); ?>" title="<?php echo esc_attr( $preset['description'] ); ?>"><?php echo esc_html( $preset['label'] ); ?></button>
                <?php endforeach; ?>
            </div>

            <p><label><?php esc_html_e( 'Contenu HTML (utilisez {{variables}})', 'bi-signatures' ); ?><br>
                <textarea name="content" rows="12" style="width:100%;font-family:monospace;" data-bi-sig-template-content><?php echo esc_textarea( $content ); ?></textarea></label></p>
            <p><button type="submit"><?php esc_html_e( 'Enregistrer le modèle', 'bi-signatures' ); ?></button></p>
        </form>
        <?php
        return ob_get_clean();
    }

    public function maybe_render_query() {
        $flag = get_query_var( 'bi_sig_signature' );
        if ( empty( $flag ) ) {
            return;
        }

        $data = array(
            'first_name' => isset( $_GET['first_name'] ) ? sanitize_text_field( wp_unslash( $_GET['first_name'] ) ) : '',
            'last_name'  => isset( $_GET['last_name'] ) ? sanitize_text_field( wp_unslash( $_GET['last_name'] ) ) : '',
            'email'      => isset( $_GET['email'] ) ? sanitize_email( wp_unslash( $_GET['email'] ) ) : '',
            'phone'      => isset( $_GET['phone'] ) ? sanitize_text_field( wp_unslash( $_GET['phone'] ) ) : '',
            'job_title'  => isset( $_GET['job_title'] ) ? sanitize_text_field( wp_unslash( $_GET['job_title'] ) ) : '',
            'company'    => isset( $_GET['company'] ) ? sanitize_text_field( wp_unslash( $_GET['company'] ) ) : '',
            'logo'       => isset( $_GET['logo'] ) ? esc_url_raw( wp_unslash( $_GET['logo'] ) ) : '',
            'banner_slug' => isset( $_GET['banner_slug'] ) ? sanitize_title( wp_unslash( $_GET['banner_slug'] ) ) : '',
        );

        $template_id = isset( $_GET['template'] ) ? absint( wp_unslash( $_GET['template'] ) ) : 0;

        $html = Renderer::instance()->render_from_array( $data, $template_id );
        $html = Renderer::instance()->safe_output( $html );

        status_header( 200 );
        header( 'Content-Type: text/html; charset=UTF-8' );
        echo '<!doctype html><html><head><meta charset="utf-8"><title>Signature</title></head><body>';
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</body></html>';
        exit;
    }

    private function find_contact_for_user( $user_id ) {
        $user_id = absint( $user_id );
        if ( ! $user_id ) return 0;

        $query = new \WP_Query( array(
            'post_type'      => Post_Types::CONTACT,
            'posts_per_page' => 1,
            'meta_query'     => array(
                array(
                    'key'   => '_bi_sig_user_id',
                    'value' => $user_id,
                ),
            ),
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ) );
        return $query->have_posts() ? (int) $query->posts[0] : 0;
    }

    public function can_edit_contact( $contact_id ) {
        if ( current_user_can( 'edit_others_posts' ) ) {
            return true;
        }
        $post = get_post( $contact_id );
        if ( ! $post ) return false;
        return (int) $post->post_author === get_current_user_id();
    }
}
