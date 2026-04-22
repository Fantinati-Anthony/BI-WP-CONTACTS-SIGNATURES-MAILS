<?php
namespace BI_Signatures\Import;

use BI_Signatures\Core\Post_Types;
use BI_Signatures\Core\Meta_Fields;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Importer {

    const NONCE = 'bi_sig_import';

    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function register_hooks() {
        add_action( 'admin_menu', array( $this, 'register_page' ), 20 );
        add_action( 'admin_post_bi_sig_import_upload', array( $this, 'handle_upload' ) );
        add_action( 'admin_post_bi_sig_import_process', array( $this, 'handle_process' ) );
    }

    public function register_page() {
        add_submenu_page(
            'bi-signatures',
            __( 'Import CSV / XLS', 'bi-signatures' ),
            __( 'Import', 'bi-signatures' ),
            'manage_options',
            'bi-signatures-import',
            array( $this, 'render' )
        );
    }

    public function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $step    = isset( $_GET['step'] ) ? sanitize_key( wp_unslash( $_GET['step'] ) ) : 'upload';
        $message = isset( $_GET['bi_sig_msg'] ) ? sanitize_text_field( wp_unslash( $_GET['bi_sig_msg'] ) ) : '';

        echo '<div class="wrap"><h1>' . esc_html__( 'Import CSV / XLS', 'bi-signatures' ) . '</h1>';
        if ( $message ) {
            echo '<div class="notice notice-info"><p>' . esc_html( $message ) . '</p></div>';
        }

        if ( 'map' === $step ) {
            $this->render_mapping_step();
        } else {
            $this->render_upload_step();
        }
        echo '</div>';
    }

    private function render_upload_step() {
        $saved_mappings = get_option( 'bi_sig_import_mappings', array() );
        ?>
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="bi_sig_import_upload" />
            <?php wp_nonce_field( self::NONCE ); ?>
            <table class="form-table">
                <tr>
                    <th><label for="bi_sig_file"><?php esc_html_e( 'Fichier (CSV, XLS, XLSX)', 'bi-signatures' ); ?></label></th>
                    <td>
                        <input type="file" name="bi_sig_file" id="bi_sig_file" accept=".csv,.xls,.xlsx,text/csv" required />
                        <p class="description"><?php esc_html_e( 'La première ligne doit contenir les en-têtes de colonnes.', 'bi-signatures' ); ?></p>
                    </td>
                </tr>
                <?php if ( ! empty( $saved_mappings ) ) : ?>
                <tr>
                    <th><label for="bi_sig_mapping_name"><?php esc_html_e( 'Mapping sauvegardé', 'bi-signatures' ); ?></label></th>
                    <td>
                        <select name="bi_sig_mapping_name" id="bi_sig_mapping_name">
                            <option value=""><?php esc_html_e( '— Nouveau mapping —', 'bi-signatures' ); ?></option>
                            <?php foreach ( $saved_mappings as $name => $map ) : ?>
                                <option value="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $name ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <?php endif; ?>
            </table>
            <?php submit_button( __( 'Téléverser et mapper', 'bi-signatures' ) ); ?>
        </form>
        <?php
    }

    private function render_mapping_step() {
        $session = $this->get_session();
        if ( empty( $session ) ) {
            echo '<p>' . esc_html__( 'Aucun import en cours.', 'bi-signatures' ) . '</p>';
            return;
        }

        $headers = $session['headers'];
        $rows    = array_slice( $session['rows'], 0, 5 );
        $mapping = isset( $session['mapping'] ) ? $session['mapping'] : array();

        $fields = array(
            ''            => __( '— Ignorer —', 'bi-signatures' ),
            'first_name'  => __( 'Prénom', 'bi-signatures' ),
            'last_name'   => __( 'Nom', 'bi-signatures' ),
            'full_name'   => __( 'Nom complet (split auto)', 'bi-signatures' ),
            'email'       => __( 'Email', 'bi-signatures' ),
            'phone'       => __( 'Téléphone', 'bi-signatures' ),
            'job_title'   => __( 'Fonction', 'bi-signatures' ),
            'company'     => __( 'Société', 'bi-signatures' ),
            'logo'        => __( 'Logo (URL)', 'bi-signatures' ),
            'banner_slug' => __( 'Slug bannière', 'bi-signatures' ),
            'user_id'     => __( 'ID utilisateur WP', 'bi-signatures' ),
        );
        ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="bi_sig_import_process" />
            <?php wp_nonce_field( self::NONCE ); ?>

            <h2><?php esc_html_e( 'Mapping des colonnes', 'bi-signatures' ); ?></h2>
            <table class="widefat striped">
                <thead><tr>
                    <th><?php esc_html_e( 'Colonne CSV', 'bi-signatures' ); ?></th>
                    <th><?php esc_html_e( 'Champ WordPress', 'bi-signatures' ); ?></th>
                    <th><?php esc_html_e( 'Aperçu', 'bi-signatures' ); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ( $headers as $i => $header ) :
                    $preview = array();
                    foreach ( $rows as $r ) {
                        $preview[] = isset( $r[ $i ] ) ? $r[ $i ] : '';
                    }
                    $selected = isset( $mapping[ $i ] ) ? $mapping[ $i ] : $this->guess_field( $header, $fields );
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html( $header ); ?></strong></td>
                        <td>
                            <select name="mapping[<?php echo esc_attr( $i ); ?>]">
                                <?php foreach ( $fields as $key => $label ) : ?>
                                    <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $selected, $key ); ?>><?php echo esc_html( $label ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td><?php echo esc_html( implode( ' | ', array_slice( $preview, 0, 3 ) ) ); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <p>
                <label><input type="checkbox" name="update_existing" value="1" checked /> <?php esc_html_e( 'Mettre à jour les contacts existants (match par email)', 'bi-signatures' ); ?></label><br>
                <label><input type="checkbox" name="split_full_name" value="1" checked /> <?php esc_html_e( 'Fractionner automatiquement "nom complet" en prénom / nom', 'bi-signatures' ); ?></label>
            </p>
            <p>
                <label><?php esc_html_e( 'Sauvegarder ce mapping sous le nom :', 'bi-signatures' ); ?>
                    <input type="text" name="save_mapping_name" />
                </label>
            </p>

            <?php submit_button( __( 'Lancer l\'import', 'bi-signatures' ) ); ?>
        </form>
        <?php
    }

    private function guess_field( $header, $fields ) {
        $h = strtolower( trim( $header ) );
        $map = array(
            'first_name' => array( 'first name', 'prenom', 'prénom', 'firstname', 'first' ),
            'last_name'  => array( 'last name', 'nom', 'lastname', 'last', 'surname' ),
            'full_name'  => array( 'full name', 'nom complet', 'fullname', 'name' ),
            'email'      => array( 'email', 'e-mail', 'mail', 'courriel' ),
            'phone'      => array( 'phone', 'tel', 'tél', 'téléphone', 'telephone', 'mobile' ),
            'job_title'  => array( 'job', 'fonction', 'poste', 'title', 'job title' ),
            'company'    => array( 'company', 'societe', 'société', 'entreprise' ),
            'logo'       => array( 'logo', 'logo url' ),
            'banner_slug' => array( 'banner', 'banniere', 'bannière', 'banner slug' ),
            'user_id'    => array( 'user', 'user id', 'utilisateur' ),
        );
        foreach ( $map as $field => $aliases ) {
            if ( in_array( $h, $aliases, true ) ) {
                return $field;
            }
        }
        return '';
    }

    public function handle_upload() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Permission refusée', 'bi-signatures' ) );
        }
        check_admin_referer( self::NONCE );

        if ( empty( $_FILES['bi_sig_file']['tmp_name'] ) ) {
            $this->redirect( 'upload', __( 'Veuillez sélectionner un fichier.', 'bi-signatures' ) );
        }

        $file = $_FILES['bi_sig_file'];
        $ext  = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );

        $rows = array();
        if ( 'csv' === $ext ) {
            $rows = $this->read_csv( $file['tmp_name'] );
        } elseif ( in_array( $ext, array( 'xls', 'xlsx' ), true ) ) {
            $rows = $this->read_xls( $file['tmp_name'] );
            if ( false === $rows ) {
                $this->redirect( 'upload', __( 'Format XLS/XLSX non supporté : PhpSpreadsheet requis. Exportez en CSV.', 'bi-signatures' ) );
            }
        } else {
            $this->redirect( 'upload', __( 'Format non supporté.', 'bi-signatures' ) );
        }

        if ( count( $rows ) < 2 ) {
            $this->redirect( 'upload', __( 'Fichier vide ou sans données.', 'bi-signatures' ) );
        }

        $headers = array_shift( $rows );
        $session = array(
            'headers' => $headers,
            'rows'    => $rows,
            'mapping' => array(),
        );

        $mapping_name = isset( $_POST['bi_sig_mapping_name'] ) ? sanitize_text_field( wp_unslash( $_POST['bi_sig_mapping_name'] ) ) : '';
        if ( $mapping_name ) {
            $saved = get_option( 'bi_sig_import_mappings', array() );
            if ( isset( $saved[ $mapping_name ] ) ) {
                $session['mapping'] = $saved[ $mapping_name ];
            }
        }

        $this->set_session( $session );

        wp_safe_redirect( add_query_arg( array(
            'page' => 'bi-signatures-import',
            'step' => 'map',
        ), admin_url( 'admin.php' ) ) );
        exit;
    }

    public function handle_process() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Permission refusée', 'bi-signatures' ) );
        }
        check_admin_referer( self::NONCE );

        $session = $this->get_session();
        if ( empty( $session ) ) {
            $this->redirect( 'upload', __( 'Session expirée.', 'bi-signatures' ) );
        }

        $mapping = isset( $_POST['mapping'] ) && is_array( $_POST['mapping'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['mapping'] ) ) : array();
        $update_existing = ! empty( $_POST['update_existing'] );
        $split_full_name = ! empty( $_POST['split_full_name'] );

        $save_name = isset( $_POST['save_mapping_name'] ) ? sanitize_text_field( wp_unslash( $_POST['save_mapping_name'] ) ) : '';
        if ( $save_name ) {
            $saved = get_option( 'bi_sig_import_mappings', array() );
            $saved[ $save_name ] = $mapping;
            update_option( 'bi_sig_import_mappings', $saved );
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach ( $session['rows'] as $row ) {
            $values = array();
            foreach ( $mapping as $idx => $field ) {
                if ( empty( $field ) ) continue;
                $val = isset( $row[ $idx ] ) ? trim( (string) $row[ $idx ] ) : '';
                if ( 'full_name' === $field ) {
                    if ( $split_full_name ) {
                        $parts = preg_split( '/\s+/', $val, 2 );
                        $values['first_name'] = isset( $parts[0] ) ? $parts[0] : '';
                        $values['last_name']  = isset( $parts[1] ) ? $parts[1] : '';
                    } else {
                        $values['last_name'] = $val;
                    }
                } else {
                    $values[ $field ] = $val;
                }
            }

            if ( empty( $values['email'] ) || ! is_email( $values['email'] ) ) {
                $skipped++;
                continue;
            }

            $existing = null;
            if ( $update_existing ) {
                $query = new \WP_Query( array(
                    'post_type'      => Post_Types::CONTACT,
                    'posts_per_page' => 1,
                    'meta_query'     => array(
                        array(
                            'key'   => '_bi_sig_email',
                            'value' => sanitize_email( $values['email'] ),
                        ),
                    ),
                    'no_found_rows'  => true,
                    'fields'         => 'ids',
                ) );
                if ( $query->have_posts() ) {
                    $existing = (int) $query->posts[0];
                }
            }

            $title = trim( ( isset( $values['first_name'] ) ? $values['first_name'] : '' ) . ' ' . ( isset( $values['last_name'] ) ? $values['last_name'] : '' ) );
            if ( empty( $title ) ) {
                $title = $values['email'];
            }

            if ( $existing ) {
                Meta_Fields::save_contact( $existing, $values );
                $updated++;
            } else {
                $post_id = wp_insert_post( array(
                    'post_type'   => Post_Types::CONTACT,
                    'post_status' => 'publish',
                    'post_title'  => $title,
                ) );
                if ( $post_id && ! is_wp_error( $post_id ) ) {
                    Meta_Fields::save_contact( $post_id, $values );
                    $created++;
                } else {
                    $skipped++;
                }
            }
        }

        $this->clear_session();
        $msg = sprintf( __( 'Import terminé : %1$d créés, %2$d mis à jour, %3$d ignorés.', 'bi-signatures' ), $created, $updated, $skipped );
        $this->redirect( 'upload', $msg );
    }

    private function read_csv( $path ) {
        $rows = array();
        $fh = fopen( $path, 'r' );
        if ( ! $fh ) return array();
        $delimiter = $this->detect_delimiter( $path );
        while ( ( $row = fgetcsv( $fh, 0, $delimiter ) ) !== false ) {
            $rows[] = $row;
        }
        fclose( $fh );
        return $rows;
    }

    private function detect_delimiter( $path ) {
        $line = '';
        $fh = fopen( $path, 'r' );
        if ( $fh ) {
            $line = fgets( $fh );
            fclose( $fh );
        }
        $candidates = array( ',' => 0, ';' => 0, "\t" => 0, '|' => 0 );
        foreach ( $candidates as $c => $_ ) {
            $candidates[ $c ] = substr_count( $line, $c );
        }
        arsort( $candidates );
        $first = array_key_first( $candidates );
        return $candidates[ $first ] > 0 ? $first : ',';
    }

    private function read_xls( $path ) {
        if ( ! class_exists( '\\PhpOffice\\PhpSpreadsheet\\IOFactory' ) ) {
            return false;
        }
        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile( $path );
            $reader->setReadDataOnly( true );
            $spreadsheet = $reader->load( $path );
            $rows = $spreadsheet->getActiveSheet()->toArray( null, true, true, false );
            return $rows;
        } catch ( \Throwable $e ) {
            return false;
        }
    }

    private function get_session() {
        $key = $this->session_key();
        return get_transient( $key );
    }

    private function set_session( $data ) {
        set_transient( $this->session_key(), $data, HOUR_IN_SECONDS );
    }

    private function clear_session() {
        delete_transient( $this->session_key() );
    }

    private function session_key() {
        return 'bi_sig_import_' . get_current_user_id();
    }

    private function redirect( $step, $msg ) {
        wp_safe_redirect( add_query_arg( array(
            'page'       => 'bi-signatures-import',
            'step'       => $step,
            'bi_sig_msg' => rawurlencode( $msg ),
        ), admin_url( 'admin.php' ) ) );
        exit;
    }
}
