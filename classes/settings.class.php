<?php

/**
 * Settings class
 */
if ( ! class_exists( 'WooCommerce_Product_Plus_Settings' ) ) {

    class WooCommerce_Product_Plus_Settings {

        public $tab_name;
        public $hidden_submit;

        /**
         * Constructor
         */
        public function __construct() {			
            $this->tab_name = 'woocommerce-product-plus';
            $this->hidden_submit = WooCommerce_Product_Plus::$plugin_prefix . 'submit';
        }

        /**
         * Load the class
         */
        public function load() {
            add_action( 'admin_init', array( $this, 'load_hooks' ) );
        }

        /**
         * Load the admin hooks
         */
        public function load_hooks() {	
            add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ) );
            add_action( 'woocommerce_settings_tabs_' . $this->tab_name, array( $this, 'create_settings_page' ) );
            add_action( 'woocommerce_update_options_' . $this->tab_name, array( $this, 'save_settings_page' ) );
            //add_action( 'current_screen', array( $this, 'load_screen_hooks' ) );
            //add_action( 'wp_ajax_load_thumbnail', array( $this, 'load_thumbnail_ajax' ) );
            add_filter( 'woocommerce_reports_charts', array ($this, 'add_report_tab') );
            
            //Display Fields
            add_action( 'woocommerce_product_after_variable_attributes', array($this, 'variable_fields'), 10, 2 );
            //Save variation fields
            add_action( 'woocommerce_process_product_meta_variable', array($this, 'variable_fields_process'), 10, 1 );
 
        }
        
        function variable_fields_process( $post_id ) {
            if (isset( $_POST['variable_sku'] ) ) :
		$variable_sku = $_POST['variable_sku'];
		$variable_post_id = $_POST['variable_post_id'];
		$variable_custom_field = $_POST['variable_qty_package'];
		for ( $i = 0; $i < sizeof( $variable_sku ); $i++ ) :
                    $variation_id = (int) $variable_post_id[$i];
                    if ( isset( $variable_custom_field[$i] ) ) {
                        update_post_meta( $variation_id, '_qty_package', stripslashes( $variable_custom_field[$i] ) );
                    }
		endfor;
            endif;
        }
        
        function variable_fields( $loop, $variation_data ) {
            if ( get_option( 'woocommerce_manage_stock' ) == 'yes' ) {
                $_qty_package = isset( $variation_data['_qty_package'][0] ) ? $variation_data['_qty_package'][0] : '';
                ?>	
                <tr>
                    <td>
                        <div>
                            <label><?php _e( 'Quantity in each package', 'woocommerce-product-plus' ); ?> <a class="tips" data-tip="<?php _e( 'Enter the quantity to debit from stock for each purchase at variation level, or leave blank to use the parent product\'s options.', 'woocommerce-product-plus' ); ?>" href="#">[?]</a></label>
                            <input type="number" size="5" name="variable_qty_package[<?php echo $loop; ?>]" value="<?php if ( isset( $_qty_package ) ) echo esc_attr( $_qty_package ); ?>" step="any"  min="1"/>
                        </div>
                    </td>
                    <td></td>
                </tr>
                <?php
            }
        }
        
        /**
         * Add a tab to the settings page
         */
        public function add_settings_tab($tabs) {
                $tabs[$this->tab_name] = __( 'Product Plus', 'woocommerce-product-plus' );

                return $tabs;
        }
        
        public function add_report_tab($charts) {
            $charts['woocommerce-product-plus'] = array(
                                                'title'  => __( 'Products', 'woocommerce-product-plus' ),
                                                'charts' => array(
                                                    "overview" => array(
                                                        'title'       => __( 'Overview', 'woocommerce-product-plus' ),
                                                        'description' => '',
                                                        'hide_title'  => true,
                                                        'function'    => array( $this, 'woocommerce_products_overview')
                                                    ),
                                                    "products-list" => array(
                                                        'title'       => __( 'Products list', 'woocommerce-product-plus' ),
                                                        'description' => '',
                                                        'hide_title'  => true,
                                                        'function'    => array( $this, 'woocommerce_products_list')
                                                    ),                                                
                                                )
                                            );
            
            return $charts;
        }
        
        /**
         * Output JavaScript for highlighting weekends on charts.
         *
         * @access public
         * @return void
         */
        public function weekend_area_js() {
               ?>
               function markWeekendAreas(axes) {
               var markings = [];
               var d = new Date(axes.xaxis.min);
               // go to the first Saturday
               d.setUTCDate(d.getUTCDate() - ((d.getUTCDay() + 1) % 7))
               d.setUTCSeconds(0);
               d.setUTCMinutes(0);
               d.setUTCHours(0);
               var i = d.getTime();
               do {
                   markings.push({ xaxis: { from: i-(12 * 60 * 60 * 1000), to: i + (2 * 24 * 60 * 60 * 1000)-(12 * 60 * 60 * 1000) } });
                   i += 7 * 24 * 60 * 60 * 1000;
               } while (i < axes.xaxis.max);

               return markings;
           }
           <?php
        }

        /**
         * Output the reports overview stats.
         *
         * @access public
         * @return void
         */
        public function woocommerce_products_overview() {
            global $start_date, $end_date, $woocommerce, $wpdb, $wp_locale;

            // Low/No stock lists
            $lowstockamount = get_option('woocommerce_notify_low_stock_amount');
            if (!is_numeric($lowstockamount)) $lowstockamount = 1;

            $nostockamount = get_option('woocommerce_notify_no_stock_amount');
            if (!is_numeric($nostockamount)) $nostockamount = 0;
            
            $total_products = 0;
            $total_variations = 0;
            $total_products_low_stock = 0;
            $total_variations_low_stock = 0;
            $total_products_no_stock = 0;
            $total_variations_no_stock = 0;
            $total_products_out_of_stock = 0;
            $total_variations_out_of_stock = 0;
            
            $args = array(
		'post_type'		=> 'product',
		'post_status' 		=> 'publish',
		'posts_per_page' 	=> -1,
                'suppress_filters'      => 0
            );

            $products = (array) get_posts($args);
            
            $total_products = (int) sizeof($products);

            $args = array(
		'post_type'		=> 'product_variation',
		'post_status' 		=> 'publish',
		'posts_per_page' 	=> -1,
                'suppress_filters'      => 0,
            );

            $variations = (array) get_posts($args);
            
            $total_variations = (int) sizeof($variations);
            
            // Get low in stock simple/downloadable/virtual products. Grouped don't have stock. Variations need a separate query.
            $args = array(
                    'post_type'			=> 'product',
                    'post_status' 		=> 'publish',
                    'posts_per_page'            => -1,
                    'suppress_filters'          => 0,
                    'meta_query' => array(
                            array(
                                    'key' 	=> '_manage_stock',
                                    'value' 	=> 'yes'
                            ),
                            array(
                                    'key' 	=> '_stock',
                                    'value' 	=> $lowstockamount,
                                    'compare' 	=> '<=',
                                    'type' 	=> 'NUMERIC'
                            ),
                            array(
                                    'key' 	=> '_stock',
                                    'value' 	=> $nostockamount,
                                    'compare' 	=> '>',
                                    'type' 	=> 'NUMERIC'
                            )
                    ),
                    'fields' => 'id=>parent'
            );

            $low_stock_products = (array) get_posts($args);

            $total_products_low_stock = (int) sizeof($low_stock_products);
            
            // Get low stock product variations
            $args = array(
                    'post_type'			=> 'product_variation',
                    'post_status' 		=> 'publish',
                    'posts_per_page'            => -1,
                    'suppress_filters'          => 0,
                    'meta_query' => array(
                            array(
                                    'key' 	=> '_stock',
                                    'value' 	=> $lowstockamount,
                                    'compare' 	=> '<=',
                                    'type' 	=> 'NUMERIC'
                            ),
                            array(
                                    'key' 	=> '_stock',
                                    'value' 	=> $nostockamount,
                                    'compare' 	=> '>',
                                    'type' 	=> 'NUMERIC'
                            ),
                            array(
                                    'key' 	=> '_stock',
                                    'value' 	=> array( '', false, null ),
                                    'compare' 	=> 'NOT IN'
                            )
                    ),
                    'fields' => 'id=>parent'
            );

            $low_stock_variations = (array) get_posts($args);

            $total_variations_low_stock = (int) sizeof($low_stock_variations);
            
            // Get products marked out of stock
            $args = array(
                    'post_type'			=> array( 'product' ),
                    'post_status' 		=> 'publish',
                    'posts_per_page'            => -1,
                    'suppress_filters'          => 0,
                    'meta_query' => array(
                            'relation' => 'AND',
                            array(
                                    'key' 	=> '_stock_status',
                                    'value' 	=> 'outofstock'
                            )
                    ),
                    'fields' => 'id=>parent'
            );

            $out_of_stock_status_products = (array) get_posts($args);
        
            $total_products_out_of_stock = (int) sizeof($out_of_stock_status_products);
                 
             // Get low in stock simple/downloadable/virtual products. Grouped don't have stock. Variations need a separate query.
            $args = array(
                    'post_type'			=> 'product',
                    'post_status' 		=> 'publish',
                    'posts_per_page'            => -1,
                    'suppress_filters'          => 0,
                    'meta_query' => array(
                            array(
                                    'key' 	=> '_manage_stock',
                                    'value' 	=> 'yes'
                            ),
                            array(
                                    'key' 	=> '_stock',
                                    'value' 	=> $nostockamount,
                                    'compare' 	=> '<=',
                                    'type' 	=> 'NUMERIC'
                            )
                    ),
                    'fields' => 'id=>parent'
            );

            $no_stock_products = (array) get_posts($args);

            $total_products_no_stock = (int) sizeof($no_stock_products);
            $total_products_out_of_stock += $total_products_no_stock;
            
            // Get low stock product variations
            $args = array(
                    'post_type'			=> 'product_variation',
                    'post_status' 		=> 'publish',
                    'posts_per_page'            => -1,
                    'suppress_filters'          => 0,
                    'meta_query' => array(
                            array(
                                    'key' 	=> '_stock',
                                    'value' 	=> $nostockamount,
                                    'compare' 	=> '<=',
                                    'type' 	=> 'NUMERIC'
                            ),
                            array(
                                    'key' 	=> '_stock',
                                    'value' 	=> array( '', false, null ),
                                    'compare' 	=> 'NOT IN'
                            )
                    ),
                    'fields' => 'id=>parent'
            );

            $no_stock_variations = (array) get_posts($args);

            $total_variations_out_of_stock = (int) sizeof($no_stock_variations);
            
            ?>
            <div id="poststuff" class="woocommerce-product-plus-wrap">
                <div class="woocommerce-product-plus-sidebar">
                    <div class="postbox">
                        <h3><span><?php _e( 'Total products / variations', 'woocommerce-product-plus' ); ?></span></h3>
                        <div class="inside">
                            <p class="stat"><?php if ($total_products>0) echo $total_products; else _e( 'n/a', 'woocommerce-product-plus' ); ?> / <?php if ($total_variations>0) echo $total_variations; else _e( 'n/a', 'woocommerce-product-plus' ); ?></p>
                        </div>
                    </div>
                    <div class="postbox">
                        <h3><span><?php _e( 'Total products / variations in low stock', 'woocommerce-product-plus' ); ?></span></h3>
                        <div class="inside">
                            <p class="stat"><?php if ($total_products_low_stock>0) echo $total_products_low_stock; else _e( 'n/a', 'woocommerce-product-plus' ); ?> / <?php if ($total_variations_low_stock>0) echo $total_variations_low_stock; else _e( 'n/a', 'woocommerce-product-plus' ); ?></p>
                        </div>
                    </div>
                    <div class="postbox">
                        <h3><span><?php _e( 'Total products / variations out of stock', 'woocommerce-product-plus' ); ?></span></h3>
                        <div class="inside">
                            <p class="stat"><?php if ($total_products_out_of_stock>0) echo $total_products_out_of_stock; else _e( 'n/a', 'woocommerce-product-plus' ); ?> / <?php if ($total_variations_out_of_stock>0) echo $total_variations_out_of_stock; else _e( 'n/a', 'woocommerce-product-plus' ); ?></p>
                        </div>
                    </div>
                </div>
		<div class="woocommerce-product-plus-main">
                    <div class="postbox">
                        <h3><span><?php _e( 'Modifieds products per day', 'woocommerce-product-plus' ); ?></span></h3>
                        <div class="inside chart">
                            <div id="placeholder" style="width:100%; overflow:hidden; height:568px; position:relative;"></div>
                            <div id="cart_legend"></div>
                        </div>
                    </div>
		</div>
            </div>
            <?php

            $start_date = strtotime('-30 days', current_time('timestamp'));
            $end_date = current_time( 'timestamp' );
            $modifications = array();

            // Blank date ranges to begin
            $count = 0;
            $days = ($end_date - $start_date) / (60 * 60 * 24);
            if ($days==0) $days = 1;

            while ($count <= $days) :
                    $time = strtotime(date('Ymd', strtotime('+ '.$count.' DAY', $start_date))).'000';

                    $modifications[ $time ] = 0;

                    $count++;
            endwhile;
            
            foreach ($products as $product) :
                if (strtotime($product->post_modified) > $start_date) :
                    $time = strtotime(date('Ymd', strtotime($product->post_modified))).'000';

                    if (isset($modifications[ $time ])) :
                        $modifications[ $time ]++;
                    else :
                        $modifications[ $time ] = 1;
                    endif;
                endif;
            endforeach;
            
            $modifications_array = array();
            foreach ($modifications as $key => $count) :
		$modifications_array[] = array( esc_js( $key ), esc_js( $count ) );
            endforeach;

            $chart_data = json_encode($modifications_array);            
            ?>

            <script type="text/javascript">
		jQuery(function(){
                    var d = jQuery.parseJSON( '<?php echo $chart_data; ?>' );

                    //for (var i = 0; i < d.length; ++i){ d[i][0] = +(d[i][0]) + (60 * 60 * 1000); }

                    var placeholder = jQuery("#placeholder");

                    var plot = jQuery.plot(placeholder, [ { data: d } ], {
                        legend: {
                            container: jQuery('#cart_legend'),
                            noColumns: 2
                        },
                        series: {
                            bars: {
                                barWidth: 60 * 60 * 24 * 1000,
                                align: "center",
                                show: true
                            },
                            points: { show: true }
                        },
                        grid: {
                            show: true,
                            aboveData: false,
                            color: '#aaa',
                            backgroundColor: '#fff',
                            borderWidth: 2,
                            borderColor: '#aaa',
                            clickable: false,
                            hoverable: true,
                            markings: markWeekendAreas
                        },
                        xaxis: {
                            mode: "time",
                            timeformat: "%d %b",
                            monthNames: <?php echo json_encode( array_values( $wp_locale->month_abbrev ) ) ?>,
                            tickLength: 1,
                            minTickSize: [1, "day"]
                        },
                        yaxes: [ { position: "right", min: 0, tickSize: 1, tickDecimals: 0 } ],
                        colors: ["#8a4b75"]
                    });

                    placeholder.resize();                   

                    <?php $this->weekend_area_js(); ?>
                    <?php woocommerce_tooltip_js(); ?>
                });
            </script>
        <?php
        }

        /**
         * Output the product list.
         *
         * @access public
         * @return void
         */
        public function woocommerce_products_list() {
            global $woocommerce, $post, $the_product;

            $args = array(
		'post_type'		=> 'product',
		'posts_per_page' 	=> -1,
                'orderby'               => 'title',
                'order'                 => 'ASC',
                'suppress_filters'      => 0
	            );

            $products = (array) get_posts($args);
            
            $columns = apply_filters('manage_edit-product_columns', null);
            
            unset( $columns['cb'], $columns['product_cat'], $columns['product_tag'] );
            $index = array_search('price', array_keys($columns));
            
            $newColumns = array();
            
            $newColumns['status'] = __( 'Status', 'woocommerce-product-plus' );
            $newColumns['visibility'] = __( 'Visibility', 'woocommerce-product-plus' );
            
            if (!isset($index) || $index < 0) {
                $columns = array_merge($columns, $newColumns);
            }
            else {
                $columns = array_merge(array_slice($columns, 0, ++$index), $newColumns, array_slice($columns, $index));
            }
            
            ?>
            <div id="poststuff" class="woocommerce-wide-reports-wrap">
                <table class="widefat wp-list-table">
                    <thead>
                        <tr>
                            <?php
                            foreach ($columns as $column_name => $column_display_name) {
                                echo '<th class="manage-column column-' . $column_name . '">' . $column_display_name . '</th>';
                            } 
                            ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                        if ( $products ) {
                            foreach ( $products as $product ) {
                                $post = $product;
                                $the_product = get_product( $post );
                                ( $the_product->product_type == 'variable' ) ? $attributes = ' style = "border-bottom: none;"' : $attributes = '';
                                echo '<tr>';
                                foreach ($columns as $column_name => $column_display_name) {
                                    echo '<td class="' . $column_name . ' column-' . $column_name . '"'.$attributes.'>';
                                        
                                        add_filter( 'post_row_actions', array( $this, 'limit_action') );
					do_action( 'manage_posts_custom_column', $column_name, $product->ID );
                                        do_action( 'manage_product_posts_custom_column', $column_name, $product->ID );
                                        remove_filter( 'post_row_actions', array( $this, 'limit_action') );
                                        switch ($column_name) {
                                            case "date" :
                                                if ( '0000-00-00 00:00:00' == $post->post_date ) {
                                                    $t_time = $h_time = __( 'Unpublished' );
                                                    $time_diff = 0;
                                                } else {
                                                    $t_time = get_the_time( __( 'Y/m/d g:i:s A' ) );
                                                    $m_time = $post->post_date;
                                                    $time = get_post_time( 'G', true, $post );

                                                    $time_diff = time() - $time;

                                                    if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS )
                                                        $h_time = sprintf( __( '%s ago' ), human_time_diff( $time ) );
                                                    else
                                                        $h_time = mysql2date( __( 'Y/m/d' ), $m_time );
                                                }

                                                echo '<abbr title="' . $t_time . '">' . apply_filters( 'post_date_column_time', $h_time, $post, $column_name, 'list' ) . '</abbr>';
                                                break;
                                            case "visibility" :
                                                if (get_post_status( $the_product->id ) != 'publish' || ! $the_product->is_visible()) {
                                                        echo ('<mark class="outofstock">' . __( 'Hidden' ) . '</mark>');
                                                    } else { 
                                                        echo ('<mark class="instock">' . __( 'Visible' ) . '</mark>');
                                                    };
                                                break;
                                            case "status" :
                                                switch ( $post->post_status ) {
                                                    case 'private':
                                                        _e('Privately Published');
                                                        break;
                                                    case 'publish':
                                                        _e('Published');
                                                        break;
                                                    case 'future':
                                                        _e('Scheduled');
                                                        break;
                                                    case 'pending':
                                                        _e('Pending Review');
                                                        break;
                                                    case 'draft':
                                                    case 'auto-draft':
                                                        _e('Draft');
                                                        break;
                                                }
                                                break;
                                        }
                                    echo '</td>';
                                }
                                echo '</tr>';
                                
                                if ($the_product->product_type == 'variable'){
                                    $variables_attributes = $the_product->get_variation_attributes();
                                    $variables = $the_product->get_children();
                                    $variables_count = (int)  sizeof($variables);
                                    $i = 0;
                                    foreach ( $variables as $variable ) {
                                        $attributes = ' style = "border-bottom: none;"';
                                        $the_product = get_product( $variable );
                                        $post = get_post( $variable );
                                        echo '<tr>';
                                        foreach ($columns as $column_name => $column_display_name) {
                                            echo '<td'.$attributes.'>';
                                            switch ($column_name) {
                                                case "name" :
                                                    $variation_data = $the_product->variation_data;
                                                    ksort($variation_data);
                                                    echo woocommerce_get_formatted_variation($variation_data, true);
                                                    break;
                                                case "sku" :
                                                    if ($the_product->get_sku()) echo $the_product->get_sku(); else echo '<span class="na">&ndash;</span>';
                                                    break;
                                                case "price":
                                                    if ($the_product->get_price_html()) echo $the_product->get_price_html(); else echo '<span class="na">&ndash;</span>';
                                                    break;
                                                case "is_in_stock" :
                                                    if ($the_product->is_in_stock()) {
                                                        echo '<mark class="instock">' . __( 'In stock', 'woocommerce' ) . '</mark>';
                                                    } else {
                                                        echo '<mark class="outofstock">' . __( 'Out of stock', 'woocommerce' ) . '</mark>';
                                                    }

                                                    if ( $the_product->managing_stock() ) :
                                                        echo ' &times; ' . $the_product->get_total_stock();
                                                    endif;
                                                    break;
                                                case "date" :
                                                    if ( '0000-00-00 00:00:00' == $post->post_date ) {
                                                        $t_time = $h_time = __( 'Unpublished' );
                                                        $time_diff = 0;
                                                    } else {
                                                        $t_time = get_the_time( __( 'Y/m/d g:i:s A' ) );
                                                        $m_time = $post->post_date;
                                                        $time = get_post_time( 'G', true, $post );

                                                        $time_diff = time() - $time;

                                                        if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS )
                                                            $h_time = sprintf( __( '%s ago' ), human_time_diff( $time ) );
                                                        else
                                                            $h_time = mysql2date( __( 'Y/m/d' ), $m_time );
                                                    }

                                                    echo '<abbr title="' . $t_time . '">' . apply_filters( 'post_date_column_time', $h_time, $post, $column_name, 'list' ) . '</abbr>';
                                                    break;
                                                case "visibility" :
                                                    if (get_post_status( $the_product->get_variation_id() ) != 'publish' || ! $the_product->is_visible()) {
                                                        echo ('<mark class="outofstock">' . __( 'Hidden' ) . '</mark>');
                                                    } else { 
                                                        echo ('<mark class="instock">' . __( 'Visible' ) . '</mark>');
                                                    };
                                                    break;
                                                case "status" :
                                                    switch ( $post->post_status ) {
                                                        case 'private':
                                                            _e('Privately Published');
                                                            break;
                                                        case 'publish':
                                                            _e('Published');
                                                            break;
                                                        case 'future':
                                                            _e('Scheduled');
                                                            break;
                                                        case 'pending':
                                                            _e('Pending Review');
                                                            break;
                                                        case 'draft':
                                                        case 'auto-draft':
                                                            _e('Draft');
                                                            break;
                                                    }
                                                    break;
                                            }
                                            echo '</td>';
                                        }
                                        echo '</tr>';
                                    }
                                    echo '<tr><td colspan="' . (int) sizeof($columns) . '">&nbsp;</td></tr>';
                                }
                            }
                        } else {
                            echo '<tr><th colspan="' . (int) sizeof($columns) . '"><strong>' . __( 'There are no products to show.', 'woocommerce-product-plus' ) . '</strong></th></tr>';
                        }
                    ?>
                    </tbody>
                </table>
                <script type="text/javascript">
                    jQuery('td[class="featured column-featured"] > a > img').unwrap();
                </script>
            </div>
        <?php
        }

        /**
         * Create the settings page content
         */
        public function create_settings_page() {
            ?>
            <h3><?php _e( 'Product Plus', 'woocommerce-product-plus' ); ?></h3>
            <table class="form-table">
                <tbody>
                </tbody>
            </table>
            <input type="hidden" name="<?php echo $this->hidden_submit; ?>" value="submitted">
            <?php
        }
        
        /**
         * Save all settings
         */
        public function save_settings_page() {
            if ( isset( $_POST[ $this->hidden_submit ] ) && $_POST[ $this->hidden_submit ] == 'submitted' ) {
                foreach ( $_POST as $key => $value ) {
                    /*if ( $key != $this->hidden_submit && strpos( $key, WooCommerce_Reports::$plugin_prefix ) !== false ) {
                        if ( empty( $value ) ) {
                            delete_option( $key );
                        } else {
                            if ( get_option( $key ) && get_option( $key ) != $value ) {
                                update_option( $key, $value );
                            }
                            else {
                                add_option( $key, $value );
                            }
                        }
                    }*/
                }
            }
        }

        /**
         * Limit actions in the post line.
         * @param type $actions
         * @param type $post
         * @return type $actions
         */
        public function limit_action($actions){
            unset( $actions['inline hide-if-no-js'], $actions['untrash'], $actions['trash'], $actions['delete'], $actions['duplicate']);
            return $actions;
        }
    }
}
?>
