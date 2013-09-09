<?php

class WCPP_Dependencies{
    private $missing = array();

    /**
     * Constructor
     */    
    function __construct(){}      
      
    /**
     * Check for dependencies
     */    
    function check(){
        $allok = true;
        
        if(!class_exists('woocommerce')){
            $this->missing['WooCommerce'] = 'http://www.woothemes.com/woocommerce/';
            $allok = false;
        }

        if (!$allok) {
            add_action('admin_notices', array($this, '_missing_plugins_warning'));
            return false;
        }
    }

    /**
    * Adds admin notice.
    */
    function _missing_plugins_warning(){
        $missing = '';
        $counter = 0;
        foreach ($this->missing as $title => $url) {
            $counter ++;
            if ($counter == sizeof($this->missing)) {                
                $sep = '';
            } elseif ($counter == sizeof($this->missing) - 1) {              
                $sep = ' ' . __('and', 'woocommerce-product-plus') . ' ';
            } else {                    
                $sep = ', ';
            }
            $missing .= '<a href="' . $url . '">' . $title . '</a>' . $sep;              
        }
        
        ?>
        <div class="message error"><p><?php printf(__('WooCommerce Product Plus is enabled but not effective. It requires %s in order to work.', 'woocommerce-product-plus'), $missing); ?></p></div>
        <?php
    }
}

?>
