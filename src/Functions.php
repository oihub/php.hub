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
