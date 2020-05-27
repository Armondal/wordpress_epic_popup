<?php
/*
Plugin Name: Epic Popup Creator
Plugin URI: https://github.com/Armondal/wordpress_epic_popup
Description: a plugin for creating popup for wordpress website
Version: 1.0.0
Author: Arnab
Author URI: http://devsinnovation.com/
License: GPLv2 or later
Text Domain: ppc
Domain Path: /languages/
 */

use Carbon_Fields\Container;
use Carbon_Fields\Field;

function popupCreator() {
    require_once "vendor\autoload.php";
    \Carbon_Fields\Carbon_Fields::boot();
    define( 'WP_DEBUG', true );

}
add_action( 'plugins_loaded', 'popupCreator' );

function register_cpt_popup() {

    $labels = array(
        "name"               => __( "Popups", "ppc" ),
        "singular_name"      => __( "Popup", "ppc" ),
        "featured_image"     => __( 'Popup Image', 'ppc' ),
        "set_featured_image" => __( 'Set Popup Image', 'ppc' ),
    );

    $args = array(
        "label"               => __( "Popups", "ppc" ),
        "labels"              => $labels,
        "description"         => "",
        "public"              => false,
        "publicly_queryable"  => true,
        "show_ui"             => true,
        "delete_with_user"    => false,
        "show_in_rest"        => true,
        "has_archive"         => false,
        "show_in_menu"        => true,
        "show_in_nav_menus"   => false,
        "exclude_from_search" => true,
        "capability_type"     => "post",
        "map_meta_cap"        => true,
        "hierarchical"        => false,
        "rewrite"             => array( "slug" => "popup", "with_front" => true ),
        "query_var"           => true,
        "supports"            => array( "title", "thumbnail" ),
    );

    register_post_type( "popup", $args );
}
add_action( 'init', 'register_cpt_popup' );
function register_popup_size() {
    add_image_size( 'popup-landscape', '700', '400', true );
    add_image_size( 'popup-square', '400', '400', true );
}
add_action( 'init', 'register_popup_size' );
function load_assets() {
    wp_enqueue_style( 'popupcreator-css', plugin_dir_url( __FILE__ ) . "assets/css/modal.css", null, time() );
    wp_enqueue_script( 'plainmodal-js', plugin_dir_url( __FILE__ ) . "assets/js/plain-modal.min.js", null, "1.0.27", true );

}

add_action( 'init', 'load_assets' );

function ppc_load_frontend_assets() {
    wp_enqueue_script( 'popupcreator-main', plugin_dir_url( __FILE__ ) . "assets/js/popupcreator-main.js", array(
        'jquery',
        'plainmodal-js',
    ), time(), true );
}

add_action( 'wp_enqueue_scripts', 'ppc_load_frontend_assets' );

