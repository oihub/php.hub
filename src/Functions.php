<?php

if (!function_exists('dd')) {
    function dd($expression)
    {
        echo '<pre>';
        print_r($expression);
        echo '</pre>';
        exit;
    }
}

if (!function_exists('dr')) {
    function dr($expression)
    {
        $response = '';
        $map = unserialize($expression);
        foreach ($map as $key => $value) {
            $response .= $key . ': ' . $value . "<br/>";
        }

        echo '<pre>';
        print_r($response);
        echo '</pre>';
        exit;
    }
}
