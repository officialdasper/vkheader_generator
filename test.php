<?php
    define('BASEPATH', str_replace('\\', '/', dirname(__FILE__)) . '/');

    require_once("./lib/Generator.php");

    header('Content-type: text/html; charset=utf-8');
    // Получим текущую дату
    $date_today = date('Ymd');

    $api = new HeaderGenerator("vk1.a.j5RMjtWlCdywvwkd8YZ7T_yKxMa5fv54LAfZOIuIm9rHmgflMHiwLvlkz6xEzK97gqVFiuiYjOWZDl2DWcTg-ES9p26XM-Bkdt6xWil0j_cKTKRzvOCdEry5HAQOXuk2V0IaEa3FgEYLey2m-_gP8b49eEFukqSpHXZ0aVDJ0CVlCkRTADAAlOxwRaaKdfoI", "217094387", true, false, false, "banner_vk.png");




