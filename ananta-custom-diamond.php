<?php
/*
 * Plugin Name: ANANTA Custom Diamond
 * Description: Enable customer to select a diamond for a ring setting.
 * Version: 1.0.0
 * Author: ANANTA Jewelry
 * Author URI: https://anantajewelry.com
 * License: Copyright 2025 by ANANTA Jewelry Co., Ltd.
 * Text Domain: ananta-custom-diamond
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'ANANTA_CD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ANANTA_CD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ANANTA_CD_PLUGIN_FILE', __FILE__ );

/**
 * Activation hook: Create database table if it doesn't exist.
 */
register_activation_hook( ANANTA_CD_PLUGIN_FILE, 'ananta_cd_activate' );
function ananta_cd_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ananta_diamonds';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id INT(11) NOT NULL AUTO_INCREMENT,
        diamond_id VARCHAR(255) NOT NULL,
        sup_name VARCHAR(255) NOT NULL,
        shape VARCHAR(255) NOT NULL,
        size FLOAT NOT NULL,
        color VARCHAR(255) NOT NULL,
        clarity VARCHAR(255) NOT NULL,
        cut VARCHAR(255) NOT NULL,
        symmetry VARCHAR(255) NOT NULL,
        polish VARCHAR(255) NOT NULL,
        lab VARCHAR(255) NOT NULL,
        cert_number VARCHAR(255) NOT NULL,
        cert_url VARCHAR(255) NOT NULL,
        location VARCHAR(255) NOT NULL,
        price_usd FLOAT NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY diamond_id (diamond_id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

/**
 * Add admin menu page.
 */
add_action( 'admin_menu', 'ananta_cd_admin_menu' );
function ananta_cd_admin_menu() {
    add_menu_page(
        __( 'Ananta Diamonds', 'ananta-custom-diamond' ),
        __( 'Ananta Diamonds', 'ananta-custom-diamond' ),
        'manage_options',
        'ananta-diamonds',
        'ananta_cd_admin_page_html',
        'dashicons-smiley',
        20
    );
}

/**
 * Admin page HTML.
 */
function ananta_cd_admin_page_html() {
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <p><?php esc_html_e( 'Manage diamond products from Ananta Jewelry.', 'ananta-custom-diamond' ); ?></p>

        <?php
        // Handle sync button click
        if ( isset( $_POST['ananta_cd_sync_nonce'] ) && wp_verify_nonce( sanitize_key($_POST['ananta_cd_sync_nonce']), 'ananta_cd_sync_action' ) ) {
            if ( isset( $_POST['ananta_cd_sync_button'] ) ) {
                ananta_cd_sync_data();
            }
        }
        ?>

        <form method="post" action="">
            <?php wp_nonce_field( 'ananta_cd_sync_action', 'ananta_cd_sync_nonce' ); ?>
            <p>
                <button type="submit" name="ananta_cd_sync_button" class="button button-primary">
                    <?php esc_html_e( 'Sync Diamond Data', 'ananta-custom-diamond' ); ?>
                </button>
            </p>
        </form>

        <h2><?php esc_html_e( 'Stored Diamonds', 'ananta-custom-diamond' ); ?></h2>
        <?php ananta_cd_display_admin_table(); ?>
    </div>
    <?php
}

/**
 * Fetch and store diamond data.
 */
function ananta_cd_sync_data() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ananta_diamonds';
    $json_url = 'https://anantajewelry.com/pub/tech-test/mock-diamonds.json';

    $response = wp_remote_get( $json_url );

    if ( is_wp_error( $response ) ) {
        $error_message = $response->get_error_message();
        echo '<div class="notice notice-error is-dismissible"><p>' . sprintf( esc_html__( 'Error fetching data: %s', 'ananta-custom-diamond' ), esc_html( $error_message ) ) . '</p></div>';
        return;
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $data ) ) {
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Error decoding JSON data or invalid data format.', 'ananta-custom-diamond' ) . '</p></div>';
        return;
    }

    $inserted_count = 0;
    $updated_count = 0;
    $error_count = 0;

    foreach ( $data as $item ) {
        $diamond_id = isset($item['diamond_id']) ? $item['diamond_id'] : null;
		$sup_name = isset($item['supplier_name']) ? $item['supplier_name'] : null;
        $shape = isset($item['shape']) ? $item['shape'] : null;
        $size = isset($item['size']) ? $item['size'] : null;
        $color = isset($item['color']) ? $item['color'] : null;
        $clarity = isset($item['clarity']) ? $item['clarity'] : null;
        $cut = isset($item['cut']) ? $item['cut'] : null;
        $symmetry = isset($item['symmetry']) ? $item['symmetry'] : null;
        $polish = isset($item['polish']) ? $item['polish'] : null;
        $lab = isset($item['lab']) ? $item['lab'] : null;
        $cert_number = isset($item['certification_number']) ? $item['certification_number'] : null;
        $cert_url = isset($item['certificate_url']) ? $item['certificate_url'] : null;
        $location = isset($item['location']) ? $item['location'] : null;
        $price_usd = isset($item['price_usd']) ? $item['price_usd'] : null;

        if ( ! $diamond_id ) {
            $error_count++;
            continue;
        }

        $existing_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_name WHERE diamond_id = %s", $diamond_id ) );

        $data_to_insert_update = array(
            'diamond_id' => $diamond_id,
            'sup_name' => $sup_name,
            'shape' => $shape,
            'size' => $size,
            'color' => $color,
            'clarity' => $clarity,
            'cut' => $cut,
            'symmetry' => $symmetry,
            'polish' => $polish,
            'lab' => $lab,
            'cert_number' => $cert_number,
            'cert_url' => $cert_url,
            'location' => $location,
            'price_usd' => $price_usd,
        );
        $data_types = array(
            '%s',  // diamond_id
            '%s',  // sup_name
            '%s',  // shape
            '%f',  // size
            '%s',  // color
            '%s',  // clarity
            '%s',  // cut
            '%s',  // symmetry
            '%s',  // polish
            '%s',  // lab
            '%s',  // cert_number
            '%s',  // cert_url
            '%s',  // location
            '%f',  // price_usd
        );


        if ( $existing_id ) {
            // Update existing record
            $result = $wpdb->update( $table_name, $data_to_insert_update, array( 'id' => $existing_id ), $data_types, array( '%d' ) );
            if ($result !== false) {
                $updated_count++;
            } else {
                $error_count++;
            }
        } else {
            // Insert new record
            $result = $wpdb->insert( $table_name, $data_to_insert_update, $data_types );
            if ($result !== false) {
                $inserted_count++;
            } else {
                $error_count++;
            }
        }
    }

    $message = sprintf(
        esc_html__( 'Sync complete. %d items inserted, %d items updated, %d errors.', 'ananta-custom-diamond' ),
        $inserted_count,
        $updated_count,
        $error_count
    );
    echo '<div class="notice notice-success is-dismissible"><p>' . $message . '</p></div>';
}

