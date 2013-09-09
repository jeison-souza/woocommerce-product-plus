<?php

/**
 * Base class
 */
if ( !class_exists( 'WooCommerce_Product_Plus' ) ) {

    class WooCommerce_Product_Plus {

        var $settings;

        public static $plugin_prefix;
        public static $plugin_url;
        public static $plugin_path;
        public static $plugin_basefile;

        public $wc_settings;
        
        /**
         * Constructor
         */
        public function __construct() {
            self::$plugin_prefix = 'wcpp_';
            self::$plugin_basefile = str_replace('/classes', '', plugin_basename(__FILE__));
            self::$plugin_url = plugin_dir_url(self::$plugin_basefile);
            self::$plugin_path = trailingslashit(dirname(str_replace('/classes', '', __FILE__)));
            
            add_action('plugins_loaded', array($this, 'load'), 2);
        }

        /**
         * Load the hooks
         */
        public function load() {
            $this->load_localisation();

            $this->settings = $this->get_settings();

            $WCPP_Dependencies = new WCPP_Dependencies;
            $WCPP_Dependencies->check();
            
            new WCPP_Requests;
                    
            // load the hooks
            add_action( 'init', array( $this, 'load_hooks' ) );
            add_action( 'admin_init', array( $this, 'load_admin_hooks' ) );

            if(is_admin()){        
                //add_action('admin_footer', array($this, 'documentation_links'));
                add_action('admin_notices', array($this, 'admin_notice_after_install'));
            }
        }

        /**
         * Update the settings
         */
        function update_settings($settings = null){
            if(is_null($settings)){
                $settings = $this->settings;
            }
            update_option('_wcpp_settings', $settings);        
        }

        /**
         * Load the settings
         */
        function get_settings(){

            $defaults = array(
                'dismiss_doc_main'             => 0 
            );

            if(empty($this->settings)){
                $this->settings = get_option('_wcpp_settings');
            }

            foreach($defaults as $key => $value){
                if(!isset($this->settings[$key])){
                    $this->settings[$key] = $value;
                }
            }

            return $this->settings;
        }        
        
        /**
         * Load the main plugin classes and functions
         */
        public function includes() {
                include_once( 'settings.class.php' );
        }

        /**
         * Load the localisation 
         */
        public function load_localisation() {	
            load_plugin_textdomain( 'woocommerce-product-plus', false, dirname( self::$plugin_basefile ) . '/../../languages/woocommerce-product-plus/' );
            load_plugin_textdomain( 'woocommerce-product-plus', false, dirname( self::$plugin_basefile ) . '/languages' );
        }     
        
        /**
         * Load the init hooks
         */
        public function load_hooks() {	
            if ( $this->is_woocommerce_activated() ) {
                $this->includes();
                //$this->writepanel = new WooCommerce_Delivery_Notes_Writepanel();
                //$this->writepanel->load();
                $this->wc_settings = new WooCommerce_Product_Plus_Settings();
                $this->wc_settings->load();
                //$this->print = new WooCommerce_Delivery_Notes_Print();
                //$this->print->load();
            }
        }
        
        /**
         * Load the admin hooks
         */
        public function load_admin_hooks() {
            if ( $this->is_woocommerce_activated() ) {
                add_filter( 'plugin_row_meta', array( $this, 'add_support_links' ), 10, 2 );			
                add_filter( 'plugin_action_links_' . self::$plugin_basefile, array( $this, 'add_settings_link') );
            }
        }

        /**
         * Add various support links to plugin page
         */
        public function add_support_links( $links, $file ) {
            /*
            if ( !current_user_can( 'install_plugins' ) ) {
                    return $links;
            }

            if ( $file == WooCommerce_Delivery_Notes::$plugin_basefile ) {
                    $links[] = '<a href="http://wordpress.org/extend/plugins/woocommerce-delivery-notes/faq/" target="_blank" title="' . __( 'FAQ', 'woocommerce-delivery-notes' ) . '">' . __( 'FAQ', 'woocommerce-delivery-notes' ) . '</a>';
                    $links[] = '<a href="http://wordpress.org/support/plugin/woocommerce-delivery-notes" target="_blank" title="' . __( 'Support', 'woocommerce-delivery-notes' ) . '">' . __( 'Support', 'woocommerce-delivery-notes' ) . '</a>';
            }
            */
            return $links;
        }

        /**
         * Add settings link to plugin page
         */
        public function add_settings_link( $links ) {
            /*
            $settings = sprintf( '<a href="%s" title="%s">%s</a>' , admin_url( 'admin.php?page=woocommerce&tab=' . $this->settings->tab_name ) , __( 'Go to the settings page', 'woocommerce-delivery-notes' ) , __( 'Settings', 'woocommerce-delivery-notes' ) );
            array_unshift( $links, $settings );
            */
            return $links;	
        }

        /**
         * Check if woocommerce is activated
         */
        public function is_woocommerce_activated() {
            $blog_plugins = get_option( 'active_plugins', array() );
            $site_plugins = get_site_option( 'active_sitewide_plugins', array() );

            if ( in_array( 'woocommerce/woocommerce.php', $blog_plugins ) || isset( $site_plugins['woocommerce/woocommerce.php'] ) ) {
                    return true;
            } else {
                    return false;
            }
        }    
        
        function admin_notice_after_install(){
            if(''.($this->settings['dismiss_doc_main']) != 'yes'){
                $url = $_SERVER['REQUEST_URI'];
                $pos = strpos($url, '?');

                if($pos !== false){
                    $url .= '&wcpp_action=dismiss';
                } else {
                    $url .= '?wcpp_action=dismiss';
                }
                
                ?>
                <div id="message" class="updated message fade" style="clear:both;margin-top:5px;">
                    <p>
                        <?php _e('WooCommerce Product Plus is installed. Would you like to see a quick overview?', 'woocommerce-product-plus'); ?>
                    </p>
                    <p>
                        <a class="button-primary" href="http://wpml.org/documentation/related-projects/woocommerce-multilingual/" target="_blank"><?php _e('Learn how to use Product Plus for WooCommerce', 'woocommerce-product-plus') ?></a>
                        <a class="button-secondary" href="<?php echo $url; ?>"><?php _e('Dismiss', 'woocommerce-product-plus') ?></a>
                    </p>
                </div>
                <?php
            }
        }        
    }
}
?>
