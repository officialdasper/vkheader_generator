<?php
    define('BASEPATH', str_replace('\\', '/', dirname(__FILE__)) . '/');

    require_once("./lib/Generator.php");

    header('Content-type: text/html; charset=utf-8');

    $api = new HeaderGenerator(
        "vk1.a.cfslPmzg04W2p3iLJzVU5Law9l3fkWN8AzxROuTVInE7y3BNWzpfR_VO-AGpXoMSKzPxFPdVCO1vZrpVjGw_g6zVr8RqgVRHj0_XZmYANqhHbrJoKKFtAULO7u-yvo3n-ulaDeP3o3LsEEev6vKEt8yybU3zrULjVyC8ncEtVXiiPg5OUK6Ouvso6XTZ9htb3yWCZCORSz7njozpTcxLeg",
        "217094387",
        true,
        false,
        false,
        "banner_vk.png");

