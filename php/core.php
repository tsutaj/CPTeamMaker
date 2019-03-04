<?php

require_once "./user_class.php";
require_once "./utils.php";

// この辺は後でパラメータを受け取るように変えたいね
const NUM_OF_TEAM_MEMBER = 3;

const NUM_OF_ANNEALING_STEP = 300000; // for release
// const NUM_OF_ANNEALING_STEP = 10000; // for debug

const START_TEMP = 70.0;
// const END_TEMP = 0.0;
const TEMP_COEFF = 0.90;
const POINT_DIV = 1000.0;
const DOUBLE_WEIGHT = 5.0;

// 所属なし (かぶってもよい文字列は？)
const NONE_AFFIL = "";

const MEMBER_EMPTY = 101;
const INVALID_TEAM_ID_ASSIGNMENT = 102;

// 分散を計算
function calculateVariance($sum_square, $sum_linear, $num_of_teams) {
    $lhs = 1.0 * $sum_square / $num_of_teams;
    $rhs = 1.0 * ($sum_linear / $num_of_teams) ** 2;
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
    $res_square = 0.0;
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

    for($i=0; $i<$num_of_members; $i++) {
        for($j=$i+1; $j<$num_of_members; $j++) {
            $p_size_i = count($team[$i]->past_assignments);
            $p_size_j = count($team[$j]->past_assignments);

            $has_dbl_past_affil = false;
            for($x=0; $x<$p_size_i; $x++) {
                for($y=0; $y<$p_size_j; $y++) {
                    if($team[$i]->past_assignments[$x] == $team[$j]->past_assignments[$y]) {
                        // すぐ抜ける
                        $has_dbl_past_affil = true;
                        $x = $p_size_i;
                        $y = $p_size_j;
                    }
                }
            }

            if($has_dbl_past_affil) $num_of_dbl++;
        }
    }

    // 平均を取るのと、値が大きくなりがちなので PONIT_DIV で割る
    $res_square /= 1.0 * $num_of_members;
    $res_square /= POINT_DIV;
    return array($res_square, $num_of_dbl);
}