function popup_creator_demo_metabox() {
    $ppc_pages = get_pages();
    $ppc_select_pages_metabox_arg = array(
        "#ALL" => "Show IN ALL Pages",
    );
    foreach ( $ppc_pages as $page ) {
        $ppc_select_pages_metabox_arg[$page->ID] = $page->post_title;
    }
    // var_dump( $ppc_select_pages_metabox_arg );

    Container::make( 'post_meta', __( 'Popup Setting', 'ppc' ) )
        ->where( 'post_type', '=', 'popup' )
        ->set_context( 'normal' )
        ->add_fields( array(
            Field::make( 'checkbox', 'ppc_active', __( 'Active', 'ppc' ) )->set_option_value( "1" ),
            Field::make( 'text', 'ppc_heading', __( 'Heading', 'ppc' ) ),
            Field::make( 'text', 'ppc_url', __( 'URL', 'ppc' ) )->set_attribute( 'placeholder', 'https://www.example.com/' )->set_attribute( 'type', 'url' ),
            Field::make( 'text', 'ppc_button_text', __( 'Button Text', 'ppc' ) ),
            Field::make( 'color', 'ppc_background_color', __( 'Background Color' ) )->set_width( 34 ),
            Field::make( 'color', 'ppc_heading_color', __( 'Heading Color' ) )->set_width( 33 ),
            Field::make( 'color', 'ppc_link_color', __( 'link Color' ) )->set_width( 33 ),
            Field::make( 'text', 'ppc_delay_sec', __( 'Display Popup After(sec)', 'ppc' ) )->set_attribute( 'placeholder', '2 ' )->set_attribute( 'type', 'number' ),
            Field::make( 'checkbox', 'ppc_auto_hide', __( 'Auto Hide', 'ppc' ) )->set_option_value( 'yes' )->set_width( 50 ),
            Field::make( 'checkbox', 'ppc_dis_exit', __( 'Display on Exit', 'ppc' ) )->set_option_value( '1' )->set_width( 50 ),
            Field::make( 'select', 'ppc_img_size', __( 'Image Size', 'ppc' ) )->add_options( array(
                'popup-landscape' => 'Landscape',
                'popup-square'    => 'Square',

            ) ),
            Field::make( 'select', 'ppc_select_page', __( 'Show Only in This Page', 'ppc' ) )->add_options( $ppc_select_pages_metabox_arg ),

        ) );
}
add_action( 'carbon_fields_register_fields', 'popup_creator_demo_metabox' );

function print_modal_markup() {
    $arguments = array(
        'post_type'   => 'popup',
        'post_status' => 'publish',
        'meta_key'    => '_ppc_active',
        'meta_value'  => 1,
    );
    $query = new WP_Query( $arguments );
    $current_page_id = get_the_ID();

    while ( $query->have_posts() ) {
        $query->the_post();
        $active = esc_attr( get_post_meta( get_the_ID(), '_ppc_active', true ) );
        $size = esc_attr( get_post_meta( get_the_ID(), '_ppc_img_size', true ) );
        $heading_max_width = '700px';
        if ( 'popup-square' == $size ) {
            $heading_max_width = '400px';
        }
        $exit = esc_attr( get_post_meta( get_the_ID(), '_ppc_dis_exit', true ) );
        $delay = esc_attr( get_post_meta( get_the_ID(), '_ppc_delay_sec', true ) );
        $auto_hide = esc_attr( get_post_meta( get_the_ID(), '_ppc_auto_hide', true ) );
        $ppc_page_id = esc_attr( get_post_meta( get_the_ID(), '_ppc_select_page', true ) );
        $ppc_heading = esc_attr( get_post_meta( get_the_ID(), '_ppc_heading', true ) );
        $ppc_url = esc_url( get_post_meta( get_the_ID(), '_ppc_url', true ) );
        $ppc_heading_color = esc_attr( get_post_meta( get_the_ID(), '_ppc_heading_color', true ) );
        $ppc_link_color = esc_attr( get_post_meta( get_the_ID(), '_ppc_link_color', true ) );
        $ppc_background_color = esc_attr( get_post_meta( get_the_ID(), '_ppc_background_color', true ) );
        $ppc_button_text = esc_attr( get_post_meta( get_the_ID(), '_ppc_button_text', true ) );
        $image = get_the_post_thumbnail_url( get_the_ID(), $size );
        if ( '1' != $active ) {
            continue;
        }

        if ( $delay > 0 ) {$delay *= 1000;} else { $delay = 0;}
        if ( $auto_hide == "" ) {
            $auto_hide = "no";
        }

        if ( $current_page_id == $ppc_page_id || '#ALL' == $ppc_page_id ) {
            ?>
        <div class="modal-content modal-id-<?php echo the_ID(); ?>" data-modal-id="<?php the_ID();?>" auto-hide="<?php echo $auto_hide; ?>"
            data-size="<?php echo esc_attr( $size ); ?>"
            data-exit="<?php echo esc_attr( $exit ) ?>"
            data-delay="<?php echo esc_attr( $delay ) ?>"
        >
            <div class="close-button-container"><img class="close-button" width="30"
                      src="<?php echo plugin_dir_url( __FILE__ ) . "assets/img/x.png"; ?>" alt="<?php _e( 'Close', 'popupcreator' )?>">
            </div>
            <img class="ppc-popup-image" src="<?php echo esc_url( $image ); ?>"
                 alt="<?php _e( 'PopUp', 'ppc' )?>">

            <div class="ppc-main-text-container">
            <?php
if ( '' != $ppc_heading ) {
                ?>
                <span class="pop-text"><?php _e( $ppc_heading, 'ppc' )?></span>
            <?php
}
            if ( '' != $ppc_button_text && '' != $ppc_url ) {
                ?>
                <a class="pop-button" href="<?php echo $ppc_url ?>"><?php _e( $ppc_button_text, 'ppc' )?></a>

            <?php
}
            ?>
            </div>

                 </div>

        </div>
        <?php
}
    }
    wp_reset_query();

    wp_localize_script( 'popupcreator-main', 'ppc_inline_style', array(
        'heading_color'     => $ppc_heading_color,
        'link_color'        => $ppc_link_color,
        'background_color'  => $ppc_background_color,
        'heading_max_width' => $heading_max_width,
    ) );
}
add_action( 'wp_footer', 'print_modal_markup' );

