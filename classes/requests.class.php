<?php
class WCPP_Requests{
    function __construct(){
        add_action('init', array($this, 'run'));
    }
    
    function run(){
        global $woocommerce_product_plus;
        
        if(isset($_GET['wcpp_action']) && $_GET['wcpp_action'] = 'dismiss'){
            $woocommerce_product_plus->settings['dismiss_doc_main'] = 'yes';
            $woocommerce_product_plus->update_settings();
        }
    }
}
?>
