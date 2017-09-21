<?php

// запрет прямого обращения к файлу
if ( !defined('ABSPATH') ) exit;


// добавляем настройки в админке
function ppr_admin_settings($options){
    $message = '';
    if (!function_exists('the_ratings')){ // если не активен WP-PostRatings
        $message = '<br/><span class="adm_warn adm_b16">У вас не активирован плагин WP-PostRatings!</span><br/>';
        $message .= 'Перейдите <a href="' . home_url('/wp-admin/plugins.php') . '" '
                . 'title="Перейти на страницу плагинов">на страницу плагинов</a> и активируйте WP-PostRatings<br/><hr><br/>';
    }

    $opt = new Rcl_Options(__FILE__);

    $options .= $opt->options(
            'Настройки Profile WP-PostRatings', $opt->option_block(
                    array(
                        $opt->title('Установить рейтинг записей:'),
                        '<div>' . $message . '</div>', // предупреждение - нет WP-PostRatings
                        $opt->label('<br/>Вы используете пяти или десятибальную систему рейтинга?'),
                        $opt->option('select', array(
                            'name' => 'nmbr_rating',
                            'options' => array(5 => '5 баллов', 10 => '10 баллов',)
                        )),
                        $opt->notice('Выберите вашу систему рейтинга.<br/>По умолчанию 5'),
                        $opt->label('<br/>Сколько записей на страницу выводить?'),
                        $opt->option('select', array(
                            'name' => 'nmbr_per_page',
                            'options' => array(10 => '10', 20 => '20', 30 => '30', 40 => '40', 50 => '50', 60 => '60', 70 => '70', 80 => '80', 90 => '90', 100 => '100',)
                        )),
                        $opt->notice('Выберите значение.<br/>По умолчанию 20'),
                    )
            )
    );

    return $options;
}
add_filter('admin_options_wprecall', 'ppr_admin_settings');
