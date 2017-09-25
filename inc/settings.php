<?php

// запрет прямого обращения к файлу
if ( !defined('ABSPATH') ) exit;


// добавляем настройки в админке
function ppr_admin_settings($options){
    $message = '';
    if (!function_exists('the_ratings')){ // если не активен WP-PostRatings
        $message = '<br/><span class="adm_warn adm_b16">'.__('You have not activated the plugin WP-PostRatings', 'ppr-rating').'!</span><br/>';
        $message .= __('Go to', 'ppr-rating').' <a href="' . home_url('/wp-admin/plugins.php') . '" '
                . 'title="'.__('Go to the plugins page', 'ppr-rating').'">'.__('plugins', 'ppr-rating').'</a> '.__('and activate WP-PostRatings', 'ppr-rating').'<br/><hr><br/>';
    }

    $opt = new Rcl_Options(__FILE__);

    $options .= $opt->options(
        __('Settings Profile WP-PostRatings', 'ppr-rating'), $opt->option_block(
            array(
                $opt->title(__('Set a record rating', 'ppr-rating').':'),
                '<div>' . $message . '</div>', // предупреждение - нет WP-PostRatings
                $opt->label('<br/>'.__('You use a five or ten-point rating system', 'ppr-rating').'?'),
                $opt->option('select', array(
                    'name' => 'nmbr_rating',
                    'options' => array(5 => '5 '.__('points', 'ppr-rating'), 10 => '10 '.__('points', 'ppr-rating'),)
                )),
                $opt->notice(__('Select your rating system. <br/> Default 5', 'ppr-rating')),
                $opt->label('<br/>'.__('How many entries per page output', 'ppr-rating').'?'),
                $opt->option('select', array(
                    'name' => 'nmbr_per_page',
                    'options' => array(10 => '10', 20 => '20', 30 => '30', 40 => '40', 50 => '50', 60 => '60', 70 => '70', 80 => '80', 90 => '90', 100 => '100',)
                )),
                $opt->notice(__('Select a value. <br/> Default 20', 'ppr-rating')),
            )
        )
    );

    return $options;
}
add_filter('admin_options_wprecall', 'ppr_admin_settings');
