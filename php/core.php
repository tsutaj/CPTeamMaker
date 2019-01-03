<?php

require_once "./user_class.php";
require_once "./utils.php";

// この辺は後でパラメータを受け取るように変えたいね
const NUM_OF_TEAM_MEMBER = 3;
const NUM_OF_ANNEALING_STEP = 100 * 100 * 2;
const START_TEMP = 1000000000.0;
const END_TEMP = 1000.0;
const POINT_DIV = 1000.0;

// 所属なし (かぶってもよい文字列は？)
const NONE_AFFIL = "";

// 分散を計算
function calculateVariance($sum_square, $sum_linear, $num_of_teams) {
    $lhs = $sum_square / $num_of_teams;
    $rhs = ($sum_linear / $num_of_teams) ** 2;
    return $lhs - $rhs;
}

// 点数を計算する ((分散) * (かぶった数 + 1))
// (二乗和, 線形和, かぶった数, チーム数) が必要
function calculatePoint($sum_square, $sum_linear, $sum_of_dbl, $num_of_teams) {
    $variance = calculateVariance($sum_square, $sum_linear, $num_of_teams);
    return $variance * ($sum_of_dbl + 1);
}

// チームの評価指標 (二乗和平均, かぶった数を返す)
// 内部でソートするので参照にするとこわれる、注意
// 点数のキーになるのは二乗和平均
function evaluateTeam($team) {
    $res_square = 0;
    $num_of_members = count($team);

    foreach($team as $member) {
        $res_square += $member->rating * $member->rating;
    }

    usort($team, "cmpUserAffil");
    $num_of_dbl = 0; $last_affil = false;
    for($i=0; $i<$num_of_members; $i++) {
        if($team[$i]->affiliation == NONE_AFFIL) continue;
        if($last_affil == $team[$i]->affiliation) $num_of_dbl++;
        $last_affil = $team[$i]->affiliation;
    }
    // 平均を取るのと、値が大きくなりがちなので PONIT_DIV で割る
    $res_square /= $num_of_members;
    $res_square /= POINT_DIV;
    return array($res_square, $num_of_dbl);
}

// 全体の評価指標
// (チーム得点二乗和, 線形和, かぶった数, 二乗配列, 線形配列, かぶった数配列) を返す
// 内容は特に変えないので参照で良いはず
function evaluateWhole(&$teams) {
    $sum_linear = $sum_square = $sum_of_dbl = 0;
    $vec_linear = $vec_square = $vec_of_dbl = array();

    foreach($teams as &$team) {
        list($res_point, $num_of_dbl) = evaluateTeam($team);
        $sum_linear += $res_point;
        $sum_square += $res_point * $res_point;
        $sum_of_dbl += $num_of_dbl;
        
        array_push($vec_linear, $res_point);
        array_push($vec_square, $res_point * $res_point);
        array_push($vec_of_dbl, $num_of_dbl);
    }
    return array($sum_square, $sum_linear, $sum_of_dbl, $vec_square, $vec_linear, $vec_of_dbl);
}

