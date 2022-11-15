<?php

    class api {
        public $config = [];

        function __construct($token, $group_id, $sub, $like, $com, $api, $img, $output, $font) {
            $this->config['settings'] = [
                'token' => $token,
                'group_id' => $group_id,
                'font' => $font,
                'api' => $api,
                'output' => BASEPATH . 'header/' . $output,
                'img' => BASEPATH . 'header/' . $img,
                'rounding' => true,
            ];

            $this->config['sub']['active'] = $sub;
            $this->config['like']['active'] = $like;
            $this->config['com']['active'] = $com;

            /* ----------------------- ПОСЛЕДНИЙ ПОДПИСАВШИЙСЯ ----------------------- */
            if($this->config['sub']['active']) {
                $this->config['sub'] += [
                    'fontsize' => 16,             // Размер шрифта

                    'fontcolor' => '255,255,255',             // Цвет текста

                    'width' => 97,             // Ширина аватарки
                    'height' => 97,             // Высота аватарки

                    'title_pixel_x' => 720,             // Координаты Заголовка по оси X
                    'title_pixel_y' => 138,             // Координаты Заголовка по оси Y

                    'p_pixel_x' => 729,              // Координаты аватарки по оси Х
                    'p_pixel_y' => 152,             // Координаты аватарки по оси Y

                    't_pixel_x' => 725,             // Координаты имени и фамилии по оси Х
                    't_pixel_y' => 275,             // Координаты имени и фамилии по оси Y
                ];
            }

            /* ------------------------ ТОП ПО КОЛ-ВУ ЛАЙКОВ ------------------------ */
            if($this->config['like']['active']) {
                $this->config['like'] += [
                    'fontsize' => 20,             // Размер шрифта
                    'fontcolor' => '255,255,255',             // Цвет текста
                    'width' => 137,             // Ширина аватарки
                    'height' => 137,             // Высота аватарки
                    'p_pixel_x' => 229,              // Координаты аватарки по оси Х
                    'p_pixel_y' => 66,             // Координаты аватарки по оси Y
                    't_pixel_x' => 295,             // Координаты имени и фамилии по оси Х
                    't_pixel_y' => 235,             // Координаты имени и фамилии по оси Y
                ];
            }

            /* ----------------------- ТОП ПО КОЛ-ВУ КОММЕНТОВ ----------------------- */
            if($this->config['com']['active']) {
                $this->config['com'] += [
                    'fontsize' => 20,             // Размер шрифта
                    'fontcolor' => '255,255,255',             // Цвет текста
                    'width' => 137,             // Ширина аватарки
                    'height' => 137,             // Высота аватарки
                    'p_pixel_x' => 416,              // Координаты аватарки по оси Х
                    'p_pixel_y' => 66,             // Координаты аватарки по оси Y
                    't_pixel_x' => 485,             // Координаты имени и фамилии по оси Х
                    't_pixel_y' => 235,             // Координаты имени и фамилии по оси Y
                ];
            }
        }


        function DownloadImages($url, $filename) {
            $ch = curl_init($url);
            $fp = fopen($filename, 'wb');
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_exec($ch);
            curl_close($ch);
            fclose($fp);
        }

        function getPOST($url, $post) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url); //урл сайта к которому обращаемся
            curl_setopt($ch, CURLOPT_HEADER, false); //выводим заголовки
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //теперь curl вернет нам ответ, а не выведет
            curl_setopt($ch, CURLOPT_POST, true); //передача данных методом POST
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/51.0.2704.106 Safari/537.36');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post); //тут переменные которые будут переданы методом POST
            $result = curl_exec($ch);
            curl_close($ch);
            return $result;

        }

        function getApiMethod($method_name, $params) {
            // Сделаем проверки на токен и версию апи, если их не указали, добавим.
            if(!array_key_exists('access_token', $params) && !is_null($this->config['settings']['token'])) {
                $params['access_token'] = $this->config['settings']['token'];
            }

            if(!array_key_exists('v', $params) && !is_null($this->config['settings']['api'])) {
                $params['v'] = $this->config['settings']['api'];
            }
            // Сортируем массив по ключам
            ksort($params);
            // Отправим запрос
            return ($this->getPOST('https://api.vk.com/method/' . $method_name, $params));
        }

    }