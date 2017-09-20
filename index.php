<?php


require_once('inc/settings.php'); // настройки


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
        'name' => 'Рейтинг записей',
        'supports' => array('ajax', 'cache'),
        'public' => 1,
        'icon' => 'fa-bar-chart',
        'output' => 'menu',
        'content' => array(
            array(
                'callback' => array(
                    'name' => 'ppr_content_profile_wp_postrating'
                )
            )
        )
    );

    rcl_tab($tab_data);
}
add_action('init', 'ppr_tab');


// коллбек
function ppr_content_profile_wp_postrating(){
    $block_wprecall = '<div class="ppr_warning">'
            . 'У вас не активирован плагин <a href="https://wordpress.org/plugins/wp-postratings/" title="Перейти в репозиторий вордпресс">WP-PostRatings</a>'
            . '</div>';
    if(function_exists('the_ratings')) { // если используется плагин wp_postrating стартуем
        $block_wprecall = profile_wp_postrating();
    }

    return $block_wprecall;
}


// чтобы ссылки пагинации в фильтре работали
function ppr_add_page_link_attributes($attrs){
    $attrs['class'] = 'ppr_ajax_page_navi'; // fake

    return $attrs;
}



// достанем рейтинг
function profile_wp_postrating(){
    global $wpdb, $user_LK;

    $five_or_ten = rcl_get_option('nmbr_rating', 5); // 5-10-ти или бальный рейтинг

    $postratings_log_perpage = isset($_GET['in_page']) ? intval($_GET['in_page']) : rcl_get_option('nmbr_per_page', 20); // записей на страницу

    $postratings_sortorder = 'DESC';
    if ( isset($_GET['order']) && $_GET['order'] == 'asc' ){
        $postratings_sortorder = 'ASC';
    }

    $get_by = '';
    if ( isset($_GET['by']) && in_array( $_GET['by'], array('posttitle', 'rating') ) ){
        $get_by = $_GET['by'];
    }
    $postratings_sortby = 'rating_timestamp';
    $postratings_sortby_text = 'дате';
    if($get_by == 'rating'){
        $postratings_sortby = 'rating_rating';
        $postratings_sortby_text = 'рейтингу';
    }
    else if($get_by == 'posttitle'){
        $postratings_sortby = 'rating_posttitle';
        $postratings_sortby_text = 'заголовку записи';
    }

    $postratings_filterrating = isset($_GET['rating']) ? intval($_GET['rating']) : 0;
    $postratings_where = '';
    if ( !empty($postratings_filterrating) ){
        $postratings_where = $wpdb->prepare(" AND rating_rating = %d", $postratings_filterrating);
    }


    $total_ratings = ppr_count_ratings($postratings_where);

    $rclnavi = new Rcl_PageNavi('page_tab_rayt',$total_ratings,array('in_page'=>$postratings_log_perpage));

    $datas = $wpdb->get_results($wpdb->prepare("
                                            SELECT rating_postid, rating_posttitle, rating_rating, rating_timestamp
                                            FROM $wpdb->ratings
                                            WHERE rating_userid = $user_LK $postratings_where
                                            ORDER BY $postratings_sortby $postratings_sortorder
                                            LIMIT %d, %d", $rclnavi->offset, $postratings_log_perpage), ARRAY_A);

    if($datas){ // есть данные
        if( isset($_GET['order']) ){ // нажали кнопку фильтра. Пагинация будет работать. Но она не будет ajax
            add_filter('rcl_page_link_attributes','ppr_add_page_link_attributes',10);
        }
        $out = '<div id="ppr_rating_content">';
            $out .= ppr_head_blk($total_ratings, $postratings_sortby_text);
            $out .= ppr_rating_blk($datas, $five_or_ten);

            // это запрос с фильтра или общее колво оценок > чем оценок на страницу - выведем пагинацию
            if( isset($_GET['order']) || $total_ratings > $postratings_log_perpage){
                $out .= $rclnavi->pagenavi();
                // фильтр будет выводится только если есть пагинация или фильтр
                $out .= ppr_footer_filter($five_or_ten,$postratings_filterrating,$postratings_sortby,$postratings_sortorder,$postratings_log_perpage);
            }
        $out .= '</div>';
    } else {
        $out = '<div class="ppr_not_data">Не оценил ни одной записи</div>';
    }

    return $out;
}


// кол-во рейтинга
function ppr_count_ratings($postratings_where){
    global $wpdb, $user_LK;

    $total = $wpdb->get_var("SELECT COUNT(rating_id) FROM $wpdb->ratings WHERE rating_userid = $user_LK $postratings_where");

    return $total;
}




// верхняя часть
function ppr_head_blk($total_ratings, $postratings_sortby_text){
    $out = '<div class="ppr_stats">';
        $out .= '<span>Оценок: '.$total_ratings.'</span>';
        $out .= '<span>Сортировка по: '.$postratings_sortby_text.'</span>';
    $out .= '</div>';

    $out .= '<div class="ppr_header_table">';
        $out .= '<span>Рейтинг</span>';
        $out .= '<span>Заголовок</span>';
        $out .= '<span>Дата и время</span>';
    $out .= '</div>';

    return $out;
}

// средняя. контент рейтинга
function ppr_rating_blk($datas, $five_or_ten){
    $months = array('Января','Февраля','Марта','Апреля','Мая','Июня','Июля','Августа','Сентября','Октября','Ноября','Декабря');
    $ratings_image = get_option('postratings_image');

    $out = '<div id="ppr_table">';
        foreach($datas as $data){
            $rating = intval($data['rating_rating']);
            $postid = intval($data['rating_postid']);

            $posttitle = stripslashes($data['rating_posttitle']);
            if(!$posttitle) $posttitle = '<small><del>Нет заголовка</del></small>';

            $date = $data['rating_timestamp'];
            $postratings_date = '<div class="ppr_ymd">'. date('j', $date) . '&nbsp;' . $months[date('m', $date)-1] . '&nbsp;' . date('Y', $date).'</div>';
            $postratings_date .= '<small>' . date('H:i', $date).'</small>';

            $out .= '<div class="ppr_item">';
                $out .= '<span class="ppr_star ppr_star_'.$five_or_ten.'">';
                    for($j=1; $j <= $five_or_ten; $j++) {
                        if($j <= $rating) {
                            $out .= '<img src="'.plugins_url('wp-postratings/images/'.$ratings_image.'/rating_on.'.RATINGS_IMG_EXT).'" alt="" class="ppr_images" />';
                        } else {
                            $out .= '<img src="'.plugins_url('wp-postratings/images/'.$ratings_image.'/rating_off.'.RATINGS_IMG_EXT).'" alt="" class="ppr_images" />';
                        }
                    }
                $out .= '</span>';
                $out .= '<span class="ppr_title"><a title="Перейти" href="/?p='.$postid.'">'.$posttitle.'</a></span>';
                $out .= '<span class="ppr_date">'.$postratings_date.'</span>';
            $out .= '</div>';
        }
    $out .= '</div>';

    return $out;
}



// нижняя часть. фильтр
function ppr_footer_filter($five_or_ten,$postratings_filterrating,$postratings_sortby,$postratings_sortorder,$postratings_log_perpage){
    global $user_LK;
    $lk_url = rcl_format_url(get_author_posts_url($user_LK),'tab_rayt');


    $out = '<div class="ppr_title_filter">Фильтр рейтинга:</div>';
    $out .= '<form action="'.$lk_url.'" method="get">';
        $out .= '<div class="ppr_selects">';
            $out .= '<span>Оценка:</span>';
            $out .= ppr_select_rating($five_or_ten, $postratings_filterrating);
            $out .= '<span>Сортировка:</span>';
            $out .= ppr_select_by($postratings_sortby);
            $out .= ppr_select_order($postratings_sortorder);
            $out .= ppr_select_in_page($postratings_log_perpage);
        $out .= '</div>';

        $out .= '<div style="text-align:right;"><input type="submit" value="Применить" class="recall-button"/></div>';
        $out .= '<input type="hidden" name="tab" value="tab_rayt" />';

        if( rcl_get_option('view_user_lk_rcl') ==1 ){ // если вывод через шорткод кабинета
            $out .= '<input type="hidden" name="'.rcl_get_option('link_user_lk_rcl','user').'" value="'.$user_LK.'">';
        }

    $out .= '</form>';

    if( isset( $_GET['order'] ) ){ // была фильтрация
        $out .= '<div class="ppr_filter_reset""><a href="'.$lk_url.'"  class="recall-button" style=""><span>Сбросить фильтр</span></a></div>';
    }

    return '<div class="ppr_filter">'.$out.'</div>';
}

// селекты сортировки по оценкам 1 до 5-ти, или 1 до 10-ти
function ppr_select_rating($five_or_ten, $postratings_filterrating){
    $out = '<select name="rating">';
        $out .= '<option value="">Все</option>';
        for ($num = 1; $num <= $five_or_ten; $num++){
            $out .= '<option value="'.$num.'"' . selected($postratings_filterrating, $num, false) . '>'.$num.'</option>';
        }
    $out .= '</select>';

    return $out;
}

// селекты сортировки по типу
function ppr_select_by($postratings_sortby){
    $out = '<select name="by">';
        $out .= '<option value="rating"' . selected($postratings_sortby, 'rating_rating', false) . '>По рейтингу</option>';
        $out .= '<option value="posttitle"' . selected($postratings_sortby, 'rating_posttitle', false) . '>По заголовку</option>';
        $out .= '<option value="date"' . selected($postratings_sortby, 'rating_timestamp', false) . '>По дате</option>';
    $out .= '</select>';

    return $out;
}

// селекты сортировки по направлению
function ppr_select_order($postratings_sortorder){
    $out = '<select name="order">';
        $out .= '<option value="desc"' . selected($postratings_sortorder, 'desc', false) . '>По убыванию</option>';
        $out .= '<option value="asc"' . selected($postratings_sortorder, 'asc', false) . '>По возрастанию</option>';
    $out .= '</select>';

    return $out;
}

// селекты вывода кол-ва по страницам
function ppr_select_in_page($postratings_log_perpage){
    $out = '<select name="in_page">';
        for($i=10; $i <= 100; $i+=10){
            $out .= '<option value="'.$i.'"' . selected($postratings_log_perpage, $i, false) . '>'.$i.' на страницу</option>';
        }
    $out .= '</select>';

    return $out;
}











