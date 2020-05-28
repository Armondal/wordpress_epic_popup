<?php
/*
Plugin Name: Epic Popup Creator
Plugin URI: https://github.com/Armondal/wordpress_epic_popup
Description: a plugin for creating popup for wordpress website
Version: 1.0.0
Author: Arnab
Author URI: http://devsinnovation.com/
License: GPLv2 or later
Text Domain: eppc
Domain Path: /languages/
 */

use Carbon_Fields\Container;
use Carbon_Fields\Field;

function eppc_popupCreator() {
    require_once "vendor\autoload.php";
    \Carbon_Fields\Carbon_Fields::boot();
    define( 'WP_DEBUG', true );

}
add_action( 'plugins_loaded', 'eppc_popupCreator' );

function eppc_register_cpt_popup() {

    $labels = array(
        "name"               => __( "Popups", "eppc" ),
        "singular_name"      => __( "Popup", "eppc" ),
        "featured_image"     => __( 'Popup Image', 'eppc' ),
        "set_featured_image" => __( 'Set Popup Image', 'eppc' ),
    );

    $args = array(
        "label"               => __( "Popups", "eppc" ),
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
add_action( 'init', 'eppc_register_cpt_popup' );
function eppc_register_popup_size() {
    add_image_size( 'popup-landscape', '700', '400', true );
    add_image_size( 'popup-square', '400', '400', true );
}
add_action( 'init', 'eppc_register_popup_size' );
function eppc_load_assets() {
    wp_enqueue_style( 'popupcreator-css', plugin_dir_url( __FILE__ ) . "assets/css/modal.css", null, time() );
    wp_enqueue_script( 'plainmodal-js', plugin_dir_url( __FILE__ ) . "assets/js/plain-modal.min.js", null, "1.0.27", true );

}

add_action( 'init', 'eppc_load_assets' );

function eppc_load_frontend_assets() {
    wp_enqueue_script( 'popupcreator-main', plugin_dir_url( __FILE__ ) . "assets/js/popupcreator-main.js", array(
        'jquery',
        'plainmodal-js',
    ), time(), true );
}

add_action( 'wp_enqueue_scripts', 'eppc_load_frontend_assets' );

function popup_creator_demo_metabox() {
    $eppc_pages = get_pages();
    $eppc_select_pages_metabox_arg = array(
        "#ALL" => "Show IN ALL Pages",
    );
    foreach ( $eppc_pages as $page ) {
        $eppc_select_pages_metabox_arg[$page->ID] = $page->post_title;
    }
    // var_dump( $eppc_select_pages_metabox_arg );

    Container::make( 'post_meta', __( 'Popup Setting', 'eppc' ) )
        ->where( 'post_type', '=', 'popup' )
        ->set_context( 'normal' )
        ->add_fields( array(
            Field::make( 'checkbox', 'eppc_active', __( 'Active', 'eppc' ) )->set_option_value( "1" ),
            Field::make( 'text', 'eppc_heading', __( 'Heading', 'eppc' ) ),
            Field::make( 'text', 'eppc_url', __( 'URL', 'eppc' ) )->set_attribute( 'placeholder', 'https://www.example.com/' )->set_attribute( 'type', 'url' ),
            Field::make( 'text', 'eppc_button_text', __( 'Button Text', 'eppc' ) ),
            Field::make( 'color', 'eppc_background_color', __( 'Background Color' ) )->set_width( 34 ),
            Field::make( 'color', 'eppc_heading_color', __( 'Heading Color' ) )->set_width( 33 ),
            Field::make( 'color', 'eppc_link_color', __( 'link Color' ) )->set_width( 33 ),
            Field::make( 'text', 'eppc_delay_sec', __( 'Display Popup After(sec)', 'eppc' ) )->set_attribute( 'placeholder', '2 ' )->set_attribute( 'type', 'number' ),
            Field::make( 'checkbox', 'eppc_auto_hide', __( 'Auto Hide', 'eppc' ) )->set_option_value( 'yes' )->set_width( 50 ),
            Field::make( 'checkbox', 'eppc_dis_exit', __( 'Display on Exit', 'eppc' ) )->set_option_value( '1' )->set_width( 50 ),
            Field::make( 'select', 'eppc_img_size', __( 'Image Size', 'eppc' ) )->add_options( array(
                'popup-landscape' => 'Landscape',
                'popup-square'    => 'Square',

            ) ),
            Field::make( 'select', 'eppc_select_page', __( 'Show Only in This Page', 'eppc' ) )->add_options( $eppc_select_pages_metabox_arg ),

        ) );
}
add_action( 'carbon_fields_register_fields', 'popup_creator_demo_metabox' );

function print_modal_markup() {
    $arguments = array(
        'post_type'   => 'popup',
        'post_status' => 'publish',
        'meta_key'    => '_eppc_active',
        'meta_value'  => 1,
    );
    $query = new WP_Query( $arguments );
    $current_page_id = get_the_ID();

    while ( $query->have_posts() ) {
        $query->the_post();
        $active = esc_attr( get_post_meta( get_the_ID(), '_eppc_active', true ) );
        $size = esc_attr( get_post_meta( get_the_ID(), '_eppc_img_size', true ) );
        $heading_max_width = '700px';
        if ( 'popup-square' == $size ) {
            $heading_max_width = '400px';
        }
        $exit = esc_attr( get_post_meta( get_the_ID(), '_eppc_dis_exit', true ) );
        $delay = esc_attr( get_post_meta( get_the_ID(), '_eppc_delay_sec', true ) );
        $auto_hide = esc_attr( get_post_meta( get_the_ID(), '_eppc_auto_hide', true ) );
        $eppc_page_id = esc_attr( get_post_meta( get_the_ID(), '_eppc_select_page', true ) );
        $eppc_heading = esc_attr( get_post_meta( get_the_ID(), '_eppc_heading', true ) );
        $eppc_url = esc_url( get_post_meta( get_the_ID(), '_eppc_url', true ) );
        $eppc_heading_color = esc_attr( get_post_meta( get_the_ID(), '_eppc_heading_color', true ) );
        $eppc_link_color = esc_attr( get_post_meta( get_the_ID(), '_eppc_link_color', true ) );
        $eppc_background_color = esc_attr( get_post_meta( get_the_ID(), '_eppc_background_color', true ) );
        $eppc_button_text = esc_attr( get_post_meta( get_the_ID(), '_eppc_button_text', true ) );
        $image = get_the_post_thumbnail_url( get_the_ID(), $size );
        if ( '1' != $active ) {
            continue;
        }

        if ( $delay > 0 ) {$delay *= 1000;} else { $delay = 0;}
        if ( $auto_hide == "" ) {
            $auto_hide = "no";
        }

        if ( $current_page_id == $eppc_page_id || '#ALL' == $eppc_page_id ) {
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
                 alt="<?php _e( 'PopUp', 'eppc' )?>">

            <div class="ppc-main-text-container">
            <?php
if ( '' != $eppc_heading ) {
                ?>
                <span class="pop-text"><?php _e( $eppc_heading, 'eppc' )?></span>
            <?php
}
            if ( '' != $eppc_button_text && '' != $eppc_url ) {
                ?>
                <a class="pop-button" href="<?php echo $eppc_url ?>"><?php _e( $eppc_button_text, 'eppc' )?></a>

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
        'heading_color'     => $eppc_heading_color,
        'link_color'        => $eppc_link_color,
        'background_color'  => $eppc_background_color,
        'heading_max_width' => $heading_max_width,
    ) );
}
add_action( 'wp_footer', 'print_modal_markup' );

function eppc_popup_columns( $columns ) {
    //     $eppc_post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( $_GET['post_type'] ) : "";

    if ( isset( $_GET['post_type'] ) && 'popup' == $_GET['post_type'] ) {
        unset( $columns['date'] );
        $columns['selected_page'] = __( 'Selected Papge', 'eppc' );
        $columns['popup_type'] = __( 'Popup Type', 'eppc' );
        $columns['popup_status'] = __( 'Popup Status', 'eppc' );
        $columns['date'] = __( 'Date', 'eppc' );
    }
    return $columns;
}

add_filter( 'manage_posts_columns', 'eppc_popup_columns' );

function eppc_popup_columns_data( $column, $post_id ) {
    $eppc_selected_page_id = get_post_meta( get_the_ID(), '_eppc_select_page', true );
    $exit = get_post_meta( get_the_ID(), '_eppc_dis_exit', true );
    $active = get_post_meta( get_the_ID(), '_eppc_active', true );
    if ( 'selected_page' == $column ) {
        if ( '#ALL' != $eppc_selected_page_id ) {
            ?>
            <a href="<?php echo esc_url( get_permalink( $eppc_selected_page_id ) ); ?>" target="_blank">
                <?php echo get_the_title( $eppc_selected_page_id ); ?>
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
add_filter( 'manage_posts_custom_column', 'eppc_popup_columns_data', 10, 2 );

function eppc_add_filters() {
    if ( isset( $_GET['post_type'] ) && 'popup' == $_GET['post_type'] ) {
        $eppc_pages = get_pages();

        ?>
        <select name="eppc_page_filter">
            <option value="0" <?php if ( isset( $_GET['eppc_page_filter'] ) && '0' == $_GET['eppc_page_filter'] ) {
            echo "selected";
        }
        ?>>
        <?php _e( "Select A Page", "eppc" )?>
        </option>
            <option value="#ALL" <?php if ( isset( $_GET['eppc_page_filter'] ) && '#ALL' == $_GET['eppc_page_filter'] ) {
            echo "selected";
        }
        ?> > <?php _e( "Global Filters", "eppc" )?> </option>
            <?php
foreach ( $eppc_pages as $page ) {
            ?>
                 <option value="<?php echo $page->ID ?>" <?php if ( isset( $_GET['eppc_page_filter'] ) && $page->ID == $_GET['eppc_page_filter'] ) {
                echo "selected";
            }
            ?>  > <?php echo $page->post_title ?> </option>
            <?php
}
        ?>
        </select>
    <select name="eppc_popup_type_filter">
        <option value="0" <?php if ( isset( $_GET['eppc_popup_type_filter'] ) && '0' == $_GET['eppc_popup_type_filter'] ) {
            echo "selected";
        }
        ?>> <?php _e( "Select a Type", "eppc" )?> </option>
        <option value="1"  <?php if ( isset( $_GET['eppc_popup_type_filter'] ) && '1' == $_GET['eppc_popup_type_filter'] ) {
            echo "selected";
        }
        ?>  > <?php _e( "Start Popup", "eppc" )?> </option>
        <option value="2"  <?php if ( isset( $_GET['eppc_popup_type_filter'] ) && '2' == $_GET['eppc_popup_type_filter'] ) {
            echo "selected";
        }
        ?>> <?php _e( "Exit Popup", "eppc" )?> </option>
    </select>
    <select name="eppc_popup_status_filter">
        <option value="0"  <?php if ( isset( $_GET['eppc_popup_status_filter'] ) && '0' == $_GET['eppc_popup_status_filter'] ) {
            echo "selected";
        }
        ?>> <?php _e( "Select Active/Inactive Filters", "eppc" )?> </option>
        <option value="1"<?php if ( isset( $_GET['eppc_popup_status_filter'] ) && '1' == $_GET['eppc_popup_status_filter'] ) {
            echo "selected";
        }
        ?>> <?php _e( "Active Popup", "eppc" )?> </option>
        <option value="2"<?php if ( isset( $_GET['eppc_popup_status_filter'] ) && '2' == $_GET['eppc_popup_status_filter'] ) {
            echo "selected";
        }
        ?>> <?php _e( "Inactive Popup", "eppc" )?> </option>
    </select>
        <?php
}

}

add_action( 'restrict_manage_posts', 'eppc_add_filters' );

function eppc_filter_data( $wpquery ) {
    if ( !is_admin() ) {
        return;
    }
    $eppc_page_filter_value = isset( $_GET['eppc_page_filter'] ) ? sanitize_text_field( $_GET['eppc_page_filter'] ) : "0";
    if ( '0' != $eppc_page_filter_value ) {
        $wpquery->set( 'meta_query', array(
            array(
                'key'     => '_eppc_select_page',
                'value'   => $eppc_page_filter_value,
                'compare' => '==',
            ),
        ) );
    }

    $eppc_eppc_popup_type_filter = isset( $_GET['eppc_popup_type_filter'] ) ? sanitize_text_field( $_GET['eppc_popup_type_filter'] ) : "0";
    if ( '1' == $eppc_eppc_popup_type_filter ) {
        $wpquery->set( 'meta_query', array(
            array(
                'key'     => '_eppc_dis_exit',
                'value'   => '',
                'compare' => '==',
            ),
        ) );
    } elseif ( '2' == $eppc_eppc_popup_type_filter ) {
        $wpquery->set( 'meta_query', array(
            array(
                'key'     => '_eppc_dis_exit',
                'value'   => '1',
                'compare' => '==',
            ),
        ) );
    }
    $eppc_popup_status_filter = isset( $_GET['eppc_popup_status_filter'] ) ? sanitize_text_field( $_GET['eppc_popup_status_filter'] ) : "0";
    if ( '1' == $eppc_popup_status_filter ) {
        $wpquery->set( 'meta_query', array(
            array(
                'key'     => '_eppc_active',
                'value'   => '1',
                'compare' => '==',
            ),
        ) );
    } elseif ( '2' == $eppc_popup_status_filter ) {
        $wpquery->set( 'meta_query', array(
            array(
                'key'     => '_eppc_active',
                'value'   => '',
                'compare' => '==',
            ),
        ) );
    }
}
add_action( 'pre_get_posts', 'eppc_filter_data' );
