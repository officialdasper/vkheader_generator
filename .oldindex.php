<?php

require_once('config.old.php');
require_once('/lib/api.php');

header('Content-type: text/html; charset=utf-8');

// Получим текущую дату
$date_today = date('Ymd');

if($show_top_like or $show_top_comments) {
    setLog('Получаю посты группы');
    // Получим посты со стены
    // больше 100 постов получать нет смысла, так как в вк ограничение
    // разрешено постить не больше 50 постов в сутки.
    $wall_get = getApiMethod('wall.get', array(
        'owner_id' => '-'.$group_id,
        'count' => '50'
    ));

    if($wall_get) {
        $wall_get = json_decode($wall_get, true);

        $countlike = array();
        $countcomments = array();
        
        foreach($wall_get['response']['items'] as $wall) {
            
            // Получим кол-во комментариев к посту
            $count = $wall['comments']['count'];
            $offset = 0;

            if($count > 0) { 
                // Получим все комментарии, так как их может быть больше 100.
                while($offset < $count){
                    setLog('Получаю кол-во комментариев к посту '.$wall['id']);
                    // Отправим запрос на получение комментариев
                    $comments_get = getApiMethod('wall.getComments', array(
                        'owner_id' => '-'.$group_id,
                        'post_id' => $wall['id'],
                        'need_likes' => '1',
                        'count' => '100',
                        'offset' => $offset
                    ));
                    
                    if($comments_get) {
                        $comments_get = json_decode($comments_get, true);

                        foreach($comments_get['response']['items'] as $comments) {
                            
                            if($date_today == date('Ymd', $comments['date'])) {
                                // В двух словах мы заносим данные в массив, суммируя их
                                if(!isset($countcomments[$comments['from_id']]) and !isset($countlike[$comments['from_id']])) {
                                    $countcomments[$comments['from_id']] = 1;
                                    $countlike[$comments['from_id']] = $comments['likes']['count'];   
                                } else {
                                    $countcomments[$comments['from_id']]++;
                                    $countlike[$comments['from_id']] += $comments['likes']['count'];
                                } 
                                var_dump($comments);
                            }
                            
                        }  
                    }

                    if($offset<$count) 
                        $offset = $offset + 100;
                }
            }

        }
    }
}



if($show_top_like) {
$day_like_top = 0;


if(count($countlike) > 0) {
    // Теперь найдем кто суммарно получил большее кол-во лайков к комментариям
    $value = max($countlike); 
    $day_like_top = array_search($value, $countlike);
    setLog('Получаю ID кто сумарно набрал большее кол-во лайков к комментариям '.$day_like_top);

    if($day_like_top > 0) {
        $user_top_like = getApiMethod('users.get', array(
            'user_ids' => $day_like_top,
            'fields' => 'photo_200'
        ));

        if($user_top_like) {
            $user_top_like = json_decode($user_top_like, true);

            $top_like_name = $user_top_like['response'][0]['first_name'];
            $top_like_lastname = $user_top_like['response'][0]['last_name'];
            $top_like_photo = $user_top_like['response'][0]['photo_200'];
            
            // Скачиваем фото
            if(!empty($top_like_name) && !empty($top_like_lastname) && !empty($top_like_photo)){
                DownloadImages($top_like_photo, 'header/top_likes.jpg');
            }
        }
    }
}

}


if($show_top_comments) {
    $day_comment_top = 0;




if(count($countcomments) > 0) {
        // Теперь найдем кто суммарно написал больше всех комментариев
        $value = max($countcomments); 
        $day_comment_top = array_search($value, $countcomments);
        setLog('Получаю ID кто суммарно написал больше всех комментариев '.$day_comment_top);

        if($day_comment_top > 0) {
            $user_top_comment = getApiMethod('users.get', array(
                'user_ids' => $day_comment_top,
                'fields' => 'photo_200'
            ));

            if($user_top_comment) {
                $user_top_comment = json_decode($user_top_comment, true);

                $top_comment_name = $user_top_comment['response'][0]['first_name'];
                $top_comment_lastname = $user_top_comment['response'][0]['last_name'];
                $top_comment_photo = $user_top_comment['response'][0]['photo_200'];
                
                // Скачиваем фото
                if(!empty($top_comment_name) && !empty($top_comment_lastname) && !empty($top_comment_photo)){
                    DownloadImages($top_comment_photo, 'header/top_comments.jpg');
                }
            }
        }
    }
}


