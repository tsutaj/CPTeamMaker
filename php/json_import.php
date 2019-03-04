<?php


// 他のサイトでインラインフレーム表示を禁止する（クリックジャッキング対策）
header('X-FRAME-OPTIONS: SAMEORIGIN');

// json のデータを元に、各ユーザーに対して過去のチーム分け結果をマップする
// [handle] => [file_id] + '/' + [team_id] みたいなものの配列をもたせる
function getPastAssignments($json_files) {
    // なにもない
    if(!isset($json_files["tmp_name"])) {
        return array();
    }
    
    $n = count($json_files["tmp_name"]);

    $user_past_assignments = array();
    for($i=0; $i<$n; $i++) {
        if(is_uploaded_file($json_files["tmp_name"][$i])) {
            // JSON が SJIS だとうくちゃん
            // ref: https://www.marineroad.com/staff-blog/12831.html
            $unknown_encoding_data = file_get_contents($json_files["tmp_name"][$i]);
            if($unknown_encoding_data === false) continue;
            
            $utf8_data = mb_convert_encoding($unknown_encoding_data, "UTF-8", "auto");
            $json_array = json_decode($utf8_data, true);
            
            $teams = count($json_array);
            for($j=0; $j<$teams; $j++) {
                $members = count($json_array[$j]);
                $assignment_id = "" . ($i+1) . "/" . ($j+1);
                for($k=0; $k<$members; $k++) {
                    $user = &$json_array[$j][$k];
                    if(!array_key_exists($user['handle'], $user_past_assignments)) {
                        $user_past_assignments[$user['handle']] = array();
                    }
                    array_push($user_past_assignments[$user['handle']], $assignment_id);
                }
            }
        }
    }

    return $user_past_assignments;
}

?>