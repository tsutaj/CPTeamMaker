<?php

require_once "./user_class.php";
require_once "./fetch_user_info.php";

// ユーザー構造体配列に変換
// $rating_key は 'rating' か 'highest' (default: 'rating')
function getUserArray($take_user, $team_id, $handle, $user_id, $affiliation, $past_assignments, $rating_key='rating') {
    // 全ての配列は同じであると仮定 (表の構造的に例外処理しなくてもいい気がしている)
    $len = count($user_id);

    $user_array = array();
    $error_array = array();

    // ID 被りの際は先頭要素のみ反映
    $user_id_set = array();
    
    for($i=0; $i<$len; $i++) {
        // ハンドルネームが空欄なら無視される
        if(empty($handle[$i])) continue;
        
        // そのユーザーを使わないようにしたい
        if(!isset($take_user[$i]) or $take_user[$i] != "on") continue;

        // デフォルトのユーザー情報 (レート -1)
        $user_info = getEmptyInfo($user_id[$i]);

        // AtCoder ID を全て小文字に直し、重複していれば無視
        $lower_case_user_id = mb_strtolower($user_id[$i]);
        if(!in_array($lower_case_user_id, $user_id_set)) {
            // API でレート取得 (取得に失敗したら無視)
            $user_info = getUserRating($user_id[$i]);
            if($lower_case_user_id !== "") {
                array_push($user_id_set, $lower_case_user_id);
            }
            else {
                // ID が空欄ならレート 0 扱いにする
                $user_info['rating'] = 0;
            }
        }

        $user_past_assignments = array();
        if(array_key_exists($handle[$i], $past_assignments)) {
            $user_past_assignments = $past_assignments[$handle[$i]];
        }
        
        $user = new UserInfo($handle[$i], $team_id[$i], $user_info['name'], $user_info['rating'], $affiliation[$i], $user_past_assignments);

        // レートが負なら、そのユーザーが存在しないことを表す
        if($user_info[$rating_key] < 0) {
            array_push($error_array, $user);
        }
        else {
            array_push($user_array, $user);
        }
    }

    return array($user_array, $error_array);
}

?>