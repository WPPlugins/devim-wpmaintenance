<?php
/*
 * Plugin Name: Devim - WPMaintenance
 * Plugin URI: https://github.com/devimweb/devim-wpmaintenance
 * Description: Add a page that prevents your site's content view. Ideal to report a scheduled maintenance or coming soon page.
 * Version: 1.0.0
 * Author: Miguel Müller
 * Author URI: https://github.com/miguelsneto
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: dvm-wpmaintenance
 * Domain Path: /languages/
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'DVM_WPMaintenance' ) ) :

/**
 * Wordpress Simple Maintenance Main class
 */
class DVM_WPMaintenance
{
    /**
     * Instance of this class.
     *
     * @var object
     */
    private static $instance = null;

    /**
     * Array of plugin settings
     *
     * @var array
     */
    protected $dvm_wpmaintenance_settings;

    /**
     * Return an instance of this class.
     *
     * @return object A single instance of this class.
     */
    public static function get_instance() {

        if ( null == self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;

    }

    /**
     * Initialize the plugin public actions.
     */
    private function __construct() {
        add_action( 'init', array( $this, 'load_textdomain' ) );

        $this->do_plugin_settings();

        add_action( 'admin_init', array( &$this, 'admin_init' ));
        add_action( 'admin_menu', array( &$this, 'admin_menu' ));

        if ($this->dvm_wpmaintenance_settings['status'] === 'TRUE') {
            add_filter( 'login_message', array( &$this, 'login_message' ));
            add_action( 'admin_notices', array( &$this, 'admin_notices' ));
            add_action( 'wp_loaded', array( &$this, 'apply_maintenance_mode' ));
            add_filter( 'wp_title', array( &$this, 'wpTitle' ), 9999, 2 );
        }
    }

    /**
     * Load the plugin text domain for translation.
     */
    public function load_textdomain() {
        $locale = apply_filters( 'plugin_locale', get_locale(), 'dvm-wpmaintenance' );

        load_plugin_textdomain( 'dvm-wpmaintenance', FALSE, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    /**
     * Construction of the plugin
     */
    public function do_plugin_settings() {
        if( false == get_option( 'dvm_wpmaintenance_settings' )) {
            add_option( 'dvm_wpmaintenance_settings' );
            $default = array(
                'status'           => FALSE,
                'description'      => '', // Why the maintenance mode is active
                'time_activated'   => '', // Time that has been activated
                'duration_days'    => '', // Days suspended
                'duration_hours'   => '', // Hours suspended
                'duration_minutes' => '', // Minutos suspended
                'url_allowed'      => '',
                'role_allow_front' => '',
                'role_allow_back'  => ''
            );
            update_option( 'dvm_wpmaintenance_settings', $default );
        }

        $this->dvm_wpmaintenance_settings = get_option( 'dvm_wpmaintenance_settings' );

        if (!isset($this->dvm_wpmaintenance_settings['status'])) $this->dvm_wpmaintenance_settings['status'] = FALSE;
    }

    /**
     * Cria um formulário pra ser usada pra configuração do tema
     */
    function admin_init(){
        add_settings_section(
            'section_maintenance',
            __('Configure the details of the maintenance mode', 'dvm-wpmaintenance'),
            '__return_false',
            'page-dvm-wpmaintenance'
        );

        add_settings_field(
            'status',
            __('Enable maintenance mode:', 'dvm-wpmaintenance'),
            array( &$this, 'html_input_status' ),
            'page-dvm-wpmaintenance',
            'section_maintenance'
        );

        add_settings_field(
            'description',
            __('Motive of maintenance:', 'dvm-wpmaintenance'),
            array( &$this, 'html_input_description' ),
            'page-dvm-wpmaintenance',
            'section_maintenance'
        );

        add_settings_field(
            'url_allowed',
            __('The following pages have free access:', 'dvm-wpmaintenance'),
            array( &$this, 'html_input_url_allowed' ),
            'page-dvm-wpmaintenance',
            'section_maintenance'
        );

        add_settings_field(
            'role_allow',
            __('Who can access:', 'dvm-wpmaintenance'),
            array( &$this, 'html_input_role_allow' ),
            'page-dvm-wpmaintenance',
            'section_maintenance'
        );

        register_setting(
            'page-dvm-wpmaintenance',
            'dvm_wpmaintenance_settings'
        );
    }

    /**
     *
     */
    public function html_input_status(){
        if ($this->dvm_wpmaintenance_settings['status'] == TRUE) :
            $return    = $this->calc_time_maintenance();

            $message = sprintf( __("The maintenance mode will end in <strong>%s</strong>", 'dvm-wpmaintenance'), $return['return-date'] );

            echo ("<p><span class='description'>$message</span></p><br/>");
        endif;

        $days  = $this->dvm_wpmaintenance_settings['status'] == TRUE ? $return['remaining-array']['days'] : '1';
        $hours = $this->dvm_wpmaintenance_settings['status'] == TRUE ? $return['remaining-array']['hours'] : '0';
        $mins  = $this->dvm_wpmaintenance_settings['status'] == TRUE ? $return['remaining-array']['mins'] : '0';
        ?>

        <input type="hidden" name="dvm_wpmaintenance_settings[time_activated]" value="<?php echo current_time('timestamp'); ?>">

        <label>
            <input type="checkbox" id="status" name="dvm_wpmaintenance_settings[status]" value="TRUE" <?php checked( 'TRUE', $this->dvm_wpmaintenance_settings['status'] ) ?> /> <?php _e('I want to enable', 'dvm-wpmaintenance'); ?>
        </label>

        <br/>
        <table>
            <tbody>
                <tr>
                    <td><strong><?php _e('Back in:'); ?></strong></td>
                    <td><input type="text" id="duration_days" name="dvm_wpmaintenance_settings[duration_days]" value="<?php echo $days; ?>" size="4" maxlength="5"> <label for="duration_days"><?php _e('Days', 'dvm-wpmaintenance'); ?></label></td>
                    <td><input type="text" id="duration_hours" name="dvm_wpmaintenance_settings[duration_hours]" value="<?php echo $hours; ?>" size="4" maxlength="5"> <label for="duration_hours"><?php _e('Hours', 'dvm-wpmaintenance'); ?></label></td>
                    <td><input type="text" id="duration_minutes" name="dvm_wpmaintenance_settings[duration_minutes]" value="<?php echo $mins; ?>" size="4" maxlength="5"> <label for="duration_minutes"><?php _e('Minutes', 'dvm-wpmaintenance'); ?></label></td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    /**
     *
     */
    public function html_input_description(){
        $html = '<textarea id="description" name="dvm_wpmaintenance_settings[description]" cols="80" rows="5" class="large-text">'.$this->dvm_wpmaintenance_settings['description'].'</textarea>';
        echo $html;
    }

    /**
     *
     */
    public function html_input_url_allowed(){
        $html = '<textarea id="url_allowed" name="dvm_wpmaintenance_settings[url_allowed]" cols="80" rows="5" class="large-text">'.$this->dvm_wpmaintenance_settings['url_allowed'].'</textarea>';
        $html .= '<br/><span class="description">Digite os caminhos que devem estar acessíveis mesmo em modo de manutenção. Separe os vários caminhos com quebras de linha.<br/>Exemplo: Se você quer liberar acesso á pagina <strong>http://site.com/sobre/</strong>, você deve digitar <strong>/sobre/</strong>.<br/>Dica: Se você quiser liberar acesso a página inicial digite <strong>[HOME]</strong>.</span>';
        echo $html;
    }

    /**
     *
     */
    public function html_input_role_allow(){
        //INPUT FOR ALLOW BACK
        $html = '<label>'. __('Access the administrative panel:', 'dvm-wpmaintenance');
        $html .= ' <select id="role_allow_back" name="dvm_wpmaintenance_settings[role_allow_back]">
                    <option value="manage_options" ' . selected( $this->dvm_wpmaintenance_settings['role_allow_back'], 'manage_options', false) . '>Ninguém</option>
                    <option value="manage_categories" ' . selected( $this->dvm_wpmaintenance_settings['role_allow_back'], 'manage_categories', false) . '>Editor</option>
                    <option value="publish_posts" ' . selected( $this->dvm_wpmaintenance_settings['role_allow_back'], 'publish_posts', false) . '>Autor</option>
                    <option value="edit_posts" ' . selected( $this->dvm_wpmaintenance_settings['role_allow_back'], 'edit_posts', false) . '>Colaborador</option>
                    <option value="read" ' . selected( $this->dvm_wpmaintenance_settings['role_allow_back'], 'read', false) . '>Visitante</option>
                </select>';
        $html .= '</label><br />';

        //INPUT FOR ALLOW FRONT
        $html .= '<label>'. __('Access the public site:', 'dvm-wpmaintenance');
        $html .= ' <select id="role_allow_front" name="dvm_wpmaintenance_settings[role_allow_front]">
                    <option value="manage_options" ' . selected( $this->dvm_wpmaintenance_settings['role_allow_front'], 'manage_options', false) . '>Ninguém</option>
                    <option value="manage_categories" ' . selected( $this->dvm_wpmaintenance_settings['role_allow_front'], 'manage_categories', false) . '>Editor</option>
                    <option value="publish_posts" ' . selected( $this->dvm_wpmaintenance_settings['role_allow_front'], 'publish_posts', false) . '>Autor</option>
                    <option value="edit_posts" ' . selected( $this->dvm_wpmaintenance_settings['role_allow_front'], 'edit_posts', false) . '>Colaborador</option>
                    <option value="read" ' . selected( $this->dvm_wpmaintenance_settings['role_allow_front'], 'read', false) . '>Visitante</option>
                </select>';
        $html .= '</label><br />';
        echo $html;
    }


    /**
     *
     */
    function admin_menu(){
        add_submenu_page(
            'options-general.php',
            __('Maintenance mode', 'dvm-wpmaintenance'),
            __('Maintenance mode', 'dvm-wpmaintenance'),
            'administrator',
            'page-dvm-wpmaintenance',
            array( &$this, 'html_form_settings' )
        );
    }

    /**
     *
     */
    public function html_form_settings(){
    ?>
        <div class="wrap">
            <div id="icon-options-general" class="icon32"></div>
            <h2><?php _e('General Settings'); ?></h2>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'page-dvm-wpmaintenance' );
                do_settings_sections( 'page-dvm-wpmaintenance' );
                submit_button();
                ?>
            </form>
        </div>
    <?php
    }

    /**
     *
     */
    public function calc_time_maintenance(){
        // How long will it stay off in seconds
        $time_duration = 0;
        $time_duration += intval($this->dvm_wpmaintenance_settings['duration_days']) * 24 * 60;
        $time_duration += intval($this->dvm_wpmaintenance_settings['duration_hours']) * 60;
        $time_duration += intval($this->dvm_wpmaintenance_settings['duration_minutes']);
        $time_duration = intval($time_duration * 60);

        // Timestamp of time activated, time finished, time current e time remaining
        $time_activated = intval($this->dvm_wpmaintenance_settings['time_activated']);
        $time_finished  = intval($time_activated + $time_duration);
        $time_current   = current_time('timestamp');
        $time_remaining = $time_finished - $time_current;

        // Format the date in the format defined by the system
        $return_day  = date_i18n( get_option('date_format'), $time_finished );
        $return_time = date_i18n( get_option('time_format'), $time_finished );
        $return_date = $return_day . ' ' . $return_time;

        $time_calculated = $this->calc_separate_time($time_remaining);

        return array(
            'return-date'       => $return_date,
            'remaining-seconds' => $time_remaining,
            'remaining-array'   => $time_calculated,
        );
    }


    /**
     * Calculates the days, hours and minutes remaining based on the number of seconds
     *
     * @return array Array containing the values of days, hours and minutes remaining
     */
    private function calc_separate_time($seconds){
        $minutes = round(($seconds/(60)), 0);

        $minutes = intval($minutes);
        $vals_arr = array(  'days' => (int) ($minutes / (24*60) ),
                            'hours' => $minutes / 60 % 24,
                            'mins' => $minutes % 60);

        $return_arr = array();

        $is_added = false;

        foreach ($vals_arr as $unit => $amount) {
            $return_arr[$unit] = 0;

            if ( ($amount > 0) || $is_added ) {
                $is_added          = true;
                $return_arr[$unit] = $amount;
            }
        }
        return $return_arr;
    }

    /**
     *
     */
    function apply_maintenance_mode()
    {
        if ( strstr($_SERVER['PHP_SELF'],'wp-login.php')) return;
        if ( strstr($_SERVER['PHP_SELF'], 'wp-admin/admin-ajax.php')) return;
        if ( strstr($_SERVER['PHP_SELF'], 'async-upload.php')) return;
        if ( strstr(htmlspecialchars($_SERVER['REQUEST_URI']), '/plugins/')) return;
        if ( strstr($_SERVER['PHP_SELF'], 'upgrade.php')) return;
        if ( $this->check_url_allowed()) return;

        //Never show maintenance page in wp-admin
        if ( is_admin() || strstr(htmlspecialchars($_SERVER['REQUEST_URI']), '/wp-admin/') ) {
            if ( !is_user_logged_in() ) {
                auth_redirect();
            }
            if ( $this->user_allow('admin') ) {
                return;
            } else {
                $this->display_maintenance_page();
            }
        } else {
            if( $this->user_allow('public') ) {
                return;
            } else {
                $this->display_maintenance_page();
            }
        }
    }


    /**
     *
     */
    function display_maintenance_page()
    {
        $time_maintenance = $this->calc_time_maintenance();
        $time_maintenance = $time_maintenance['remaining-seconds'];

        //Define header as unavailable
        header('HTTP/1.1 503 Service Temporarily Unavailable');
        header('Status: 503 Service Temporarily Unavailable');

        if ( $time_maintenance > 1 ) header('Retry-After: ' . $time_maintenance );

        // Check what used in page will be visitor redirect
        $file503 = get_template_directory() . '/503.php';
        if (file_exists($file503) == FALSE) {
            $file503 = dirname(  __FILE__  ) . '/503-default.php';
        }

        // Show page
        include($file503);

        exit();
    }


    /**
     *
     */
    function check_url_allowed()
    {
        $urlarray = $this->dvm_wpmaintenance_settings['url_allowed'];
        $urlarray = preg_replace("/\r|\n/s", ' ', $urlarray); //TRANSFORM BREAK LINES IN SPACE
        $urlarray = explode(' ', $urlarray); //TRANSFORM STRING IN ARRAY
        $oururl = 'http://' . $_SERVER['HTTP_HOST'] . htmlspecialchars($_SERVER['REQUEST_URI']);
        foreach ($urlarray as $expath) {
            if (!empty($expath)) {
                $expath = str_replace(' ', '', $expath);
                if (strpos($oururl, $expath) !== false) return true;
                if ( (strtoupper($expath) == '[HOME]') && ( trailingslashit(get_bloginfo('url')) == trailingslashit($oururl) ) )    return true;
            }
        }
        return false;
    }


    /**
     * Cria um formulário pra ser usada pra configuração do tema
     */
    function user_allow($where)
    {
        if ($where == 'public') {
            $optval = $this->dvm_wpmaintenance_settings['role_allow_front'];
        } elseif ($where == 'admin') {
            $optval = $this->dvm_wpmaintenance_settings['role_allow_back'];
        } else {
            return false;
        }

        if ( $optval == 'manage_options' && current_user_can('manage_options') ) { return true; }
        elseif ( $optval == 'manage_categories' && current_user_can('manage_categories') ) { return true; }
        elseif ( $optval == 'publish_posts' && current_user_can('publish_posts') ) { return true;   }
        elseif ( $optval == 'edit_posts' && current_user_can('edit_posts') ) { return true; }
        elseif ( $optval == 'read' && current_user_can('read') ) { return true; }
        else { return false; }
    }

    /**
     *
     */
    function login_message( $message ){
        $message = apply_filters( 'smaintenance_loginnotice', __('Currently this site is in MAINTENANCE MODE.', 'dvm-wpmaintenance') );

        return '<div id="login_error"><p class="text-center">'. $message .'</p></div>';
    }

    /**
     *
     */
    function admin_notices()
    {
        $edit_url = site_url() . '/wp-admin/admin.php?page=page-dvm-wpmaintenance';

        $message1 = __('Currently this site is in MAINTENANCE MODE.', 'dvm-wpmaintenance');
        $message2 = sprintf( __('To exit the maintenance mode just change the settings <a href="%s">clicking here</a>.', 'dvm-wpmaintenance'), $edit_url);

        $message = apply_filters( 'smaintenance_adminnotice', $message1. ' '. $message2 );

        echo '<div id="message" class="error"><p>'. $message .'</p></div>';
    }

    /**
     *
     */
    function wpTitle()
    {
        return get_bloginfo( 'name' ). ' | Modo Manutenção';
    }

}
add_action( 'plugins_loaded', array( 'DVM_WPMaintenance', 'get_instance' ), 0 );

endif;