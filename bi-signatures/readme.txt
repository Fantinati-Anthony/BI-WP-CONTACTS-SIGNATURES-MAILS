=== BI Contacts & Signatures Mails ===
Contributors: bi
Tags: signatures, email, contacts, import, csv
Requires at least: 5.8
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gestion avancée de contacts, modèles de signatures email dynamiques, bannières à URL stable, import CSV/XLS et envoi par email.

== Description ==

* CPT: contact_signature, signature_template, banner
* Variables: {{first_name}}, {{last_name}}, {{full_name}}, {{email}}, {{phone}}, {{job_title}}, {{company}}, {{logo}}, {{banner}}
* Bannières dynamiques: URL /signature-banner/{slug}
* Import CSV/XLS avec mapping des colonnes
* Shortcodes:
  * [signature contact_id="123"]
  * [signature user="current"]
  * [bi_signature_form]
  * [bi_signature_list]
  * [bi_template_form]
* REST: /wp-json/signature/v1/render
* GET dynamique: /signature?first_name=John&last_name=Doe&phone=123

== Installation ==

1. Copier le dossier `bi-signatures` dans `wp-content/plugins`.
2. Activer le plugin.
3. Visiter Réglages > Permaliens puis sauvegarder (pour les routes).

== Changelog ==

= 1.0.0 =
* Version initiale.