if($show_last_subscribe) {
    // Теперь найдем последнего подписчика
    $last_subscribe = getApiMethod('groups.getMembers', array(
                'group_id' => $group_id,
                'sort' => 'time_desc',
                'count' => '1',
                'fields' => 'photo_200',
                'access_token' => $access_token
            ));

    if($last_subscribe) {
        $last_subscribe = json_decode($last_subscribe, true);

        $members_count = $last_subscribe['response']['count'];
        $last_subscribe_firstname = $last_subscribe['response']['items'][0]['first_name'];
        $last_subscribe_lastname = $last_subscribe['response']['items'][0]['last_name'];
        $last_subscribe_photo = $last_subscribe['response']['items'][0]['photo_200'];

        setLog('Получаю последнего вступившего в группу '.$last_subscribe_firstname.' '.$last_subscribe_lastname);
        
        // Скачиваем фото
        if(!empty($last_subscribe_firstname) && !empty($last_subscribe_lastname) && !empty($last_subscribe_photo)){
            DownloadImages($last_subscribe_photo, 'header/last_subscribe.jpg');
        }

    }
}
sleep(3);
if($show_weather){
    $ResultWeatherApi = getPOST('http://api.openweathermap.org/data/2.5/weather', array(
        'id' => $weather_city_id,
        'units' => 'metric',
        'CNT' => '1',
        'lang' => 'ru',
        'appid' => $weather_api_id
    ));

    $s = array(
        'id' => $weather_city_id,
        'units' => 'metric',
        'CNT' => '1',
        'lang' => 'ru',
        'appid' => $weather_api_id
    );
}

// -----------------------------------------------------------------------------
// --------------------------------- РИСОВАНИЕ ---------------------------------
// -----------------------------------------------------------------------------
setLog('Создание обложки');

$draw = new ImagickDraw(); 
$bg = new Imagick($image_bg);
$draw->setFont(BASEPATH."/font/".$font);
$draw->setTextAlignment(Imagick::ALIGN_CENTER);

// Последний подписчик
if($show_last_subscribe) {
    $file_name = BASEPATH.'header/last_subscribe.jpg';

    if(file_exists($file_name) && $show_last_subscribe) {
        $last_subscribe_photo = new Imagick($file_name);
        if($roundingOff==true) {
            RoundingOff($last_subscribe_photo, $last_subscribe_width,$last_subscribe_height);
        }

        $draw->setFontSize($last_subscribe_font_size);
        $draw->setFillColor("rgb(".$last_subscribe_font_color.")");

        $bg->compositeImage($last_subscribe_photo, Imagick::COMPOSITE_DEFAULT, $last_subscribe_photo_pixel_x, $last_subscribe_photo_pixel_y);
        $bg->annotateImage($draw, $last_subscribe_text_pixel_x, $last_subscribe_text_pixel_y, 0, mb_strtoupper($last_subscribe_firstname."\n".$last_subscribe_lastname, 'UTF-8'));
    }
}

// Топ по комментам
$file_name = BASEPATH.'header/top_comments.jpg';

if(file_exists($file_name) && $show_top_comments) {
    $top_comments_photo = new Imagick($file_name);
    if($roundingOff==true) {
        RoundingOff($top_comments_photo, $top_comments_width,$top_comments_height);
    }

    $draw->setFontSize($top_comments_font_size);
    $draw->setFillColor("rgb(".$top_comments_font_color.")");

    $bg->compositeImage($top_comments_photo, Imagick::COMPOSITE_DEFAULT, $top_comments_photo_pixel_x, $top_comments_photo_pixel_y);
    $bg->annotateImage($draw, $top_comments_text_pixel_x, $top_comments_text_pixel_y, 0, mb_strtoupper($top_comment_name."\n".$top_comment_lastname, 'UTF-8'));
}

// Топ по лайкам
$file_name = BASEPATH.'header/top_likes.jpg';

if(file_exists($file_name) && $show_top_like) {
    $top_like_photo = new Imagick($file_name);
    if($roundingOff==true) {
        RoundingOff($top_like_photo, $top_like_width,$top_like_height);
    }

    $draw->setFontSize($top_like_font_size);
    $draw->setFillColor("rgb(".$top_like_font_color.")");

    $bg->compositeImage($top_like_photo, Imagick::COMPOSITE_DEFAULT, $top_like_photo_pixel_x, $top_like_photo_pixel_y);
    $bg->annotateImage($draw, $top_like_text_pixel_x, $top_like_text_pixel_y, 0, mb_strtoupper($top_like_name."\n".$top_like_lastname, 'UTF-8'));
}

$bg->setImageFormat("png");
$bg->writeImage($output_header);

//echo '<img src="'.'header/output.png'.'">';

// -----------------------------------------------------------------------------
// --------------------------- ЗАГРУЗКА НА СЕРВЕР ------------------------------
// -----------------------------------------------------------------------------

// Получим адресс сервера







?>