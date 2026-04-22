<?php
namespace BI_Signatures\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Presets {

    public static function all() {
        return array(
            'classic_2cols'    => array(
                'label'       => __( 'Classique 2 colonnes', 'bi-signatures' ),
                'description' => __( 'Logo à gauche, infos à droite, bannière pleine largeur dessous.', 'bi-signatures' ),
                'html'        => self::classic_2cols(),
            ),
            'vertical_bar'     => array(
                'label'       => __( 'Séparateur vertical', 'bi-signatures' ),
                'description' => __( '2 colonnes avec trait vertical coloré entre les deux.', 'bi-signatures' ),
                'html'        => self::vertical_bar(),
            ),
            'vertical_centered' => array(
                'label'       => __( 'Vertical centré', 'bi-signatures' ),
                'description' => __( 'Logo centré en haut, infos centrées, bannière en bas.', 'bi-signatures' ),
                'html'        => self::vertical_centered(),
            ),
            'three_cols'       => array(
                'label'       => __( '3 colonnes', 'bi-signatures' ),
                'description' => __( 'Logo | identité | coordonnées, séparés par des traits.', 'bi-signatures' ),
                'html'        => self::three_cols(),
            ),
            'banner_top'       => array(
                'label'       => __( 'Bannière en haut', 'bi-signatures' ),
                'description' => __( 'Bannière pleine largeur en haut, puis 2 colonnes logo / infos.', 'bi-signatures' ),
                'html'        => self::banner_top(),
            ),
            'minimal_inline'   => array(
                'label'       => __( 'Minimaliste 1 ligne', 'bi-signatures' ),
                'description' => __( 'Une seule ligne de texte, sans logo ni bannière.', 'bi-signatures' ),
                'html'        => self::minimal_inline(),
            ),
            'card'             => array(
                'label'       => __( 'Carte (card)', 'bi-signatures' ),
                'description' => __( 'Fond coloré, coins arrondis, mise en avant de la société.', 'bi-signatures' ),
                'html'        => self::card(),
            ),
            'mobile_compact'   => array(
                'label'       => __( 'Compact mobile-first', 'bi-signatures' ),
                'description' => __( 'Empilé verticalement, optimisé smartphone.', 'bi-signatures' ),
                'html'        => self::mobile_compact(),
            ),
        );
    }

    public static function get( $id ) {
        $all = self::all();
        return isset( $all[ $id ] ) ? $all[ $id ] : null;
    }

    private static function classic_2cols() {
        return '<table cellpadding="0" cellspacing="0" border="0" style="font-family:Arial,sans-serif;font-size:13px;color:#333;max-width:600px;">
<tr>
<td style="padding-right:15px;vertical-align:top;width:120px;">{{logo}}</td>
<td style="vertical-align:top;">
<strong style="font-size:15px;color:#000;">{{full_name}}</strong><br>
<span style="color:#666;">{{job_title}}</span><br>
<strong>{{company}}</strong><br>
<a href="mailto:{{email}}" style="color:#0066cc;">{{email}}</a> | {{phone}}
</td>
</tr>
<tr><td colspan="2" style="padding-top:12px;">{{banner}}</td></tr>
</table>';
    }

    private static function vertical_bar() {
        return '<table cellpadding="0" cellspacing="0" border="0" style="font-family:Arial,sans-serif;font-size:13px;color:#333;max-width:600px;">
<tr>
<td style="padding-right:15px;vertical-align:middle;width:120px;">{{logo}}</td>
<td style="border-left:3px solid #0066cc;padding-left:15px;vertical-align:middle;">
<strong style="font-size:15px;color:#000;">{{full_name}}</strong><br>
<span style="color:#666;">{{job_title}} — {{company}}</span><br>
<a href="mailto:{{email}}" style="color:#0066cc;">{{email}}</a><br>
{{phone}}
</td>
</tr>
<tr><td colspan="2" style="padding-top:12px;">{{banner}}</td></tr>
</table>';
    }

