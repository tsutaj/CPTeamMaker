<?php

require_once "./user_class.php";

session_start();

// 構造体配列を JSON 用配列に変換
// dim1: チーム, dim2: メンバー, dim3: メンバーに関する情報
function transformJSONArray($array) {
    $res = array();
    foreach($array as $team) {
        $team_array = array();
        foreach($team as $user) {
            $user_array = array();
            $user_array['handle'] = $user->handle;
            $user_array['user_name'] = $user->user_name;
            $user_array['rating'] = $user->rating;
            $user_array['affiliation'] = $user->affiliation;

            array_push($team_array, $user_array);
        }
        array_push($res, $team_array);
    };

    $json = json_encode($res);
    return $json;
}

if(isset($_SESSION['final_assignment'])){
    // 念のために全ての変数に対してエスケープ処理
    foreach($_SESSION['final_assignment'] as &$team) {
        foreach($team as &$user) $user->escape();
    }
    
    $assignment = $_SESSION['final_assignment'];
    $json = transformJSONArray($assignment);

    $filename = "export";
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename='.$filename.'.json');
    echo $json;
}

?>