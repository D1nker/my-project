<?php
/*
Plugin Name: Poll
 */

include_once plugin_dir_path( __FILE__ ).'/pollwidget.php';

/**
 * Classe Poll_Plugin
 * Déclare le plugin
 */
class Poll_Plugin
{
    /**
     * Nom de base des tables
     */
    const OPTIONS_TABLE = 'poll_options';
    const RESULTS_TABLE = 'poll_results';

    /**
     * Nom des tables avec préfixe
     */
    protected $options_table;
    protected $results_table;

    /**
     * Constructeur
     */
    public function __construct()
    {
        add_action('widgets_init', function() {register_widget('Poll_Widget');});
        add_action('wp_loaded', array($this, 'save_vote'));

        register_activation_hook(__FILE__, array(get_class(), 'install'));
        register_uninstall_hook(__FILE__, array(get_class(), 'uninstall'));

        add_action('admin_menu', array($this, 'add_admin_menu'), 20);

        global $wpdb;
        $this->options_table = $wpdb->prefix.self::OPTIONS_TABLE;
        $this->results_table = $wpdb->prefix.self::RESULTS_TABLE;

    }

    /**
     * Fonction d'installation
     * Création des tables
     */
    public static function install()
    {
        /** @var $wpdb wpdb */
        global $wpdb;

        $wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->prefix.self::OPTIONS_TABLE." (id INT AUTO_INCREMENT PRIMARY KEY, label VARCHAR(255) NOT NULL)");
        $wpdb->query("CREATE TABLE IF NOT EXISTS ".$wpdb->prefix.self::RESULTS_TABLE." (option_id INT NOT NULL, total INT NOT NULL)");

    }

    /**
     * Fonction de désinstallation
     * Suppression des tables du sondage
     */
    public static function uninstall()
    {
        global $wpdb;

        $wpdb->query("DROP TABLE ".$wpdb->prefix.self::OPTIONS_TABLE);
        $wpdb->query("DROP TABLE ".$wpdb->prefix.self::RESULTS_TABLE);
    }

    /**
     * Enregistrement d'un vote
     */
    public function save_vote()
    {
        if (isset($_POST['poll_vote'])) {
            /** @var $wpdb wpdb */
            global $wpdb;
            $vote = (int) $_POST['poll_vote'];
            $row = $wpdb->get_row("SELECT * FROM {$this->results_table} WHERE option_id =$vote");
            if (!$row) {
                // si la ligne de résultat n'existe pas pour cette option, on la créé
                $wpdb->insert('wp_poll_results', array('option_id' => $vote, 'total' => 1));
            } else {
                $total = $row->total+1;
                $wpdb->update('wp_poll_results', array('total' => $total), array('option_id' => $vote));
            }
            // on place le cookie pour ne voter qu'une fois par heure au maximum
            setcookie('voted', 1, time() + 3600);
            // on fait une redirection pour éviter que l'utilisateur ne revote en rafraichissant la page
            wp_redirect($_SERVER['REQUEST_URI']);
            exit;
        }
    }

    /**
     * Ajout du menu dans l'administration
     */
    public function add_admin_menu()
    {
        $hook = add_submenu_page('options-general.php', __('Sondage'), __('Sondage'), 'manage_options', 'poll', array($this, 'menu_html'));
        add_action('load-'.$hook, array($this, 'process_action'));
    }

    /**
     * Affiche le formulaire de gestion du sondage dans l'administration
     */
    public function menu_html()
    {
        global $wpdb;
        $options = $wpdb->get_results("SELECT * FROM {$this->options_table}");
        echo '<h1>'.get_admin_page_title().'</h1>';
        ?>
        <form method="post" action="">
            <p>
                <label><?php _e('Question'); ?>
                    <input type="text" name="poll_question" value="<?php echo get_option('poll_question') ?>"/>
                </label>
            </p>
            <?php // affichage des options ?>
            <?php foreach ($options as $option): ?>
                <p>
                    <input type="text" name="poll_option_label[<?php echo $option->id ?>]" value="<?php echo $option->label ?>"/>
                </p>
            <?php endforeach; ?>
            <p>
                <label><?php _e('Ajouter une nouvelle réponse') ?>
                    <input type="text" name="poll_new_option" value=""/>
                </label>
            </p>
            <?php submit_button(); ?>
        </form>

        <form method="post" action="">
            <input type="hidden" name="poll_reset" value="1"/>
            <?php submit_button(__('Réinitiaiser les options et les résultats')); ?>
        </form>

    <?php
    }

    /**
     * Traitement des actions dans l'administration
     * Sauvegarde des options et remise à zéro des résultats
     */
    public function process_action()
    {
        /** @var $wpdb wpdb */
        global $wpdb;
        if (isset($_POST['poll_reset'])) {
            $wpdb->query("TRUNCATE {$this->results_table}");
            $wpdb->query("TRUNCATE {$this->options_table}");
        }
        if (!empty($_POST['poll_option_label'])) {
            foreach ($_POST['poll_option_label'] as $id => $label) {
                $wpdb->update($this->options_table, array('label' => $label), array('id' => $id));
            }
        }
        if (!empty($_POST['poll_new_option'])) {
            $wpdb->insert($this->options_table, array('label' => $_POST['poll_new_option']));
        }
        if (isset($_POST['poll_question'])) {
            update_option('poll_question', $_POST['poll_question']);
        }
    }
}

new Poll_Plugin();
