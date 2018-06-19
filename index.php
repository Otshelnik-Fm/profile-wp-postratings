<?php

/*

╔═╗╔╦╗╔═╗╔╦╗
║ ║ ║ ╠╣ ║║║ https://otshelnik-fm.ru
╚═╝ ╩ ╚  ╩ ╩

*/

// запрет прямого обращения к файлу
if ( !defined('ABSPATH') ) exit;


// подключим файлы
require_once 'inc/settings.php';
require_once 'inc/class-ppr-postratings.php';


// грузим стили в кабинете
function ppr_load_style(){
    if( !rcl_is_office() ) return false;

    rcl_enqueue_style('ppr_load_style',rcl_addon_url('style.css', __FILE__));
}
if(!is_admin()){
    add_action('rcl_enqueue_scripts','ppr_load_style',10);
}


// вкладка в лк
function ppr_tab(){
    $tab_data = array(
        'id' => 'tab_rayt',
        'name' => __('Rating of publications', 'ppr-rating'),
        'supports' => array('ajax', 'cache'),
        'public' => 1,
        'icon' => 'fa-bar-chart',
        'output' => 'menu',
        'content' => array(
            array(
                'callback' => array(
                    'name' => 'ppr_content_profile_rating'
                )
            )
        )
    );

    rcl_tab($tab_data);
}
add_action('init', 'ppr_tab');


// коллбек. ф-ция принимает id кабинета
function ppr_content_profile_rating($user_lk){
    $block_wprecall = '<div class="ppr_warning">'
            . __('You have not activated the plugin <a href="https://wordpress.org/plugins/wp-postratings/" title="Go to the repository WordPress">WP-PostRatings</a>', 'ppr-rating')
            . '</div>';
    if(function_exists('the_ratings')){         // если используется плагин wp_postrating стартуем
        $obj = new pprPostRatings($user_lk);
        $block_wprecall = $obj->get_rating();
    }

    return $block_wprecall;
}


// чтобы ссылки пагинации в фильтре работали
function ppr_add_page_link_attributes($attrs){
    $attrs['class'] = 'ppr_ajax_page_navi';     // fake

    return $attrs;
}


// подключим перевод
function ppr_textdomain(){
    load_textdomain( 'ppr-rating', rcl_addon_path(__FILE__).'/languages/ppr-rating-'.get_locale().'.mo' );
}
add_action('plugins_loaded', 'ppr_textdomain',10);

