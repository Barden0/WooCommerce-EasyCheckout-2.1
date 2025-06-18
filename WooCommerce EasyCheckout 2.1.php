<?php
/*
Plugin Name: WooCommerce EasyCheckout 2.0
Description: Hide billing fields at checkout if the cart only contains virtual or downloadable products.
Version: 2.1
Author: Samuel Barden
Author URI: https://samuelbarden.com
*/

// Check if the cart contains any physical product
function wce_cart_contains_physical_product() {
    if ( ! WC()->cart ) {
        return true; // default to showing fields if cart isn't available
    }

    foreach (WC()->cart->get_cart() as $cart_item) {
        $product = $cart_item['data'];
        if (!$product->is_virtual() && !$product->is_downloadable()) {
            return true;
        }
    }
    return false;
}

// Conditionally hide billing fields
function wce_hide_billing_fields_for_virtual_downloadable_products($fields) {
    if (!wce_cart_contains_physical_product()) {
        $hidden_fields = get_option('wce_hidden_fields', array());
        foreach ($hidden_fields as $field => $enabled) {
            if ($enabled && isset($fields['billing'][$field])) {
                unset($fields['billing'][$field]);
            }
        }
    }
    return $fields;
}
add_filter('woocommerce_checkout_fields', 'wce_hide_billing_fields_for_virtual_downloadable_products', 999);

// Create settings page
function wce_register_settings_page() {
    add_options_page('WooCommerce EasyCheckout Settings', 'Woo EasyCheckout', 'manage_options', 'wce-easycheckout', 'wce_settings_page');
}
add_action('admin_menu', 'wce_register_settings_page');

// Register settings
function wce_register_settings() {
    register_setting('wce-settings-group', 'wce_hidden_fields', 'wce_sanitize_hidden_fields');
}
add_action('admin_init', 'wce_register_settings');

// Sanitize input fields
function wce_sanitize_hidden_fields($input) {
    $valid = array();
    $available_fields = array(
        'billing_address_1', 'billing_address_2', 'billing_city',
        'billing_postcode', 'billing_country', 'billing_state', 'billing_phone'
    );
    foreach ($available_fields as $field) {
        $valid[$field] = isset($input[$field]) ? 1 : 0;
    }
    return $valid;
}

// Settings page HTML
function wce_settings_page() {
    $hidden_fields = get_option('wce_hidden_fields', array());
    ?>
    <div class="wrap">
        <h1>WooCommerce EasyCheckout Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('wce-settings-group'); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Billing Fields to Hide (for digital-only carts)</th>
                    <td>
                        <?php
                        $fields = array(
                            'billing_address_1' => 'Address 1',
                            'billing_address_2' => 'Address 2',
                            'billing_city'      => 'City',
                            'billing_postcode'  => 'Postcode',
                            'billing_country'   => 'Country',
                            'billing_state'     => 'State',
                            'billing_phone'     => 'Phone',
                        );
                        foreach ($fields as $key => $label) {
                            $checked = isset($hidden_fields[$key]) ? $hidden_fields[$key] : 0;
                            echo '<label><input type="checkbox" name="wce_hidden_fields[' . esc_attr($key) . ']" value="1" ' . checked(1, $checked, false) . '> ' . esc_html($label) . '</label><br />';
                        }
                        ?>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Add "Settings" link in plugin list
function wce_plugin_settings_link($links) {
    $settings_link = '<a href="options-general.php?page=wce-easycheckout">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wce_plugin_settings_link');

