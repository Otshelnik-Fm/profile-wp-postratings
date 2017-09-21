<?php

// запрет прямого обращения к файлу
if ( !defined('ABSPATH') ) exit;

class pprPostRatings {

    public $user_lk;                        // id-кабинета пользователя
    public $total_ratings;                  // счетчик кол-ва проставленного рейтинга
    public $five_or_ten;                    // 5-10-ти или бальный рейтинг
    public $per_page;                       // записей на страницу
    public $sort_asc_desc = 'DESC';         // направление сортировки
    public $get_by;
    public $sort_by = 'rating_timestamp';   // тип сортировки
    public $sort_by_text = 'дате';          // текст длдя шапки
    public $where_query;                    // параметры запроса
    public $filter_rating = 0;              // сортировка по кол-ву рейтинга


    function __construct($user_lk){
        $this->user_lk = $user_lk;

        $this->five_or_ten = rcl_get_option('nmbr_rating', 5);

        $this->per_page = isset($_GET['in_page']) ? intval($_GET['in_page']) : rcl_get_option('nmbr_per_page', 20);

        if ( isset($_GET['order']) && $_GET['order'] == 'asc' ){
            $this->sort_asc_desc = 'ASC';
        }

        if ( isset($_GET['by']) && in_array( $_GET['by'], array('posttitle', 'rating') ) ){ // белый список
            $this->get_by = $_GET['by'];
        }
        if($this->get_by == 'rating'){
            $this->sort_by = 'rating_rating';
            $this->sort_by_text = 'рейтингу';
        }
        else if($this->get_by == 'posttitle'){
            $this->sort_by = 'rating_posttitle';
            $this->sort_by_text = 'заголовку записи';
        }

        $this->filter_rating = isset($_GET['rating']) ? intval($_GET['rating']) : 0;
        if ( !empty($this->filter_rating) ){
            $this->where_query = ' AND rating_rating = ' . $this->filter_rating;
        }

        $this->total_ratings = $this->count_ratings();
    }