/**
 * Display stored diamonds in admin table.
 */
function ananta_cd_display_admin_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ananta_diamonds';
    $items = $wpdb->get_results( "SELECT * FROM $table_name" ); // Limit for performance

    if ( empty( $items ) ) {
        echo '<p>' . esc_html__( 'No diamonds found. Please sync the data.', 'ananta-custom-diamond' ) . '</p>';
        return;
    }
    ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 50px;"><?php esc_html_e( 'ID', 'ananta-custom-diamond' ); ?></th>
                <th><?php esc_html_e( 'Diamond ID', 'ananta-custom-diamond' ); ?></th>
                <th><?php esc_html_e( 'Supplier Name', 'ananta-custom-diamond' ); ?></th> 
                <th><?php esc_html_e( 'Shape', 'ananta-custom-diamond' ); ?></th>
                <th><?php esc_html_e( 'Size', 'ananta-custom-diamond' ); ?></th>
                <th><?php esc_html_e( 'Color', 'ananta-custom-diamond' ); ?></th>
                <th><?php esc_html_e( 'Clarity', 'ananta-custom-diamond' ); ?></th>
                <th><?php esc_html_e( 'Cut', 'ananta-custom-diamond' ); ?></th>
                <th><?php esc_html_e( 'Symmetry', 'ananta-custom-diamond' ); ?></th>
                <th><?php esc_html_e( 'Polish', 'ananta-custom-diamond' ); ?></th>
                <th><?php esc_html_e( 'Lab', 'ananta-custom-diamond' ); ?></th>
                <th><?php esc_html_e( 'Cert Number', 'ananta-custom-diamond' ); ?></th>
                <th><?php esc_html_e( 'Cert URL', 'ananta-custom-diamond' ); ?></th>
                <th><?php esc_html_e( 'Location', 'ananta-custom-diamond' ); ?></th>
                <th><?php esc_html_e( 'Price USD', 'ananta-custom-diamond' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $items as $item ) : ?>
                <tr>
                    <td><?php echo esc_html( $item->id ); ?></td>
                    <td><?php echo esc_html( $item->diamond_id ); ?></td>
                    <td><?php echo esc_html( $item->sup_name ); ?></td>
                    <td><?php echo esc_html( $item->shape ); ?></td>
                    <td><?php echo esc_html( $item->size ); ?></td>
                    <td><?php echo esc_html( $item->color ); ?></td>
                    <td><?php echo esc_html( $item->clarity ); ?></td>
                    <td><?php echo esc_html( $item->cut ); ?></td>
                    <td><?php echo esc_html( $item->symmetry ); ?></td>
                    <td><?php echo esc_html( $item->polish ); ?></td>
                    <td><?php echo esc_html( $item->lab ); ?></td>
                    <td><?php echo esc_html( $item->cert_number ); ?></td>
                    <td><?php echo esc_html( $item->cert_url ); ?></td>
                    <td><?php echo esc_html( $item->location ); ?></td>
                    <td><?php echo esc_html( $item->price_usd ); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

