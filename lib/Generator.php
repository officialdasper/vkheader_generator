<?php

    require_once("api.php");

    class HeaderGenerator {

        protected $debug = true;

        public $sub = null;

        public function __construct($token = null, $group_id = null, $sub = true, $like = true, $com = true, $img = "bg.jpg", $output = "output.png", $font = "UniNeue-HeavyItalic.otf", $api = "5.131",) {
            $this->api = new api($token, $group_id, $sub, $like, $com, $api, $img, $output, $font);

            if($this->api->config['sub']['active']) $this->GetLastSub();

            $this->NewHeader();

        }

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


        /* Рисуем новую шапку */
        function NewHeader() {
            $this->setLog('Создание обложки');

            $draw = new ImagickDraw();
            $bg = new Imagick($this->api->config['settings']['img']);
            $draw->setFont(BASEPATH . "/font/" . $this->api->config['settings']['font']);
            $draw->setTextAlignment(Imagick::ALIGN_CENTER);

            // Последний подписчик
            if(file_exists(BASEPATH . 'header/last_subscribe.jpg') && $this->api->config['sub']['active']) {
                $sub_photo = new Imagick(BASEPATH . 'header/last_subscribe.jpg');
                if($this->api->config['settings']['rounding'] == true) {
                    $this->RoundingOff($sub_photo, $this->api->config['sub']['width'], $this->api->config['sub']['height']);
                }

                $draw->setFontSize($this->api->config['sub']['fontsize']);
                $draw->setFillColor("rgb(" . $this->api->config['sub']['fontcolor'] . ")");

                $bg->compositeImage($sub_photo, Imagick::COMPOSITE_DEFAULT, $this->api->config['sub']['p_pixel_x'], $this->api->config['sub']['p_pixel_y']);

                $bg->annotateImage($draw, $this->api->config['sub']['t_pixel_x'], $this->api->config['sub']['t_pixel_y']-135, 0, mb_strtoupper("Последний подписчик", 'UTF-8'));
                $bg->annotateImage($draw, $this->api->config['sub']['t_pixel_x'], $this->api->config['sub']['t_pixel_y'], 0, mb_strtoupper($this->sub['firstname'] . "\n" . $this->sub['lastname'], 'UTF-8'));

            }

            // Топ по комментам
            //            if(file_exists(BASEPATH.'header/top_comments.jpg') && $show_top_comments) {
            //                $top_comments_photo = new Imagick(BASEPATH.'header/top_comments.jpg');
            //                if($roundingOff==true) {
            //                    RoundingOff($top_comments_photo, $top_comments_width,$top_comments_height);
            //                }
            //
            //                $draw->setFontSize($top_comments_font_size);
            //                $draw->setFillColor("rgb(".$top_comments_font_color.")");
            //
            //                $bg->compositeImage($top_comments_photo, Imagick::COMPOSITE_DEFAULT, $top_comments_photo_pixel_x, $top_comments_photo_pixel_y);
            //                $bg->annotateImage($draw, $top_comments_text_pixel_x, $top_comments_text_pixel_y, 0, mb_strtoupper($top_comment_name."\n".$top_comment_lastname, 'UTF-8'));
            //            }
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

            $bg->setImageFormat("png");
            $bg->writeImage($this->api->config['settings']['output']);
            if($this->debug){
//                echo "<img src=\"{$this->api->config['settings']['output']}\">";
            echo "<img src=\"/header/output.png\">";
            }
        }

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

    }