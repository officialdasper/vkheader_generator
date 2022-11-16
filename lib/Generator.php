<?php

    require_once("api.php");

    class HeaderGenerator {

        protected $debug = true;

        public $sub = null;

        public function __construct($token = null, $group_id = null, $sub = true, $like = true, $com = true, $img = "bg.jpg", $output = "output.png", $font = "RobotoCondensed-Regular.ttf", $api = "5.131") {
            $this->api = new api($token, $group_id, $sub, $like, $com, $api, $img, $output, $font);

            if($this->api->config['sub']['active']) $this->GetLastSub();
            if($this->api->config['com']['active'] or $this->api->config['like']['active']) $this->GetCommentsOrLike();

            $this->NewHeader();
        }

        /* Логи и скругления */
        function RoundingOff($_imagick, $width, $height) {
            $_imagick->adaptiveResizeImage($width, $height, 100);
            $_imagick->setImageFormat('png');
            $_imagick->roundCornersImage(90, 90, 0, 0, 0);
        }
        function setLog($message) {
            $log_file_name = 'logs.txt';

            if(file_exists($log_file_name)) {
                $log = array_diff(explode("\r\n", file_get_contents($log_file_name)), ['']);
            }

            $log[] = date("m.d.Y-H:i:s") . ' | ' . $message;

            if(file_put_contents($log_file_name, implode("\r\n", $log))) return true; else return false;
        }


        /* VK Methods */


        /* Получить последнего вступившего */
        function GetLastSub() {
            try {
                $last_subscribe = $this->api->getApiMethod('groups.getMembers', [
                    'group_id' => $this->api->config['settings']['group_id'],
                    'sort' => 'time_desc',
                    'count' => '1',
                    'fields' => 'photo_200',
                    'access_token' => $this->api->config['settings']['token'],
                ]);

                $json = json_decode($last_subscribe, true);

                if(array_key_exists("error", $json)) {
                    throw new Exception("Error № {$json['error']['error_code']} | msg: {$json['error']['error_msg']}");
                }
            } catch(Exception $e) {
                echo $e->getMessage();
                die();
            }


            $members_count = $json['response']['count'];

            $this->sub['firstname'] = $json['response']['items'][0]['first_name'];
            $this->sub['lastname'] = $json['response']['items'][0]['last_name'];
            $this->sub['photo'] = $json['response']['items'][0]['photo_200'];

            $msg = "Получаю последнего вступившего в группу <b>{$this->sub['firstname']} {$this->sub['lastname']}</b>";
            $this->setLog(strip_tags($msg));
            // Скачиваем фото
            if(!empty($this->sub['firstname']) && !empty($this->sub['lastname']) && !empty($this->sub['photo'])) {
                $this->api->DownloadImages($this->sub['photo'], 'header/last_subscribe.jpg');
            }

            if($this->debug) echo $msg;
        }

        /* Получить коменты и лайки */
        function GetCommentsOrLike() {
            try {
                $this->setLog('Получаю посты группы');
                // Получим посты со стены
                // больше 100 постов получать нет смысла, так как в вк ограничение
                // разрешено постить не больше 50 постов в сутки.
                $wall_get = $this->api->getApiMethod('wall.get', [
                    'owner_id' => '-' . $this->api->config['settings']['group_id'],
                    'count' => '50',
                ]);
                $wall_get = json_decode($wall_get, true);
                if(array_key_exists("error", $wall_get)) {
                    throw new Exception("Error № {$wall_get['error']['error_code']} | msg: {$wall_get['error']['error_msg']}");
                }
            } catch(Exception $e) {
                echo $e->getMessage();
                die();
            }
            $count_like = [];
            $count_comments = [];

            foreach($wall_get['response']['items'] as $wall) {

                // Получим кол-во комментариев к посту
                $count = $wall['comments']['count'];
                $current_count = 0;
                $limit = 100;

                if($count) {
                    // Получим все комментарии, так как их может быть больше 100.
                    while(true){
                        try {
                            $this->setLog('Получаю кол-во комментариев к посту ' . $wall['id']);
                            // Получим посты со стены
                            // больше 100 постов получать нет смысла, так как в вк ограничение
                            // разрешено постить не больше 50 постов в сутки.
                            $comments_get = $this->api->getApiMethod('wall.getComments', [
                                'owner_id' => '-' . $this->api->config['settings']['group_id'],
                                'post_id' => $wall['id'],
                                'need_likes' => '1',
                                'count' => $limit,
                                'offset' => $current_count,
                            ]);
                            $comments_get = json_decode($comments_get, true);
                            if(array_key_exists("error", $comments_get)) {
                                throw new Exception("Error № {$comments_get['error']['error_code']} | msg: {$comments_get['error']['error_msg']}");
                            }
                        } catch(Exception $e) {
                            echo $e->getMessage();
                            die();
                        }

                        foreach($comments_get['response']['items'] as $comments) {
                            if(date('Ymd') == date('Ymd', $comments['date'])) {
                                // В двух словах мы заносим данные в массив, суммируя их
                                if(!isset($count_comments[$comments['from_id']]) and !isset($count_like[$comments['from_id']])) {
                                    $count_comments[$comments['from_id']] = 1;
                                    $count_like[$comments['from_id']] = $comments['likes']['count'];
                                } else {
                                    $count_comments[$comments['from_id']]++;
                                    $count_like[$comments['from_id']] += $comments['likes']['count'];
                                }
                                var_dump($comments);
                            }

                        }

                        if($current_count < $count)
                            $current_count =+ $limit;
                        else break;
                    }
                }

            }
        }

        /* Рисуем новую шапку */
        function NewHeader() {
            $this->setLog('Создание обложки');

            $draw = new ImagickDraw();
            $bg = new Imagick($this->api->config['settings']['img']);
            $bg->setCompressionQuality(100);
            $draw->setFont(BASEPATH . "/font/" . $this->api->config['settings']['font']);
            $draw->setTextAlignment(Imagick::ALIGN_CENTER);

            // Последний подписчик
            if(file_exists(BASEPATH . 'header/last_subscribe.jpg') && $this->api->config['sub']['active']) {
                $sub_photo = new Imagick(BASEPATH . 'header/last_subscribe.jpg');
                $shadow = new Imagick(BASEPATH . 'header/shadow.png');
                if($this->api->config['settings']['rounding']) {
                    $this->RoundingOff($sub_photo, $this->api->config['sub']['width'], $this->api->config['sub']['height']);
                    $this->RoundingOff($shadow, 109, 107);

                } else {
                    $sub_photo->adaptiveResizeImage($this->api->config['sub']['width'], $this->api->config['sub']['height']);
                    $shadow->adaptiveResizeImage(109, 107);
                }

                $draw->setFontSize($this->api->config['sub']['fontsize']);
                $draw->setFillColor("rgb({$this->api->config['sub']['fontcolor']})");


                $bg->compositeImage($shadow, Imagick::COMPOSITE_DEFAULT, $this->api->config['sub']['p_pixel_x'], $this->api->config['sub']['p_pixel_y']);

                $bg->compositeImage($sub_photo, Imagick::COMPOSITE_DEFAULT, $this->api->config['sub']['p_pixel_x'], $this->api->config['sub']['p_pixel_y']);

                $draw->setTextAlignment(\Imagick::ALIGN_LEFT);
                $bg->annotateImage($draw, $this->api->config['sub']['title_pixel_x'], $this->api->config['sub']['title_pixel_y'], 0, mb_strtoupper("last subscriber", 'UTF-8'));


                $bg->annotateImage($draw, $this->api->config['sub']['t_pixel_x'], $this->api->config['sub']['t_pixel_y'], 0, mb_strtoupper("{$this->sub['firstname']} {$this->sub['lastname']}", 'UTF-8'));
                $draw->setTextAlignment(\Imagick::ALIGN_CENTER);

            }

            //  Топ по комментам

            if(file_exists(BASEPATH . 'header/top_comments.jpg') && $this->api->config['com']['active']) {
                $com_photo = new Imagick(BASEPATH . 'header/top_comments.jpg');
                if($this->api->config['settings']['rounding']) {
                    $this->RoundingOff($com_photo, $this->api->config['com']['width'], $this->api->config['com']['height']);
                    $this->RoundingOff($shadow, 109, 107);

                } else {
                    $com_photo->adaptiveResizeImage($this->api->config['com']['width'], $this->api->config['com']['height']);
                    $shadow->adaptiveResizeImage(109, 107);
                }

                $draw->setFontSize($this->api->config['com']['fontsize']);
                $draw->setFillColor("rgb({$this->api->config['com']['fontcolor']})");

                $bg->compositeImage($com_photo, Imagick::COMPOSITE_DEFAULT, $this->api->config['com']['p_pixel_x'], $this->api->config['com']['p_pixel_y']);

                $bg->annotateImage($draw, $this->api->config['com']['t_pixel_x'], $this->api->config['com']['t_pixel_y'], 0, mb_strtoupper("{$this->com['firstname']} {$this->com['lastname']}", 'UTF-8'));
                $draw->setTextAlignment(\Imagick::ALIGN_CENTER);

            }
             //
            //            // Топ по лайкам
            //            if(file_exists(BASEPATH.'header/top_likes.jpg') && $show_top_like) {
            //                $top_like_photo = new Imagick(BASEPATH.'header/top_likes.jpg');
            //                if($roundingOff==true) {
            //                    RoundingOff($top_like_photo, $top_like_width,$top_like_height);
            //                }
            //
            //                $draw->setFontSize($top_like_font_size);
            //                $draw->setFillColor("rgb(".$top_like_font_color.")");
            //
            //                $bg->compositeImage($top_like_photo, Imagick::COMPOSITE_DEFAULT, $top_like_photo_pixel_x, $top_like_photo_pixel_y);
            //                $bg->annotateImage($draw, $top_like_text_pixel_x, $top_like_text_pixel_y, 0, mb_strtoupper($top_like_name."\n".$top_like_lastname, 'UTF-8'));
            //            }

            $bg->setImageFormat("jpg");
            $bg->writeImage($this->api->config['settings']['output']);
            $this->importVK();
        }

        /* Импортируем в вк */
        public function importVK() {
            $getUrl = $this->api->getApiMethod('photos.getOwnerCoverPhotoUploadServer', [
                'group_id' => $this->api->config['settings']['group_id'],

                'crop_x' => '198', // Координата X верхнего левого угла для обрезки изображения.
                'crop_y' => '0', // Координата Y верхнего левого угла для обрезки изображения.
                'crop_x2' => '1200', // Координата X нижнего правого угла для обрезки изображения.
                'crop_y2' => '400', // Координата Y нижнего правого угла для обрезки изображения.
            ]);
            $this->setLog('Получаю адресс сервера ' . $getUrl);


            if($getUrl) {
                $getUrl = json_decode($getUrl, true);

                $url = $getUrl['response']['upload_url'];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, ['photo' => new CURLFile($this->api->config['settings']['output'], 'image/jpeg', 'image0')]);
                $upload = curl_exec($ch);
                curl_close($ch);

                if($upload) {
                    $upload = json_decode($upload, true);
                    $getUrl = $this->api->getApiMethod('photos.saveOwnerCoverPhoto', [
                        'hash' => $upload['hash'],
                        'photo' => $upload['photo'],
                    ]);

                    $this->setLog('Загружаю обложку ' . $getUrl);

                    if(stripos($getUrl, 'response":{"images":[{')) {
                        print_r('Успешно загрузили обложку<br>');
                        //                        echo '<p>*** Больше всех сегодня лайков набрал: <a href ="https://vk.com/id'.$day_like_top.'" target="_blank" >'.$top_like_name.' '.$top_like_lastname.' - '.$countlike[$day_like_top].'</a> шт.</p></br>';
                        //                        echo '<p>*** Больше всех сегодня комментариев написал: <a href ="https://vk.com/id'.$day_comment_top.'" target="_blank" >'.$top_comment_name.' '.$top_comment_lastname.' - '.$countcomments[$day_comment_top].'</a> шт.</p></br>';
                        //                        echo '<p>*** Последний подписчик <a href ="https://vk.com/id'.$day_like_top.'" target="_blank" >'.$last_subscribe_firstname.' '.$last_subscribe_lastname.'</a></p></br>';
                        echo '<br><img src="' . 'header/output.png' . '">';
                        $this->setLog('Загружаю обложку в ' . $this->api->config['settings']['group_id']);
                    } else {
                        print_r('Ошибка при загрузке обложки ' . $getUrl);
                        $this->setLog('Ошибка при загрузке обложки ' . $getUrl);
                    }

                }

            }
        }




    }