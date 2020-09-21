<?php

// запрет прямого обращения к файлу
if ( ! defined( 'ABSPATH' ) )
    exit;

add_filter( 'rcl_options', 'ppr_addon_admin_options' );
function ppr_addon_admin_options( $options ) {
    // создаем блок
    $options->add_box( 'ppr_free_box_id', array(
        'title' => __( 'Settings Profile WP-PostRatings', 'ppr-rating' ),
        'icon'  => 'fa-star-o'
    ) );


    $message = '';
    if ( ! function_exists( 'the_ratings' ) ) {
        $text = __( 'Go to', 'ppr-rating' ) . ' <a href="' . home_url( '/wp-admin/plugins.php' ) . '" '
            . 'title="' . __( 'Go to the plugins page', 'ppr-rating' ) . '">' . __( 'plugins', 'ppr-rating' ) . '</a> '
            . '' . __( 'and activate WP-PostRatings', 'ppr-rating' );
        $text .= '<style>#options-group-ppr_free_group_1 .rcl-notice__text{text-align:left;margin-left:18px;}</style>';

        $args    = [
            'type'  => 'warning', // info,success,warning,error,simple
            'icon'  => 'fa-exclamation-triangle',
            'title' => __( 'You have not activated the plugin WP-PostRatings', 'ppr-rating' ) . '!',
            'text'  => $text,
        ];
        $message .= rcl_get_notice( $args );
    }

    // сообщения
    if ( ! empty( $message ) ) {
        // создаем группу 1
        $options->box( 'ppr_free_box_id' )->add_group( 'ppr_free_group_1' )->add_options( array(
            [
                'type'    => 'custom',
                'content' => $message
            ],
        ) );
    } else {
        // создаем группу 1
        $options->box( 'ppr_free_box_id' )->add_group( 'ppr_free_group_1', array(
            'title' => __( 'Set a record rating', 'ppr-rating' ) . ':'
        ) )->add_options( array(
            [
                'title'   => __( 'You use a five or ten-point rating system', 'ppr-rating' ) . '?',
                'type'    => 'radio',
                'values'  => [ 5 => '5 ' . __( 'points', 'ppr-rating' ), 10 => '10 ' . __( 'points', 'ppr-rating' ) ],
                'default' => 5,
                'slug'    => 'nmbr_rating',
                'notice'  => __( 'Select your rating system. <br/> Default 5', 'ppr-rating' ),
            ],
            [
                'title'  => __( 'How many entries per page output', 'ppr-rating' ) . '?',
                'type'   => 'select',
                'slug'   => 'nmbr_per_page',
                'values' => [ 10 => '10', 20 => '20', 30 => '30', 40 => '40', 50 => '50', 60 => '60', 70 => '70', 80 => '80', 90 => '90', 100 => '100' ],
                'notice' => __( 'Select a value. <br/> Default 20', 'ppr-rating' ),
            ]
        ) );
    }

    return $options;
}