    private static function vertical_centered() {
        return '<table cellpadding="0" cellspacing="0" border="0" align="center" style="font-family:Arial,sans-serif;font-size:13px;color:#333;text-align:center;max-width:500px;">
<tr><td style="padding-bottom:8px;">{{logo}}</td></tr>
<tr><td><strong style="font-size:16px;color:#000;">{{full_name}}</strong></td></tr>
<tr><td style="color:#666;">{{job_title}}</td></tr>
<tr><td><strong>{{company}}</strong></td></tr>
<tr><td style="padding-top:4px;"><a href="mailto:{{email}}" style="color:#0066cc;">{{email}}</a> | {{phone}}</td></tr>
<tr><td style="padding-top:12px;">{{banner}}</td></tr>
</table>';
    }

    private static function three_cols() {
        return '<table cellpadding="0" cellspacing="0" border="0" style="font-family:Arial,sans-serif;font-size:13px;color:#333;max-width:700px;">
<tr>
<td style="padding-right:15px;vertical-align:middle;width:120px;">{{logo}}</td>
<td style="padding:0 15px;border-left:1px solid #ddd;border-right:1px solid #ddd;vertical-align:middle;">
<strong style="font-size:15px;color:#000;">{{full_name}}</strong><br>
<span style="color:#666;">{{job_title}}</span><br>
<strong>{{company}}</strong>
</td>
<td style="padding-left:15px;vertical-align:middle;">
<a href="mailto:{{email}}" style="color:#0066cc;">{{email}}</a><br>
{{phone}}
</td>
</tr>
<tr><td colspan="3" style="padding-top:12px;">{{banner}}</td></tr>
</table>';
    }

    private static function banner_top() {
        return '<table cellpadding="0" cellspacing="0" border="0" style="font-family:Arial,sans-serif;font-size:13px;color:#333;max-width:600px;">
<tr><td colspan="2" style="padding-bottom:12px;">{{banner}}</td></tr>
<tr>
<td style="padding-right:15px;vertical-align:top;width:120px;">{{logo}}</td>
<td style="vertical-align:top;">
<strong style="font-size:15px;color:#000;">{{full_name}}</strong><br>
<span style="color:#666;">{{job_title}}</span><br>
<strong>{{company}}</strong><br>
<a href="mailto:{{email}}" style="color:#0066cc;">{{email}}</a> | {{phone}}
</td>
</tr>
</table>';
    }

    private static function minimal_inline() {
        return '<p style="font-family:Arial,sans-serif;font-size:13px;color:#333;margin:0;">
<strong>{{full_name}}</strong> — {{job_title}} — <strong>{{company}}</strong> — <a href="mailto:{{email}}" style="color:#0066cc;">{{email}}</a> — {{phone}}
</p>';
    }

    private static function card() {
        return '<table cellpadding="0" cellspacing="0" border="0" style="font-family:Arial,sans-serif;font-size:13px;color:#333;background:#f5f7fa;border-radius:8px;max-width:600px;">
<tr>
<td style="padding:20px;">
<table cellpadding="0" cellspacing="0" border="0" width="100%">
<tr>
<td style="padding-right:15px;vertical-align:middle;width:100px;">{{logo}}</td>
<td style="vertical-align:middle;">
<strong style="font-size:16px;color:#1a1a1a;">{{full_name}}</strong><br>
<span style="color:#666;">{{job_title}}</span><br>
<strong style="color:#0066cc;">{{company}}</strong><br>
<a href="mailto:{{email}}" style="color:#0066cc;">{{email}}</a> | {{phone}}
</td>
</tr>
</table>
<div style="margin-top:15px;">{{banner}}</div>
</td>
</tr>
</table>';
    }

    private static function mobile_compact() {
        return '<table cellpadding="0" cellspacing="0" border="0" style="font-family:Arial,sans-serif;font-size:14px;color:#333;max-width:340px;">
<tr><td style="padding-bottom:8px;">{{logo}}</td></tr>
<tr><td><strong style="font-size:15px;">{{full_name}}</strong></td></tr>
<tr><td style="color:#666;">{{job_title}}</td></tr>
<tr><td><strong>{{company}}</strong></td></tr>
<tr><td><a href="mailto:{{email}}" style="color:#0066cc;">{{email}}</a></td></tr>
<tr><td>{{phone}}</td></tr>
<tr><td style="padding-top:10px;">{{banner}}</td></tr>
</table>';
    }
}