/**
 * Shortcode to display products on the frontend.
 * Usage: [ananta_diamond_products]
 * [ananta_diamond_products limit="5" orderby="price" order="ASC"]
 */
add_shortcode( 'ananta_diamond_products', 'ananta_cd_products_shortcode' );
function ananta_cd_products_shortcode( $atts ) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ananta_diamonds';

    // Default attributes
    $atts = shortcode_atts(
        array(
            'limit' => 10, // Default number of products to show
            'orderby' => 'name', // Default order by column
            'order' => 'ASC', // Default order
        ),
        $atts,
        'ananta_diamond_products'
    );

    $limit = intval( $atts['limit'] );
    $orderby = sanitize_key( $atts['orderby'] ); // Sanitize orderby column
    $order = strtoupper( $atts['order'] ); // Sanitize order (ASC or DESC)

    // Validate orderby and order
    $allowed_orderby = ['name', 'sku', 'shape', 'carat', 'color', 'clarity', 'price', 'id'];
    if ( ! in_array( $orderby, $allowed_orderby ) ) {
        $orderby = 'name';
    }
    if ( $order !== 'ASC' && $order !== 'DESC' ) {
        $order = 'ASC';
    }

    $items = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d",
        $limit
    ) );

    if ( empty( $items ) ) {
        return '<p>' . esc_html__( 'No diamond products available at the moment.', 'ananta-custom-diamond' ) . '</p>';
    }

    ob_start(); // Start output buffering
    ?>
    <div class="ananta-diamond-products-list">
        <?php foreach ( $items as $item ) : ?>
            <div class="ananta-diamond-product-item" style="border: 1px solid #eee; margin-bottom: 20px; padding: 15px;">
                <?php if ( ! empty( $item->image_url ) ) : ?>
                    <img src="<?php echo esc_url( $item->image_url ); ?>" alt="<?php echo esc_attr( $item->name ); ?>" style="max-width: 150px; height: auto; margin-bottom: 10px;">
                <?php endif; ?>
                <h3><?php echo esc_html( $item->name ); ?></h3>
                <p><strong><?php esc_html_e( 'SKU:', 'ananta-custom-diamond' ); ?></strong> <?php echo esc_html( $item->sku ); ?></p>
                <?php if ( $item->shape ) : ?><p><strong><?php esc_html_e( 'Shape:', 'ananta-custom-diamond' ); ?></strong> <?php echo esc_html( $item->shape ); ?></p><?php endif; ?>
                <?php if ( $item->carat ) : ?><p><strong><?php esc_html_e( 'Carat:', 'ananta-custom-diamond' ); ?></strong> <?php echo esc_html( $item->carat ); ?></p><?php endif; ?>
                <?php if ( $item->color ) : ?><p><strong><?php esc_html_e( 'Color:', 'ananta-custom-diamond' ); ?></strong> <?php echo esc_html( $item->color ); ?></p><?php endif; ?>
                <?php if ( $item->clarity ) : ?><p><strong><?php esc_html_e( 'Clarity:', 'ananta-custom-diamond' ); ?></strong> <?php echo esc_html( $item->clarity ); ?></p><?php endif; ?>
                <?php if ( $item->cut ) : ?><p><strong><?php esc_html_e( 'Cut:', 'ananta-custom-diamond' ); ?></strong> <?php echo esc_html( $item->cut ); ?></p><?php endif; ?>
                <?php if ( $item->report_number ) : ?><p><strong><?php esc_html_e( 'Report #:', 'ananta-custom-diamond' ); ?></strong> <?php echo esc_html( $item->report_number ); ?></p><?php endif; ?>
                <?php if ( $item->price ) : ?><p><strong><?php esc_html_e( 'Price:', 'ananta-custom-diamond' ); ?></strong> <?php echo esc_html( number_format_i18n( (float) $item->price, 2 ) ); ?></p><?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <style>
        /* Basic styling for the product list - you can move this to a separate CSS file */
        .ananta-diamond-products-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .ananta-diamond-product-item { box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .ananta-diamond-product-item h3 { margin-top: 0; }
    </style>
    <?php
    return ob_get_clean(); // Return buffered content
}


