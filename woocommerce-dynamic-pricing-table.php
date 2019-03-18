<?php
/**
 * Plugin Name:       WooCommerce Dynamic Pricing Table
 * Plugin URI:        https://github.com/lucasstark/woocommerce-dynamic-pricing-table
 * Description:       Displays a pricing discount table on WooCommerce products, a user role discount message and a simple category discount message when using the WooCommerce Dynamic Pricing plugin.
 * Version:           1.1.4
 * Author:            Lucas Stark
 * Author URI:        https://elementstark.com
 * Requires at least: 4.6
 * Tested up to:      5.0.2
 *
 * WC requires at least: 3.0.0
 * WC tested up to: 3.5.3
 *
 * Text Domain: woocommerce-dynamic-pricing-table
 * Domain Path: /languages/
 *
 * @package WC_Dynamic_Pricing_Table
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Returns the main instance of WC_Dynamic_Pricing_Table to prevent the need to use globals.
 *
 * @since   1.0.0
 * @return  object WC_Dynamic_Pricing_Table
 */
function WC_Dynamic_Pricing_Table() {
	return WC_Dynamic_Pricing_Table::instance();
} // End WC_Dynamic_Pricing_Table()
WC_Dynamic_Pricing_Table();

/**
 * Main WC_Dynamic_Pricing_Table Class
 *
 * @class     WC_Dynamic_Pricing_Table
 * @version   1.0.0
 * @since     1.0.0
 * @package   WC_Dynamic_Pricing_Table
 */
final class WC_Dynamic_Pricing_Table {

	/**
	 * WC_Dynamic_Pricing_Table The single instance of WC_Dynamic_Pricing_Table.
	 *
	 * @var     object
	 * @access  private
	 * @since   1.0.0
	 */
	private static $_instance = null;

	/**
	 * The token.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $token;

	/**
	 * The version number.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $version;


	/**
	 * The js and css version number
	 *
	 * @var string
	 * @access public
	 * @since  1.0.8
	 */
	public $assets_version;


	/**
	 * Constructor function.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function __construct() {
		$this->token          = 'woocommerce-dynamic-pricing-table';
		$this->plugin_url     = plugin_dir_url( __FILE__ );
		$this->plugin_path    = plugin_dir_path( __FILE__ );
		$this->version        = '1.0.0';
		$this->assets_version = '1.0.8';

		register_activation_hook( __FILE__, array( $this, 'install' ) );

		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		add_action( 'init', array( $this, 'plugin_setup' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'on_wp_enqueue_scripts' ) );
	}

	/**
	 * Main WC_Dynamic_Pricing_Table Instance
	 *
	 * Ensures only one instance of WC_Dynamic_Pricing_Table is loaded or can be loaded.
	 *
	 * @since   1.0.0
	 * @static
	 * @see     WC_Dynamic_Pricing_Table()
	 * @return  WC_Dynamic_Pricing_Table instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	} // End instance()

	/**
	 * Load the localisation file.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'woocommerce-dynamic-pricing-table', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Installation.
	 * Runs on activation. Logs the version number.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  void
	 */
	public function install() {
		$this->log_plugin_version_number();
	}

	/**
	 * Log the plugin version number.
	 *
	 * @access  private
	 * @since   1.0.0
	 * @return  void
	 */
	private function log_plugin_version_number() {
		// Log the version number.
		update_option( $this->token . '-version', $this->version );
	}

	/**
	 * Setup all the things.
	 * Only executes if WooCommerce Dynamic Pricing is active.
	 * If WooCommerce Dynamic Pricing is inactive an admin notice is displayed.
	 *
	 * @return void
	 */
	public function plugin_setup() {
		if ( class_exists( 'WC_Dynamic_Pricing' ) ) {
			add_action( 'woocommerce_get_price_html', array( $this, 'output_lowest_simple_price' ), 99, 2 );
			add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'output_dynamic_pricing_table' ) );