    // достанем рейтинг
    function get_rating(){
        global $wpdb;

        $rclnavi = new Rcl_PageNavi( 'page_tab_rayt', $this->total_ratings, array('in_page'=>$this->per_page) );

        $datas = $wpdb->get_results($wpdb->prepare("
                                                SELECT rating_postid, rating_posttitle, rating_rating, rating_timestamp
                                                FROM $wpdb->ratings
                                                WHERE rating_userid = $this->user_lk $this->where_query
                                                ORDER BY $this->sort_by $this->sort_asc_desc
                                                LIMIT %d, %d", $rclnavi->offset, $this->per_page), ARRAY_A);

        if($datas){ // есть данные
            if( isset($_GET['order']) ){ // нажали кнопку фильтра. Пагинация будет работать. Но она не будет ajax
                add_filter('rcl_page_link_attributes','ppr_add_page_link_attributes',10);
            }

            $out = '<div id="ppr_rating_content">';
                $out .= $this->head_block();
                $out .= $this->rating_block($datas);

                // это запрос с фильтра или общее колво оценок > чем оценок на страницу - выведем пагинацию
                if( isset($_GET['order']) || $this->total_ratings > $this->per_page){
                    $out .= $rclnavi->pagenavi();
                    // фильтр будет выводится только если есть пагинация или фильтр
                    $out .= $this->footer_filter();
                }
            $out .= '</div>';
        } else {
            $out = '<div class="ppr_not_data">Не оценил ни одной записи</div>';
        }

        return $out;
    }


    // кол-во рейтинга
    private function count_ratings(){
        global $wpdb;

        $total = $wpdb->get_var("SELECT COUNT(rating_id) FROM $wpdb->ratings WHERE rating_userid = $this->user_lk $this->where_query");

        return $total;
    }


    // верхняя часть
    private function head_block(){
        $out = '<div class="ppr_stats">';
            $out .= '<span>Оценок: '.$this->total_ratings.'</span>';
            $out .= '<span>Сортировка по: '.$this->sort_by_text.'</span>';
        $out .= '</div>';

        $out .= '<div class="ppr_header_table">';
            $out .= '<span>Рейтинг</span>';
            $out .= '<span>Заголовок</span>';
            $out .= '<span>Дата и время</span>';
        $out .= '</div>';

        return $out;
    }


    // средняя. контент рейтинга
    private function rating_block($datas){
        $months = array('Января','Февраля','Марта','Апреля','Мая','Июня','Июля','Августа','Сентября','Октября','Ноября','Декабря');
        $ratings_image = get_option('postratings_image');

        $out = '<div id="ppr_table">';
            foreach($datas as $data){
                $rating = intval($data['rating_rating']);
                $postid = intval($data['rating_postid']);

                $posttitle = stripslashes($data['rating_posttitle']);
                if(!$posttitle) $posttitle = '<small><del>Нет заголовка</del></small>';

                $date = $data['rating_timestamp'];
                $vote_date = '<div class="ppr_ymd">'.date('j', $date).'&nbsp;'.$months[date('m', $date)-1].'&nbsp;'.date('Y', $date).'</div>';
                $vote_date .= '<small>'.date('H:i', $date).'</small>';

                $out .= '<div class="ppr_item">';
                    $out .= '<span class="ppr_star ppr_star_'.$this->five_or_ten.'">';
                        for($j=1; $j <= $this->five_or_ten; $j++) {
                            if($j <= $rating) {
                                $out .= '<img src="'.plugins_url('wp-postratings/images/'.$ratings_image.'/rating_on.'.RATINGS_IMG_EXT).'" alt="" class="ppr_images" />';
                            } else {
                                $out .= '<img src="'.plugins_url('wp-postratings/images/'.$ratings_image.'/rating_off.'.RATINGS_IMG_EXT).'" alt="" class="ppr_images" />';
                            }
                        }
                    $out .= '</span>';
                    $out .= '<span class="ppr_title"><a title="Перейти" href="/?p='.$postid.'">'.$posttitle.'</a></span>';
                    $out .= '<span class="ppr_date">'.$vote_date.'</span>';
                $out .= '</div>';
            }
        $out .= '</div>';

        return $out;
    }


    // нижняя часть. фильтр
    private function footer_filter(){
        $lk_url = rcl_format_url(get_author_posts_url($this->user_lk),'tab_rayt');

        $out = '<div class="ppr_title_filter">Фильтр рейтинга:</div>';
        $out .= '<form action="'.$lk_url.'" method="get">';
            $out .= '<div class="ppr_selects">';
                $out .= '<span>Оценка:</span>';
                $out .= $this->select_rating();
                $out .= '<span>Сортировка:</span>';
                $out .= $this->select_by();
                $out .= $this->select_order();
                $out .= $this->select_in_page();
            $out .= '</div>';

            $out .= '<div style="text-align:right;"><input type="submit" value="Применить" class="recall-button"/></div>';
            $out .= '<input type="hidden" name="tab" value="tab_rayt" />';

            if( rcl_get_option('view_user_lk_rcl') ==1 ){ // если кабинет выводится через шорткод
                $out .= '<input type="hidden" name="'.rcl_get_option('link_user_lk_rcl','user').'" value="'.$this->user_lk.'">';
            }

        $out .= '</form>';

        if( isset($_GET['order']) ){ // была фильтрация
            $out .= '<div class="ppr_filter_reset""><a href="'.$lk_url.'" class="recall-button">Сбросить фильтр</a></div>';
        }

        return '<div class="ppr_filter">'.$out.'</div>';
    }


    // селекты сортировки по оценкам 1 до 5-ти, или 1 до 10-ти
    private function select_rating(){
        $out = '<select name="rating">';
            $out .= '<option value="">Все</option>';
            for ($num = 1; $num <= $this->five_or_ten; $num++){
                $out .= '<option value="'.$num.'"' . selected($this->filter_rating, $num, false) . '>'.$num.'</option>';
            }
        $out .= '</select>';

        return $out;
    }


    // селекты сортировки по типу
    private function select_by(){
        $out = '<select name="by">';
            $out .= '<option value="rating"' . selected($this->sort_by, 'rating_rating', false) . '>По рейтингу</option>';
            $out .= '<option value="posttitle"' . selected($this->sort_by, 'rating_posttitle', false) . '>По заголовку</option>';
            $out .= '<option value="date"' . selected($this->sort_by, 'rating_timestamp', false) . '>По дате</option>';
        $out .= '</select>';

        return $out;
    }


    // селекты сортировки по направлению
    private function select_order(){
        $out = '<select name="order">';
            $out .= '<option value="desc"' . selected($this->sort_asc_desc, 'DESC', false) . '>По убыванию</option>';
            $out .= '<option value="asc"' . selected($this->sort_asc_desc, 'ASC', false) . '>По возрастанию</option>';
        $out .= '</select>';

        return $out;
    }


    // селекты вывода кол-ва по страницам
    private function select_in_page(){
        $out = '<select name="in_page">';
            for($i=10; $i <= 100; $i+=10){
                $out .= '<option value="'.$i.'"' . selected($this->per_page, $i, false) . '>'.$i.' на страницу</option>';
            }
        $out .= '</select>';

        return $out;
    }

}