// ユーザーの構造体配列から最良のチーム分けを得る
function getAssignments($users) {
    $num_of_users = count($users);
    if($num_of_users == 0) return array();
    
    $div = intdiv($num_of_users, NUM_OF_TEAM_MEMBER);
    $mod = $num_of_users % NUM_OF_TEAM_MEMBER;

    $current_idx = 0;
    $initial_teams = array();
    $idx_row = array(); $idx_col = array();

    // 余りが商を上回る場合はもう 1 チーム作る
    if($div < $mod) {
        for($i=0; $i<=$div; $i++) {
            array_push($initial_teams, array());
            $lim = ($i < $div ? NUM_OF_TEAM_MEMBER : $mod);
            for($j=0; $j<$lim; $j++) {
                array_push($idx_row, $i);
                array_push($idx_col, $j);
                array_push($initial_teams[$i], $users[$current_idx++]);
            }
        }
    }
    // 上回らない場合は既存のチームに 1 人追加する
    else {
        for($i=0; $i<$div; $i++) {
            array_push($initial_teams, array());
            for($j=0; $j<NUM_OF_TEAM_MEMBER + ($i < $mod); $j++) {
                array_push($idx_row, $i);
                array_push($idx_col, $j);
                array_push($initial_teams[$i], $users[$current_idx++]);
            }
        }
    }

    // 最初の割当てに関する得点計算 (チーム数は不変)
    $num_of_teams = count($initial_teams);
    $current_teams = $initial_teams;
    $best_teams = $initial_teams;

    // (チーム得点二乗和, 線形和, かぶった数, 二乗配列, 線形配列, かぶった数配列) を返す
    list($sum_square, $sum_linear, $sum_of_dbl, $vec_square, $vec_linear, $vec_of_dbl) = evaluateWhole($current_teams);
    
    // 初期スコアを計算 (これ以降は全部差分計算しよう)
    // (二乗和, 線形和, かぶった数, チーム数) が必要
    $current_score = calculatePoint($sum_square, $sum_linear, $sum_of_dbl, $num_of_teams);
    $best_score = $current_score;

    for($step=0; $step<NUM_OF_ANNEALING_STEP; $step++) {
        // 異なるチーム間でメンバーを入れ替え
        $u = random_int(0, $num_of_users - 1);
        $v = random_int(0, $num_of_users - 1);
        if($idx_row[$u] == $idx_row[$v]) continue;

        // 現在の割当を参照渡し (結局交換しないなら直すこと！！)
        $next_teams = &$current_teams;
        swap($next_teams[$idx_row[$u]][$idx_col[$u]],
             $next_teams[$idx_row[$v]][$idx_col[$v]]);
        
        // 差分計算をがんばる (二乗和平均とかぶった数を得る)
        list($next_score_u, $next_dbl_u) = evaluateTeam($next_teams[$idx_row[$u]]);
        list($next_score_v, $next_dbl_v) = evaluateTeam($next_teams[$idx_row[$v]]);

        $lhs_square = $vec_square[$idx_row[$u]] + $vec_square[$idx_row[$v]];
        $lhs_linear = $vec_linear[$idx_row[$u]] + $vec_linear[$idx_row[$v]];
        $lhs_dbl = $vec_of_dbl[$idx_row[$u]] + $vec_of_dbl[$idx_row[$v]];
        $rhs_square = $next_score_u**2 + $next_score_v**2;
        $rhs_linear = $next_score_u + $next_score_v;
        $rhs_dbl = $next_dbl_u + $next_dbl_v;
        
        // 差分 (古いのを引いて新しいのを足す)
        $delta_sum_square = $rhs_square - $lhs_square;
        $delta_sum_linear = $rhs_linear - $rhs_linear;
        $delta_sum_of_dbl = $rhs_dbl - $lhs_dbl;
        
        $next_sum_square = $sum_square + $delta_sum_square;
        $next_sum_linear = $sum_linear + $delta_sum_linear;
        $next_sum_of_dbl = $sum_of_dbl + $delta_sum_of_dbl;
        
        $next_score = calculatePoint($next_sum_square, $next_sum_linear, $next_sum_of_dbl, $num_of_teams);
        
        $temp = START_TEMP + (END_TEMP - START_TEMP) * $step / NUM_OF_ANNEALING_STEP;
        $prob = exp(($current_score - $next_score) / $temp);
        $accept = $prob > random_float();

        if($accept) {
            // $current_teams = $next_teams; 参照にしたからいらなそう
            $current_score = $next_score;

            // 合計や配列の情報も更新しないとア
            $vec_square[$idx_row[$u]] = $next_score_u**2;
            $vec_square[$idx_row[$v]] = $next_score_v**2;
            $vec_linear[$idx_row[$u]] = $next_score_u;
            $vec_linear[$idx_row[$v]] = $next_score_v;
            $vec_of_dbl[$idx_row[$u]] = $next_dbl_u;
            $vec_of_dbl[$idx_row[$v]] = $next_dbl_v;
            $sum_square = $next_sum_square;
            $sum_linear = $next_sum_linear;
            $sum_of_dbl = $next_sum_of_dbl;

            if($next_score < $best_score) {
                $best_teams = $next_teams;
                $best_score = $next_score;                
            }
        }
        else {
            // 結局交換しないので
            swap($next_teams[$idx_row[$u]][$idx_col[$u]],
                 $next_teams[$idx_row[$v]][$idx_col[$v]]);         
        }
    }

    // 各チームをソート
    foreach($best_teams as &$team) {
        usort($team, "cmpUserInfo");
    }
    return $best_teams;
}

?>