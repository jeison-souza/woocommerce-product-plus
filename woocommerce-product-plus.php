<?php
/**
 * Product extender for WooCommerce shop plugin.
 *
 * Plugin Name: WooCommerce Product Plus
 * Plugin URI: https://github.com/jeison-souza/woocommerce-product-plus
 * Description: Product extender for WooCommerce shop plugin.
 * Version: 1.0.1
 * Author: Jeison Souza
 * Author URI: https://github.com/jeison-souza
 * License: GPLv3 or later
 * License URI: http://www.opensource.org/licenses/gpl-license.php
 * Text Domain: woocommerce-product-plus
 * Domain Path: /languages/
 *
 * Copyright 2013 Jeison Souza
 *		
 *     This file is part of WooCommerce Product Plus, a plugin for WordPress.
 *
 *     WooCommerce Product Plus is free software:
 *     You can redistribute it and/or modify it under the terms of the
 *     GNU General Public License as published by the Free Software
 *     Foundation, either version 2 of the License, or (at your option)
 *     any later version.
 *
 *     WooCommerce Product Plus is distributed in the hope that
 *     it will be useful, but WITHOUT ANY WARRANTY; without even the
 *     implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR
 *     PURPOSE. See the GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with WordPress. If not, see <http://www.gnu.org/licenses/>.
 */

 if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
 
if(defined('WCPP_VERSION')) return;
define('WCPP_VERSION', '1.0.1');

require dirname(__FILE__) . '/classes/dependencies.class.php';
require dirname(__FILE__) . '/classes/requests.class.php';
require dirname(__FILE__) . '/classes/woocommerce_product_plus.class.php';
$woocommerce_product_plus = new WooCommerce_Product_Plus();

?>