			if ( ! is_admin() ) {
				add_action( 'wp', array( $this, 'output_dynamic_pricing_role_message' ) );
				add_action( 'wp', array( $this, 'output_dynamic_pricing_category_message' ) );
			}
		} else {
			add_action( 'admin_notices', array( $this, 'install_wc_dynamic_pricing_notice' ) );
		}
	}

	/**
	 * WooCommerce Dynamic Pricing plugin install notice.
	 * If the user activates this plugin while not having the WooCommerce Dynamic Pricing plugin installed or activated, prompt them to install WooCommerce Dynamic Pricing.
	 *
	 * @since   1.0.0
	 * @return  void
	 */
	public function install_wc_dynamic_pricing_notice() {
		echo '<div class="notice is-dismissible updated">
      <p>' . __( 'The WooCommerce Dynamic Pricing Table extension requires that you have the WooCommerce Dynamic Pricing plugin installed and activated.', 'woocommerce-dynamic-pricing-table' ) . ' <a href="https://www.woocommerce.com/products/dynamic-pricing/">' . __( 'Get WooCommerce Dynamic Pricing now', 'woocommerce-dynamic-pricing-table' ) . '</a></p>
    </div>';
	}


	public function on_wp_enqueue_scripts() {
		wp_enqueue_script( 'woocommerce-dynamic-pricing-table', $this->plugin_url . '/assets/js/front-end.js', array( 'jquery' ), $this->assets_version );
	}


	/**
	 * Gets the dynamic pricing rules sets from the post meta.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  array
	 */
	public function get_pricing_array_rule_sets( $product_id = false ) {
		if ( $product_id === false ) {
			$product_id = get_the_ID();
		}
		$results    = array();
		$product    = wc_get_product( $product_id );
		$price_sets = get_post_meta( $product_id, '_pricing_rules', true );


		if ( empty( $price_sets ) ) {



			$price_sets = get_option( '_a_category_pricing_rules', array() );
			if ( ! empty( $price_sets ) ) {

				foreach ( $price_sets as $key => $price_set ) {
					$terms = WC_Dynamic_Pricing_Compatibility::get_product_category_ids( $product );
					if ( count( array_intersect( $price_set['collector']['args']['cats'], $terms ) ) > 0 ) {
						if ( count( array_intersect( $price_set['targets'], $terms ) ) > 0 ) {
							$results[ $key ] = $price_set;
						}
					}

				}
			}
		} else {
			$results = $price_sets;

			$price_sets = get_option( '_a_category_pricing_rules', array() );
			if ( ! empty( $price_sets ) ) {

				foreach ( $price_sets as $key => $price_set ) {
					$terms = WC_Dynamic_Pricing_Compatibility::get_product_category_ids( $product );
					if ( count( array_intersect( $price_set['collector']['args']['cats'], $terms ) ) > 0 ) {
						if ( count( array_intersect( $price_set['targets'], $terms ) ) > 0 ) {
							$results[ $key ] = $price_set;
						}
					}

				}
			}

		}

		$valid_results = array();
		foreach ( $results as $key => $result ) {

			$execute_rules = true;

			$from_date = empty( $result['date_from'] ) ? false : strtotime( date_i18n( 'Y-m-d 00:00:00', strtotime( $result['date_from'] ), false ) );
			$to_date   = empty( $result['date_to'] ) ? false : strtotime( date_i18n( 'Y-m-d 00:00:00', strtotime( $result['date_to'] ), false ) );
			$now       = current_time( 'timestamp' );

			if ( $from_date && $to_date && ! ( $now >= $from_date && $now <= $to_date ) ) {
				$execute_rules = false;
			} elseif ( $from_date && ! $to_date && ! ( $now >= $from_date ) ) {
				$execute_rules = false;
			} elseif ( $to_date && ! $from_date && ! ( $now <= $to_date ) ) {
				$execute_rules = false;
			}

			if ( $execute_rules ) {
				$valid_results[ $key ] = $result;
			}
		}

		return $valid_results;
	}

	public function filter_rulesets( $rulesets ) {
		$valid = array();

		foreach ( $rulesets as $key => $ruleset ) {

			$pricing_conditions = $ruleset['conditions'];
			$execute_rules      = true;

			if ( is_array( $pricing_conditions ) && sizeof( $pricing_conditions ) > 0 ) {
				$result         = 0;
				$conditions_met = 0;


				foreach ( $pricing_conditions as $condition ) {
					switch ( $condition['type'] ) {
						case 'apply_to':
							if ( is_array( $condition['args'] ) && isset( $condition['args']['applies_to'] ) ) {
								if ( $condition['args']['applies_to'] == 'everyone' ) {
									$result = 1;
								} elseif ( $condition['args']['applies_to'] == 'unauthenticated' ) {
									if ( ! is_user_logged_in() ) {
										$result = 1;
									}
								} elseif ( $condition['args']['applies_to'] == 'authenticated' ) {
									if ( is_user_logged_in() ) {
										$result = 1;
									}
								} elseif ( $condition['args']['applies_to'] == 'roles' && isset( $condition['args']['roles'] ) && is_array( $condition['args']['roles'] ) ) {
									if ( is_user_logged_in() ) {
										foreach ( $condition['args']['roles'] as $role ) {
											if ( current_user_can( $role ) ) {
												$result = 1;
												break;
											}
										}
									}
								} elseif ( $condition['args']['applies_to'] == 'groups' && isset( $condition['args']['groups'] ) && is_array( $condition['args']['groups'] ) ) {
									if ( is_user_logged_in() && class_exists( 'Groups_User' ) ) {
										$groups_user = new Groups_User( get_current_user_id() );
										foreach ( $condition['args']['groups'] as $group ) {
											$current_group = Groups_Group::read( $group );
											if ( $current_group ) {
												if ( Groups_User_Group::read( $groups_user->user->ID, $current_group->group_id ) ) {
													$result = 1;
													break;
												}
											}
										}
									}
								}
							}
							break;
						default:
							$result = 0;
							break;
					}

					$result         = apply_filters( 'woocommerce_dynamic_pricing_table_is_rule_set_valid_for_user', $result, $condition, $ruleset );
					$conditions_met += $result;
				}


				if ( $ruleset['conditions_type'] == 'all' ) {
					$execute_rules = $conditions_met == count( $pricing_conditions );
				} elseif ( $ruleset['conditions_type'] == 'any' ) {
					$execute_rules = $conditions_met > 0;
				}
			} else {
				//empty conditions - default match, process price adjustment rules
				$execute_rules = true;
			}

			if ( $execute_rules ) {
				$valid[ $key ] = $ruleset;
			}
		}

		return $valid;
	}

	/**
	 * Gets the current user.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  WP_User
	 */
	public function get_current_user() {
		return wp_get_current_user();
	}

	/**
	 * Gets the current category.
	 *
	 * @access  public
	 * @since   1.0.0
	 * @return  object
	 */
	public function pricing_queried_object() {
		return get_queried_object();
	}

	/**
	 * Outputs the dynamic bulk pricing table.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function bulk_pricing_table_output( $pricing_rule_set ) {

		$table_class = '';
		$style       = '';
		if ( isset( $pricing_rule_set['variation_rules'] ) && ! empty( $pricing_rule_set['variation_rules'] ) ) {
			if ( isset( $pricing_rule_set['variation_rules']['args']['variations'] ) && ! empty( $pricing_rule_set['variation_rules']['args']['variations'] ) ) {
				$style       = 'style="display:none;"';
				$table_class .= ' dynamic-pricing-table-variation ';
				foreach ( $pricing_rule_set['variation_rules']['args']['variations'] as $variation_id ) {
					$table_class .= ' dynamic-pricing-table-variation-' . $variation_id;
				}
			}
		}

		do_action( 'woocommerce_dynamic_pricing_table_before_table', $pricing_rule_set );

		$output = '<table ' . $style . ' class="dynamic-pricing-table ' . $table_class . '">';

		$output .= '<th>' . apply_filters( 'wc_dynamic_pricing_table_header_quantity_text', __( 'Quantity', 'woocommerce-dynamic-pricing-table' ) ) . '</th><th>' . apply_filters( 'wc_dynamic_pricing_table_header_text', __( 'Bulk Purchase Pricing', 'woocommerce-dynamic-pricing-table' ) ) . '</th>';

		foreach ( $pricing_rule_set['rules'] as $key => $value ) {

			// Checks if a product discount group max quantity field is less than 1.
			if ( $pricing_rule_set['rules'][ $key ]['to'] < 1 ) {
				$rules_to = __( ' or more', 'woocommerce-dynamic-pricing-table' );
			} else {
				if ( wc_stock_amount( $pricing_rule_set['rules'][ $key ]['to'] ) > wc_stock_amount( $pricing_rule_set['rules'][ $key ]['from'] ) ) {
					$rules_to = ' - ' . wc_stock_amount( $pricing_rule_set['rules'][ $key ]['to'] );
				} else {
					$rules_to = '';
				}
			}

			$output .= '<tr>';

			$output .= '<td><span class="discount-quantity">' . wc_stock_amount( $pricing_rule_set['rules'][ $key ]['from'] ) . $rules_to . '</span></td>';

			switch ( $pricing_rule_set['rules'][ $key ]['type'] ) {

				case 'price_discount':
					$output .= '<td><span class="discount-amount">' . sprintf( __( '%1$s Discount Per Item', 'woocommerce-dynamic-pricing-table' ), wc_price( $pricing_rule_set['rules'][ $key ]['amount'] ) ) . '</span></td>';
					break;

				case 'percentage_discount':
					$output .= '<td><span class="discount-amount">' . esc_html( $pricing_rule_set['rules'][ $key ]['amount'] ) . __( '% Discount', 'woocommerce-dynamic-pricing-table' ) . '</span></td>';
					break;

				case 'fixed_price':
					$amount = apply_filters('wc_dynamic_pricing_table_get_fixed_price', $pricing_rule_set['rules'][ $key ]['amount']);
					$display_price = wc_get_price_to_display( wc_get_product(), array(
						'qty'   => 1,
						'price' => $amount
					) );

					$output        .= '<td><span class="discount-amount">' . sprintf( __( '%1$s Per Item', 'woocommerce-dynamic-pricing-table' ), wc_price( $display_price ) ) . '</span></td>';
					break;

			}

			$output .= '</tr>';


		}

		$output .= '</table>';

		echo $output;

	}


	/**
	 * Outputs the dynamic special offer pricing table.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function special_offer_pricing_table_output( $pricing_rule_set ) {

		$output = '<table class="dynamic-pricing-table">';

		$output .= '<th>' . __( 'Quantity', 'woocommerce-dynamic-pricing-table' ) . '</th><th>' . __( 'Special Offer Pricing', 'woocommerce-dynamic-pricing-table' ) . '</th>';


		foreach ( $pricing_rule_set['blockrules'] as $key => $value ) {

			$output .= '<tr>';

			$to = $pricing_rule_set['blockrules'][ $key ]['adjust'];
			if ( $to == '1000000' ) {
				$to = 'unlimited';
			} else {
				wc_stock_amount( $pricing_rule_set['blockrules'][ $key ]['adjust'] );
			}

			switch ( $pricing_rule_set['blockrules'][ $key ]['type'] ) {
				case 'fixed_adjustment':
				case 'percent_adjustment':
					$output .= '<td><span class="discount-quantity">' . sprintf( __( 'Buy %1$s get %2$s more discounted', 'woocommerce-dynamic-pricing-table' ), wc_stock_amount( $pricing_rule_set['blockrules'][ $key ]['from'] ), $to ) . '</span></td>';
					break;
				case 'fixed_price':
					$output .= '<td><span class="discount-quantity">' . sprintf( __( 'Buy %1$s get %2$s more at', 'woocommerce-dynamic-pricing-table' ), wc_stock_amount( $pricing_rule_set['blockrules'][ $key ]['from'] ), $to ) . '</span></td>';

			}

			switch ( $pricing_rule_set['blockrules'][ $key ]['type'] ) {

				case 'fixed_adjustment':
					$output .= '<td><span class="discount-amount">' . sprintf( __( '%1$s Discount Per Item', 'woocommerce-dynamic-pricing-table' ), wc_price( $pricing_rule_set['blockrules'][ $key ]['amount'] ) ) . '</span></td>';
					break;

				case 'percent_adjustment':
					$output .= '<td><span class="discount-amount">' . floatval( $pricing_rule_set['blockrules'][ $key ]['amount'] ) . __( '% Discount', 'woocommerce-dynamic-pricing-table' ) . '</span></td>';
					break;

				case 'fixed_price':
					$output .= '<td><span class="discount-amount">' . sprintf( __( '%1$s Per Item', 'woocommerce-dynamic-pricing-table' ), wc_price( $pricing_rule_set['blockrules'][ $key ]['amount'] ) ) . '</span></td>';
					break;

			}

			$output .= '</tr>';

		}


		$output .= '</table>';

		echo $output;

	}

	/**
	 * Outputs the dynamic pricing table.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function output_dynamic_pricing_table() {

		$array_rule_sets = $this->get_pricing_array_rule_sets();


		if ( $array_rule_sets && is_array( $array_rule_sets ) ) {

			if ( apply_filters( 'woocommerce_dynamic_pricing_table_filter_rules', true ) ) {
				$valid_rules = apply_filters( 'woocommerce_dynamic_pricing_table_get_filtered_rules', $this->filter_rulesets( $array_rule_sets ), $array_rule_sets );
			} else {
				$valid_rules = $array_rule_sets;
			}

			foreach ( $valid_rules as $pricing_rule_set ) {
				if ( $pricing_rule_set['mode'] == 'continuous' ) :
					$this->bulk_pricing_table_output( $pricing_rule_set );
				elseif ( $pricing_rule_set['mode'] == 'block' ) :
					$this->special_offer_pricing_table_output( $pricing_rule_set );
				endif;
			}
		}
	}

	public function output_lowest_simple_price( $price_html, $product ) {

		if ( $product->get_type() == 'variation' ) {
			return $price_html;
		}

		if ( apply_filters( 'woocommerce_dynamic_pricing_show_lowest_price', false, $product ) ) {
			$array_rule_sets = $this->get_pricing_array_rule_sets();

			$lowest_price = false;
			if ( $array_rule_sets && is_array( $array_rule_sets ) ) {

				if ( apply_filters( 'woocommerce_dynamic_pricing_table_filter_rules', true ) ) {
					$valid_rules = apply_filters( 'woocommerce_dynamic_pricing_table_get_filtered_rules', $this->filter_rulesets( $array_rule_sets ), $array_rule_sets );
				} else {
					$valid_rules = $array_rule_sets;
				}

				foreach ( $valid_rules as $pricing_rule_set ) {
					if ( $pricing_rule_set['mode'] == 'continuous' ) :
						$working_price = $this->get_adjusted_price( $pricing_rule_set, $product->get_price( 'edit' ) );
						if ( $working_price < $lowest_price || $lowest_price === false ) {
							$lowest_price = $working_price;
						}

					endif;
				}
			}

			if ( $product->get_type() == 'variation' ) {
				return apply_filters( 'woocommerce_dynamic_pricing_get_lowest_price_html', wc_price( $lowest_price ), $lowest_price, $product, $array_rule_sets );

			} else {
				return apply_filters( 'woocommerce_dynamic_pricing_get_lowest_price_html', 'From ' . wc_price( $lowest_price ), $lowest_price, $product, $array_rule_sets );

			}

		} else {
			return $price_html;
		}

	}

	protected function get_adjusted_price( $set, $price ) {
		$result = false;

		$pricing_rules = $set['rules'];

		if ( is_array( $pricing_rules ) && sizeof( $pricing_rules ) > 0 ) {
			foreach ( $pricing_rules as $rule ) {

				if ( $rule['from'] == '*' ) {
					$rule['from'] = 0;
				}

				if ( empty( $rule['to'] ) || $rule['to'] == '*' ) {
					$rule['to'] = PHP_INT_MIN;
				}

				$amount       = $rule['amount'];
				$num_decimals = apply_filters( 'woocommerce_dynamic_pricing_get_decimals', (int) get_option( 'woocommerce_price_num_decimals' ) );
				switch ( $rule['type'] ) {
					case 'price_discount':
						$adjusted = floatval( $price ) - floatval( $amount );
						$result   = $adjusted >= 0 ? $adjusted : 0;
						break;
					case 'percentage_discount':
						$amount = $amount / 100;
						$result = round( floatval( $price ) - ( floatval( $amount ) * $price ), (int) $num_decimals );
						break;
					case 'fixed_price':
						$result = round( $amount, (int) $num_decimals );
						break;
					default:
						$result = false;
						break;
				}
			}
		}

		return $result;
	}

	/**
	 * The role discount notification message.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function role_discount_notification_message() {

		if ( ! is_user_logged_in() ) {
			return;
		}

		$role_pricing_rule_sets    = get_option( '_s_membership_pricing_rules', array() );
		$current_user_role         = $this->get_current_user()->roles[0];
		$current_user_display_name = $this->get_current_user()->display_name;

		foreach ( $role_pricing_rule_sets as $role_rules ) {

			// Gets the discount role of the user and the discount amount.

			if ( isset( $role_rules['conditions'][0]['args']['roles'] ) ) {
				$user_discount_role   = $role_rules['conditions'][0]['args']['roles'][0];
				$role_discount_amount = $role_rules['rules'][0]['amount'];

				if ( is_woocommerce() && $current_user_role === $user_discount_role && null !== $user_discount_role ) {

					switch ( $role_rules['rules'][0]['type'] ) {

						case 'percent_product':

							ob_start();
							wc_get_template( 'pricing-rule-output/role-percent-product.php', array(
								'current_user_display_name' => $current_user_display_name,
								'current_user_role'         => $current_user_role,
								'role_discount_amount'      => $role_discount_amount,
								'rule'                      => $role_rules
							), 'woocommerce-dynamic-pricing', $this->plugin_path() . 'templates/' );

							$info_message = ob_get_clean();

							break;

						case 'fixed_product':

							ob_start();
							wc_get_template( 'pricing-rule-output/role-fixed-product.php', array(
								'current_user_display_name' => $current_user_display_name,
								'current_user_role'         => $current_user_role,
								'role_discount_amount'      => $role_discount_amount,
								'rule'                      => $role_rules
							), 'woocommerce-dynamic-pricing', $this->plugin_path() . 'templates/' );

							$info_message = ob_get_clean();

							break;

					}

				}
			}

		}

		if ( isset( $info_message ) ) {
			wc_add_notice( $info_message, 'notice' );
		}

	}

	/**
	 * Outputs the role notificaton message.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function output_dynamic_pricing_role_message() {
		$this->role_discount_notification_message();
	}

	/**
	 * The category discount notification message.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function category_discount_notification_message() {

		$category_pricing_rule_sets = get_option( '_s_category_pricing_rules', array() );

		$queried_object = $this->pricing_queried_object();

		if ( empty( $queried_object ) || ! isset( $queried_object->term_id ) ) {
			return;
		}

		$current_product_category = $queried_object->term_id;
		$current_category_name    = $queried_object->name;

		foreach ( $category_pricing_rule_sets as $category_rules ) {

			if ( isset( $category_rules['collector']['args']['cats'][0] ) ) {
				// Gets the discount category and the discount amount set for the category.
				$discount_category        = $category_rules['collector']['args']['cats'][0];
				$category_discount_amount = $category_rules['rules'][0]['amount'];


				if ( is_product_category() && $current_product_category == $discount_category && null != $discount_category ) {

					switch ( $category_rules['rules'][0]['type'] ) {

						case 'percent_product':
							$info_message = sprintf( __( 'You will receive a %1$s percent discount on all products within the %2$s category.', 'woocommerce-dynamic-pricing-table' ), floatval( $category_discount_amount ), esc_attr( $current_category_name ) );
							break;

						case 'fixed_product':
							$info_message = sprintf( __( 'You will receive %1$s discount on all products within the %2$s category.', 'woocommerce-dynamic-pricing-table' ), wc_price( $category_discount_amount ), esc_attr( $current_category_name ) );
							break;

					}

				}
			}
		}

		if ( isset( $info_message ) ) {
			wc_add_notice( $info_message, 'notice' );
		}

	}

	/**
	 * Outputs the category notification message.
	 *
	 * @access  public
	 * @since   1.0.0
	 */
	public function output_dynamic_pricing_category_message() {
		$this->category_discount_notification_message();
	}



	/** Helper Functions */
	/**
	 * Get the plugin path
	 */
	public function plugin_path() {
		if ( $this->plugin_path ) {
			return $this->plugin_path;
		}

		return $this->plugin_path = untrailingslashit( plugin_dir_path( dirname( __FILE__ ) ) );
	}


} // End Class
