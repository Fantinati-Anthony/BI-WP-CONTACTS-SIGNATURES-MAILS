<?php
namespace BI_Signatures\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Renderer {

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function render_contact( $contact_id, $template_id = 0 ) {
        $contact = Meta_Fields::get_contact( $contact_id );
        if ( ! $contact ) {
            return '';
        }

        if ( empty( $template_id ) && ! empty( $contact['template_id'] ) ) {
            $template_id = (int) $contact['template_id'];
        }

        $template = $this->get_template_html( $template_id );
        if ( empty( $template ) ) {
            $template = $this->default_template();
        }

        return $this->render_template( $template, $contact );
    }

    public function render_from_array( $data, $template_id = 0 ) {
        $defaults = array(
            'first_name' => '',
            'last_name'  => '',
            'email'      => '',
            'phone'      => '',
            'job_title'  => '',
            'company'    => '',
            'logo'       => '',
            'banner_slug' => '',
        );
        $data = wp_parse_args( $data, $defaults );
        $data['full_name'] = trim( $data['first_name'] . ' ' . $data['last_name'] );

        $template = $this->get_template_html( $template_id );
        if ( empty( $template ) ) {
            $template = $this->default_template();
        }

        return $this->render_template( $template, $data );
    }

    public function render_template( $template, $contact ) {
        $settings     = get_option( 'bi_sig_settings', array() );
        $global_logo  = isset( $settings['global_logo_url'] ) ? $settings['global_logo_url'] : '';

        $logo = ! empty( $contact['logo'] ) ? $contact['logo'] : $global_logo;

        $banner_html = '';
        if ( ! empty( $contact['banner_slug'] ) ) {
            $banner_url  = Banner_Router::public_url( $contact['banner_slug'], true );
            $banner_html = '<img src="' . esc_url( $banner_url ) . '" alt="" style="max-width:100%;height:auto;border:0;" />';
        }

        $logo_html = '';
        if ( ! empty( $logo ) ) {
            $logo_html = '<img src="' . esc_url( $logo ) . '" alt="" style="max-height:80px;border:0;" />';
        }

        $vars = array(
            '{{first_name}}' => esc_html( isset( $contact['first_name'] ) ? $contact['first_name'] : '' ),
            '{{last_name}}'  => esc_html( isset( $contact['last_name'] ) ? $contact['last_name'] : '' ),
            '{{full_name}}'  => esc_html( isset( $contact['full_name'] ) ? $contact['full_name'] : '' ),
            '{{email}}'      => esc_html( isset( $contact['email'] ) ? $contact['email'] : '' ),
            '{{phone}}'      => esc_html( isset( $contact['phone'] ) ? $contact['phone'] : '' ),
            '{{job_title}}'  => esc_html( isset( $contact['job_title'] ) ? $contact['job_title'] : '' ),
            '{{company}}'    => esc_html( isset( $contact['company'] ) ? $contact['company'] : '' ),
            '{{logo}}'       => $logo_html,
            '{{banner}}'     => $banner_html,
            '{{logo_url}}'   => esc_url( $logo ),
        );

        $output = strtr( $template, $vars );
        return $output;
    }

    public function get_template_html( $template_id ) {
        $template_id = absint( $template_id );

        if ( ! $template_id ) {
            $settings = get_option( 'bi_sig_settings', array() );
            $template_id = isset( $settings['default_template'] ) ? (int) $settings['default_template'] : 0;
        }

        if ( ! $template_id ) {
            return '';
        }

        $post = get_post( $template_id );
        if ( ! $post || $post->post_type !== Post_Types::TEMPLATE ) {
            return '';
        }

        return $post->post_content;
    }

    public function default_template() {
        return '<table cellpadding="0" cellspacing="0" border="0" style="font-family:Arial,sans-serif;font-size:13px;color:#333;">
<tr>
<td style="padding-right:15px;vertical-align:top;">{{logo}}</td>
<td style="vertical-align:top;">
<strong style="font-size:15px;color:#000;">{{full_name}}</strong><br>
<span style="color:#666;">{{job_title}}</span><br>
<strong>{{company}}</strong><br>
<a href="mailto:{{email}}" style="color:#0066cc;">{{email}}</a> &nbsp;|&nbsp; {{phone}}
</td>
</tr>
<tr><td colspan="2" style="padding-top:10px;">{{banner}}</td></tr>
</table>';
    }

    public function allowed_html() {
        $allowed = wp_kses_allowed_html( 'post' );
        $allowed['img'] = array(
            'src'    => true,
            'alt'    => true,
            'width'  => true,
            'height' => true,
            'style'  => true,
            'border' => true,
        );
        $allowed['table'] = array(
            'cellpadding' => true,
            'cellspacing' => true,
            'border'      => true,
            'style'       => true,
            'width'       => true,
        );
        $allowed['tr'] = array( 'style' => true );
        $allowed['td'] = array( 'colspan' => true, 'rowspan' => true, 'style' => true, 'valign' => true, 'align' => true, 'width' => true );
        $allowed['th'] = array( 'colspan' => true, 'rowspan' => true, 'style' => true );
        $allowed['a']  = array( 'href' => true, 'style' => true, 'target' => true, 'rel' => true, 'title' => true );
        $allowed['span'] = array( 'style' => true, 'class' => true );
        $allowed['strong'] = array( 'style' => true );
        $allowed['br'] = array();
        $allowed['p']  = array( 'style' => true );
        return $allowed;
    }

    public function safe_output( $html ) {
        return wp_kses( $html, $this->allowed_html() );
    }
}