function ppc_popup_columns( $columns ) {
    if ( isset( $_GET['post_type'] ) && 'popup' == $_GET['post_type'] ) {
        unset( $columns['date'] );
        $columns['selected_page'] = __( 'Selected Papge', 'ppc' );
        $columns['popup_type'] = __( 'Popup Type', 'ppc' );
        $columns['popup_status'] = __( 'Popup Status', 'ppc' );
        $columns['date'] = __( 'Date', 'ppc' );
    }
    return $columns;
}

add_filter( 'manage_posts_columns', 'ppc_popup_columns' );

function ppc_popup_columns_data( $column, $post_id ) {
    $ppc_selected_page_id = get_post_meta( get_the_ID(), '_ppc_select_page', true );
    $exit = get_post_meta( get_the_ID(), '_ppc_dis_exit', true );
    $active = get_post_meta( get_the_ID(), '_ppc_active', true );
    if ( 'selected_page' == $column ) {
        if ( '#ALL' != $ppc_selected_page_id ) {
            ?>
            <a href="<?php echo esc_url( get_permalink( $ppc_selected_page_id ) ); ?>" target="_blank">
                <?php echo get_the_title( $ppc_selected_page_id ); ?>
            </a>
            <?php
} else {
            echo "<b>";
            echo "Global Popup";
            echo "</b>";
        }

    } elseif ( 'popup_type' == $column ) {
        if ( '1' == $exit ) {
            echo "Display on Exit";
        } else {
            echo "Display on Start";

        }
    } elseif ( 'popup_status' == $column ) {
        if ( '1' == $active ) {
            echo "Active";
        } else {
            echo "Not Active";

        }
    }
}
add_filter( 'manage_posts_custom_column', 'ppc_popup_columns_data', 10, 2 );