// 全体の評価指標
// (チーム得点二乗和, 線形和, かぶった数, 二乗配列, 線形配列, かぶった数配列) を返す
// 内容は特に変えないので参照で良いはず
function evaluateWhole(&$teams) {
    $sum_linear = $sum_square = $sum_of_dbl = 0.0;
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
    if($num_of_users == 0) return MEMBER_EMPTY;
    
    $div = intdiv($num_of_users, NUM_OF_TEAM_MEMBER);
    $mod = $num_of_users % NUM_OF_TEAM_MEMBER;

    $initial_teams = array();
    $idx_row = array(); $idx_col = array();

    // 部分的にチームが決定している人と、どこでもいい人を別々に管理
    $partially_determined_users = array();
    $free_users = array();
    $free_idx = 0;

    // 各 team_id に対して、その id を持っている人の数を数える
    $cnt_team_id = array();
    // 各 team id に対して、id を整数に振り直す
    $num_team_id = array();
    $idx_team_id = 0;
    foreach($users as $user) {
        if($user->team_id === "") continue;
        if(array_key_exists($user->team_id, $cnt_team_id)) {
            $cnt_team_id[$user->team_id]++;
        }
        else {
            $cnt_team_id[$user->team_id] = 1;
        }
    }

    // その team_id を持つ人が 1 人しかいないならば実質 free_users
    // そうでなければ partially_determined_users
    foreach($users as &$user) {
        if($user->team_id === "") {
            array_push($free_users, $user);
        }
        else if($cnt_team_id[$user->team_id] == 1) {
            $user->team_id = "";
            array_push($free_users, $user);
        }
        else {
            if(array_key_exists($user->team_id, $num_team_id)) {
                $idx = $num_team_id[$user->team_id];
                array_push($partially_determined_users[$idx], $user);
            }
            else {
                $idx = $num_team_id[$user->team_id] = $idx_team_id++;
                array_push($partially_determined_users, array());
                array_push($partially_determined_users[$idx], $user);
            }
        }
    }

    // 余りが商を上回る場合はもう 1 チーム作る
    if($div < $mod) {
        if(count($partially_determined_users) > $div + 1) {
            return INVALID_TEAM_ID_ASSIGNMENT;
        }
        
        for($i=0; $i<=$div; $i++) {
            array_push($initial_teams, array());
            $lim = ($i < $div ? NUM_OF_TEAM_MEMBER : $mod);
            if($i < count($partially_determined_users) and count($partially_determined_users[$i]) > $lim) {
                return INVALID_TEAM_ID_ASSIGNMENT;
            }
            for($j=0; $j<$lim; $j++) {
                array_push($idx_row, $i);
                array_push($idx_col, $j);
                if($i < count($partially_determined_users) and $j < count($partially_determined_users[$i])) {
                    array_push($initial_teams[$i], $partially_determined_users[$i][$j]);
                }
                else {
                    array_push($initial_teams[$i], $free_users[$free_idx++]);
                }
            }
        }
    }
    // 上回らない場合は既存のチームに 1 人追加する
    else {
        if(count($partially_determined_users) > $div) {
            return INVALID_TEAM_ID_ASSIGNMENT;
        }
        
        for($i=0; $i<$div; $i++) {
            array_push($initial_teams, array());
            $lim = NUM_OF_TEAM_MEMBER + ($i >= $div - $mod);
            if($i < count($partially_determined_users) and count($partially_determined_users[$i]) > $lim) {
                return INVALID_TEAM_ID_ASSIGNMENT;
            }
            for($j=0; $j<$lim; $j++) {
                array_push($idx_row, $i);
                array_push($idx_col, $j);
                if($i < count($partially_determined_users) and $j < count($partially_determined_users[$i])) {
                    array_push($initial_teams[$i], $partially_determined_users[$i][$j]);
                }
                else {
                    array_push($initial_teams[$i], $free_users[$free_idx++]);
                }
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
    $best_sum_of_dbl = $sum_of_dbl;

    $temp = START_TEMP;
    for($step=0; $step<NUM_OF_ANNEALING_STEP; $step++) {
        // 異なるチーム間でメンバーを入れ替え
        $u = random_int(0, $num_of_users - 1);
        $v = random_int(0, $num_of_users - 1);
        if($idx_row[$u] == $idx_row[$v]) continue;

        // チーム内に、team_id が同じメンバーが存在するならダメ
        $exist_same_team_id = false;
        for($i=0; $i<count($current_teams[$idx_row[$u]]); $i++) {
            if($i == $idx_col[$u]) continue;
            $id_a = $current_teams[$idx_row[$u]][$idx_col[$u]]->team_id;
            $id_b = $current_teams[$idx_row[$u]][$i          ]->team_id;
            if($id_a !== "" and $id_b !== "" and $id_a === $id_b) $exist_same_team_id = true;
        }
        for($i=0; $i<count($current_teams[$idx_row[$v]]); $i++) {
            if($i == $idx_col[$v]) continue;
            $id_a = $current_teams[$idx_row[$v]][$idx_col[$v]]->team_id;
            $id_b = $current_teams[$idx_row[$v]][$i          ]->team_id;
            if($id_a !== "" and $id_b !== "" and $id_a === $id_b) $exist_same_team_id = true;
        }
        if($exist_same_team_id == true) continue;

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
        $delta_sum_linear = $rhs_linear - $lhs_linear;
        $delta_sum_of_dbl = $rhs_dbl - $lhs_dbl;
        
        $next_sum_square = $sum_square + $delta_sum_square;
        $next_sum_linear = $sum_linear + $delta_sum_linear;
        $next_sum_of_dbl = $sum_of_dbl + $delta_sum_of_dbl;
        
        $next_score = calculatePoint($next_sum_square, $next_sum_linear, $next_sum_of_dbl, $num_of_teams);

        // 点数が小さいほうが良いので、diff が正なら採用・負でも確率的に採用
        $diff = 30 * ($current_score - $next_score) / $current_score;
        $prob = exp($diff / $temp);
        $accept = $prob > random_float();

        if($step % 3000 == 0) {
            $temp *= TEMP_COEFF;
        }

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
                $best_teams      = $next_teams;
                $best_score      = $next_score;
                $best_sum_of_dbl = $next_sum_of_dbl;
            }
        }
        else {
            // 結局交換しないので
            swap($next_teams[$idx_row[$u]][$idx_col[$u]],
                 $next_teams[$idx_row[$v]][$idx_col[$v]]);         
        }
    }

    // echo "! final result: sum_of_dbl = " . $best_sum_of_dbl . ", point = " . $best_score . "<br>";

    // 各チームをソート
    foreach($best_teams as &$team) {
        usort($team, "cmpUserInfo");
    }
    // 全体をソート
    usort($best_teams, "cmpTeamInfo");
    return $best_teams;
}

?>