/**
 * Register Custom Gutenberg Block: Random Number.
 */
// function ananta_cd_register_random_number_block() {
//     if ( ! function_exists( 'register_block_type' ) ) {
//         // Gutenberg is not active.
//         return;
//     }

//     // Register block assets (editor script)
//     wp_register_script(
//         'ananta-cd-random-number-block-editor',
//         ANANTA_CD_PLUGIN_URL . 'build/index.js', // Path to your compiled JS file for the editor
//         array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n' ),
//         filemtime( ANANTA_CD_PLUGIN_DIR . 'build/index.js' ) // Versioning based on file modification time
//     );

//     // Register block type
//     register_block_type( 'ananta-custom-diamond/random-number', array(
//         'editor_script' => 'ananta-cd-random-number-block-editor', // Script for the editor
//         'render_callback' => 'ananta_cd_render_random_number_block', // PHP function to render on frontend
//         'attributes' => array(
//             // No attributes needed for dynamic rendering based on page load for this simple case
//         ),
//     ) );
// }
// add_action( 'init', 'ananta_cd_register_random_number_block' );

/**
 * Render callback for the Random Number block (frontend).
 */
// function ananta_cd_render_random_number_block( $attributes ) {
//     $random_number = rand( 1, 100 );
//     return '<div class="ananta-random-number-block"><p>' . sprintf( esc_html__( 'Random Number: %d', 'ananta-custom-diamond' ), $random_number ) . '</p></div>';
// }

/**
 * Enqueue frontend script for the random number block if needed for dynamic client-side updates (optional).
 * For this case, PHP rendering on page load is sufficient.
 * If you wanted the number to change without a page reload (e.g., on button click within the block),
 * you would enqueue a frontend JavaScript file here.
 */
// add_action( 'wp_enqueue_scripts', 'ananta_cd_random_number_block_frontend_scripts' );
// function ananta_cd_random_number_block_frontend_scripts() {
// if ( has_block( 'ananta-custom-diamond/random-number' ) ) { // Only load if the block is present
// wp_enqueue_script(
// 'ananta-cd-random-number-frontend',
// ANANTA_CD_PLUGIN_URL . 'js/random-number-frontend.js',
// array(),
// filemtime( ANANTA_CD_PLUGIN_DIR . 'js/random-number-frontend.js' ),
// true
// );
// }
// }

/**
 * Add a link to the settings page on the plugin list.
 */
add_filter( 'plugin_action_links_' . plugin_basename( ANANTA_CD_PLUGIN_FILE ), 'ananta_cd_plugin_action_links' );
function ananta_cd_plugin_action_links( $links ) {
    $settings_link = '<a href="' . admin_url( 'admin.php?page=ananta-diamonds' ) . '">' . __( 'Settings', 'ananta-custom-diamond' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}

function ananta_cd_enqueue_scripts() {
    if ( !is_product() ) {
        return;
    }

    $script_path = plugin_dir_url( __FILE__ ) . 'build/index.asset.php';
    if (!file_exists($script_path)) {
        wp_enqueue_script('ananta-cd-diamond-selector', 
        plugin_dir_url( __FILE__ ) . 'build/index.js', ['wp-element'], '1.0.0', true);
    } else {
        $script_asset = require $script_path;
        wp_enqueue_script('ananta-cd-diamond-selector', 
        plugin_dir_url( __FILE__ ) . 'build/index.js', 
        $script_asset['dependencies'], 
        $script_asset['version'], 
        true);
    }
}
add_action('wp_enqueue_scripts', 'ananta_cd_enqueue_scripts');

function anata_cd_diamond_selector_render() {
    if (!is_product()) {
        return;
    }
    echo '<div id="ananta_custom_diamond_selector"></div>';
}
add_action('woocommerce_before_add_to_cart_button', 'anata_cd_diamond_selector_render');

function ananta_cd_register_diamonds_block() {
    register_block_type(plugin_dir_path(__FILE__) . 'src/diamonds-block/block.json');
}
add_action('init', 'ananta_cd_register_diamonds_block');

/**
 * Register REST API endpoint for diamonds
 */
add_action('rest_api_init', function () {
    register_rest_route('ananta-custom-diamond/v1', '/diamonds', array(
        'methods' => 'GET',
        'callback' => 'ananta_cd_get_diamonds',
        'permission_callback' => '__return_true'
    ));
});

function ananta_cd_get_diamonds() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'ananta_diamonds';
    
    $diamonds = $wpdb->get_results("SELECT * FROM $table_name ORDER BY price_usd ASC");
    
    if (is_wp_error($diamonds)) {
        return new WP_Error('db_error', 'Database error occurred');
    }
    
    return rest_ensure_response($diamonds);
}

?>