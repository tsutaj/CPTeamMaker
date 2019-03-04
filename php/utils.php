<?php

function swap(&$x, &$y) {
    $tmp = $y;
    $y = $x;
    $x = $tmp;
}

// 乱数の float 版
function random_float($min=0, $max=1, $mul=1000000) {
    if($min > $max) return false;
    return random_int($min*$mul, $max*$mul) / $mul;
}

// レートに対応するカラーコードを出す
function getColorCode($rating) {
    if($rating ==   0) return "#000000";
    if($rating <  400) return "#808080";
    if($rating <  800) return "#804000";
    if($rating < 1200) return "#008000";
    if($rating < 1600) return "#00C0C0";
    if($rating < 2000) return "#0000FF";
    if($rating < 2400) return "#C0C000";
    if($rating < 2800) return "#FF8000";
    return "#FF0000";
}

// デバッグ出力に使う
function var_dump_echo($var) {
    echo("<pre>");
    var_dump($var);
    echo("</pre>");
}

?>