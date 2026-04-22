<?php
namespace BI_Signatures\Admin;

use BI_Signatures\Core\Post_Types;
use BI_Signatures\Core\Presets;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Visual_Builder {

    const NONCE = 'bi_sig_builder';
    const FIELD = 'bi_sig_builder_content';

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register_hooks() {
        add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
        add_action( 'save_post_' . Post_Types::TEMPLATE, array( $this, 'save' ), 5, 2 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
        add_action( 'edit_form_after_title', array( $this, 'maybe_hide_editor' ) );
    }

    public function maybe_hide_editor( $post ) {
        if ( $post->post_type !== Post_Types::TEMPLATE ) {
            return;
        }
        echo '<style>#postdivrich,#wp-content-editor-tools,#post-status-info,#ed_toolbar{display:none !important;}</style>';
    }

    public function register_meta_box() {
        add_meta_box(
            'bi_sig_visual_builder',
            __( 'Éditeur visuel (drag & drop)', 'bi-signatures' ),
            array( $this, 'render' ),
            Post_Types::TEMPLATE,
            'normal',
            'high'
        );
    }

    public function enqueue( $hook ) {
        global $post;
        if ( ! $post || $post->post_type !== Post_Types::TEMPLATE ) {
            return;
        }
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
            return;
        }

        wp_enqueue_style( 'bi-sig-grapes-css', 'https://unpkg.com/grapesjs/dist/css/grapes.min.css', array(), '0.21.7' );
        wp_enqueue_style( 'bi-sig-grapes-newsletter', 'https://unpkg.com/grapesjs-preset-newsletter/dist/grapesjs-preset-newsletter.css', array(), '1.0.2' );
        wp_enqueue_style( 'bi-sig-builder', BI_SIG_URL . 'assets/css/builder.css', array(), BI_SIG_VERSION );

        wp_enqueue_script( 'bi-sig-grapes', 'https://unpkg.com/grapesjs', array(), '0.21.7', true );
        wp_enqueue_script( 'bi-sig-grapes-newsletter', 'https://unpkg.com/grapesjs-preset-newsletter', array( 'bi-sig-grapes' ), '1.0.2', true );
        wp_enqueue_script( 'bi-sig-builder', BI_SIG_URL . 'assets/js/builder.js', array( 'jquery', 'bi-sig-grapes', 'bi-sig-grapes-newsletter' ), BI_SIG_VERSION, true );

        $presets = array();
        foreach ( Presets::all() as $id => $p ) {
            $presets[ $id ] = array(
                'label' => $p['label'],
                'html'  => $p['html'],
            );
        }

        wp_localize_script( 'bi-sig-builder', 'BISigBuilder', array(
            'field'     => self::FIELD,
            'variables' => array(
                'first_name' => __( 'Prénom', 'bi-signatures' ),
                'last_name'  => __( 'Nom', 'bi-signatures' ),
                'full_name'  => __( 'Nom complet', 'bi-signatures' ),
                'email'      => __( 'Email', 'bi-signatures' ),
                'phone'      => __( 'Téléphone', 'bi-signatures' ),
                'job_title'  => __( 'Fonction', 'bi-signatures' ),
                'company'    => __( 'Société', 'bi-signatures' ),
                'logo'       => __( 'Logo', 'bi-signatures' ),
                'banner'     => __( 'Bannière', 'bi-signatures' ),
            ),
            'presets'   => $presets,
            'i18n'      => array(
                'copied'          => __( 'Variable copiée : %s', 'bi-signatures' ),
                'confirm_replace' => __( 'Remplacer le contenu actuel ?', 'bi-signatures' ),
                'variables'       => __( 'Variables (cliquez pour copier) :', 'bi-signatures' ),
                'presets'         => __( 'Mises en forme préétablies :', 'bi-signatures' ),
            ),
        ) );
    }

    public function render( $post ) {
        wp_nonce_field( self::NONCE, self::NONCE . '_nonce' );
        $content = $post->post_content;
        ?>
        <div class="bi-sig-builder-toolbar">
            <div class="bi-sig-builder-variables">
                <strong><?php esc_html_e( 'Variables (cliquez pour copier) :', 'bi-signatures' ); ?></strong>
                <?php
                $vars = array( 'first_name', 'last_name', 'full_name', 'email', 'phone', 'job_title', 'company', 'logo', 'banner' );
                foreach ( $vars as $v ) {
                    echo '<button type="button" class="bi-sig-var-chip" data-token="{{' . esc_attr( $v ) . '}}">{{' . esc_html( $v ) . '}}</button> ';
                }
                ?>
            </div>
            <div class="bi-sig-builder-presets">
                <strong><?php esc_html_e( 'Mises en forme :', 'bi-signatures' ); ?></strong>
                <?php foreach ( Presets::all() as $id => $p ) : ?>
                    <button type="button" class="bi-sig-preset-inject" data-preset="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $p['label'] ); ?></button>
                <?php endforeach; ?>
            </div>
            <p class="description"><?php esc_html_e( 'Glissez les blocs depuis le panneau latéral. Les variables {{…}} seront remplacées au rendu par les données du contact.', 'bi-signatures' ); ?></p>
        </div>
        <div id="bi-sig-grapes" data-initial="<?php echo esc_attr( $content ); ?>"></div>
        <textarea id="<?php echo esc_attr( self::FIELD ); ?>" name="<?php echo esc_attr( self::FIELD ); ?>" style="display:none;"><?php echo esc_textarea( $content ); ?></textarea>
        <div class="bi-sig-var-feedback" aria-live="polite"></div>
        <?php
    }

    public function save( $post_id, $post ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        if ( ! isset( $_POST[ self::NONCE . '_nonce' ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE . '_nonce' ] ) ), self::NONCE ) ) {
            return;
        }
        if ( ! isset( $_POST[ self::FIELD ] ) ) {
            return;
        }

        $html = wp_unslash( $_POST[ self::FIELD ] );
        $html = $this->sanitize_html( $html );

        remove_action( 'save_post_' . Post_Types::TEMPLATE, array( $this, 'save' ), 5 );
        wp_update_post( array(
            'ID'           => $post_id,
            'post_content' => $html,
        ) );
        add_action( 'save_post_' . Post_Types::TEMPLATE, array( $this, 'save' ), 5, 2 );
    }

    private function sanitize_html( $html ) {
        $allowed = wp_kses_allowed_html( 'post' );
        $extra_tags = array( 'table', 'tbody', 'thead', 'tfoot', 'tr', 'th', 'td', 'img', 'style', 'a', 'span', 'div', 'strong', 'em', 'p', 'br', 'hr', 'h1', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'center', 'font' );
        foreach ( $extra_tags as $tag ) {
            if ( ! isset( $allowed[ $tag ] ) ) {
                $allowed[ $tag ] = array();
            }
            $allowed[ $tag ]['style']       = true;
            $allowed[ $tag ]['class']       = true;
            $allowed[ $tag ]['id']          = true;
            $allowed[ $tag ]['align']       = true;
            $allowed[ $tag ]['valign']      = true;
            $allowed[ $tag ]['width']       = true;
            $allowed[ $tag ]['height']      = true;
            $allowed[ $tag ]['cellpadding'] = true;
            $allowed[ $tag ]['cellspacing'] = true;
            $allowed[ $tag ]['border']      = true;
            $allowed[ $tag ]['bgcolor']     = true;
            $allowed[ $tag ]['colspan']     = true;
            $allowed[ $tag ]['rowspan']     = true;
        }
        $allowed['img']['src']    = true;
        $allowed['img']['alt']    = true;
        $allowed['a']['href']     = true;
        $allowed['a']['target']   = true;
        $allowed['a']['rel']      = true;
        $allowed['style']         = array( 'type' => true );

        return wp_kses( $html, $allowed );
    }
}