function ppc_add_filters() {
    if ( isset( $_GET['post_type'] ) && 'popup' == $_GET['post_type'] ) {
        $ppc_pages = get_pages();

        ?>
        <select name="ppc_page_filter">
            <option value="0" <?php if ( isset( $_GET['ppc_page_filter'] ) && '0' == $_GET['ppc_page_filter'] ) {
            echo "selected";
        }
        ?>>
        <?php _e( "Select A Page", "ppc" )?>
        </option>
            <option value="#ALL" <?php if ( isset( $_GET['ppc_page_filter'] ) && '#ALL' == $_GET['ppc_page_filter'] ) {
            echo "selected";
        }
        ?> > <?php _e( "Global Filters", "ppc" )?> </option>
            <?php
foreach ( $ppc_pages as $page ) {
            ?>
                 <option value="<?php echo $page->ID ?>" <?php if ( isset( $_GET['ppc_page_filter'] ) && $page->ID == $_GET['ppc_page_filter'] ) {
                echo "selected";
            }
            ?>  > <?php echo $page->post_title ?> </option>
            <?php
}
        ?>
        </select>
    <select name="ppc_popup_type_filter">
        <option value="0" <?php if ( isset( $_GET['ppc_popup_type_filter'] ) && '0' == $_GET['ppc_popup_type_filter'] ) {
            echo "selected";
        }
        ?>> <?php _e( "Select a Type", "ppc" )?> </option>
        <option value="1"  <?php if ( isset( $_GET['ppc_popup_type_filter'] ) && '1' == $_GET['ppc_popup_type_filter'] ) {
            echo "selected";
        }
        ?>  > <?php _e( "Start Popup", "ppc" )?> </option>
        <option value="2"  <?php if ( isset( $_GET['ppc_popup_type_filter'] ) && '2' == $_GET['ppc_popup_type_filter'] ) {
            echo "selected";
        }
        ?>> <?php _e( "Exit Popup", "ppc" )?> </option>
    </select>
    <select name="ppc_popup_status_filter">
        <option value="0"  <?php if ( isset( $_GET['ppc_popup_status_filter'] ) && '0' == $_GET['ppc_popup_status_filter'] ) {
            echo "selected";
        }
        ?>> <?php _e( "Select Active/Inactive Filters", "ppc" )?> </option>
        <option value="1"<?php if ( isset( $_GET['ppc_popup_status_filter'] ) && '1' == $_GET['ppc_popup_status_filter'] ) {
            echo "selected";
        }
        ?>> <?php _e( "Active Popup", "ppc" )?> </option>
        <option value="2"<?php if ( isset( $_GET['ppc_popup_status_filter'] ) && '2' == $_GET['ppc_popup_status_filter'] ) {
            echo "selected";
        }
        ?>> <?php _e( "Inactive Popup", "ppc" )?> </option>
    </select>
        <?php
}

}

add_action( 'restrict_manage_posts', 'ppc_add_filters' );

function ppc_filter_data( $wpquery ) {
    if ( !is_admin() ) {
        return;
    }
    $ppc_page_filter_value = isset( $_GET['ppc_page_filter'] ) ? $_GET['ppc_page_filter'] : "0";
    if ( '0' != $ppc_page_filter_value ) {
        $wpquery->set( 'meta_query', array(
            array(
                'key'     => '_ppc_select_page',
                'value'   => $ppc_page_filter_value,
                'compare' => '==',
            ),
        ) );
    }

    $ppc_ppc_popup_type_filter = isset( $_GET['ppc_popup_type_filter'] ) ? $_GET['ppc_popup_type_filter'] : "0";
    if ( '1' == $ppc_ppc_popup_type_filter ) {
        $wpquery->set( 'meta_query', array(
            array(
                'key'     => '_ppc_dis_exit',
                'value'   => '',
                'compare' => '==',
            ),
        ) );
    } elseif ( '2' == $ppc_ppc_popup_type_filter ) {
        $wpquery->set( 'meta_query', array(
            array(
                'key'     => '_ppc_dis_exit',
                'value'   => '1',
                'compare' => '==',
            ),
        ) );
    }
    $ppc_popup_status_filter = isset( $_GET['ppc_popup_status_filter'] ) ? $_GET['ppc_popup_status_filter'] : "0";
    if ( '1' == $ppc_popup_status_filter ) {
        $wpquery->set( 'meta_query', array(
            array(
                'key'     => '_ppc_active',
                'value'   => '1',
                'compare' => '==',
            ),
        ) );
    } elseif ( '2' == $ppc_popup_status_filter ) {
        $wpquery->set( 'meta_query', array(
            array(
                'key'     => '_ppc_active',
                'value'   => '',
                'compare' => '==',
            ),
        ) );
    }
}
add_action( 'pre_get_posts', 'ppc_filter_data' );
