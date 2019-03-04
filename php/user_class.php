<?php

// ユーザーのハンドルネーム・チーム ID・AtCoder ID・レート・所属を管理
class UserInfo {
    public $handle, $team_id, $user_name, $rating, $affiliation, $past_assignments;

    function __construct($handle_, $team_id_, $user_name_, $rating_, $affil_, $past_assignments_) {
        $this->handle           = $handle_;
        $this->team_id          = $team_id_;
        $this->user_name        = $user_name_;
        $this->rating           = $rating_;
        $this->affiliation      = $affil_;
        $this->past_assignments = $past_assignments_;
        $this->escape();
    }

    function escape() {
        $this->handle           = filter_var($this->handle          , FILTER_SANITIZE_SPECIAL_CHARS);
        $this->team_id          = filter_var($this->team_id         , FILTER_SANITIZE_SPECIAL_CHARS);
        $this->user_name        = filter_var($this->user_name       , FILTER_SANITIZE_SPECIAL_CHARS);
        $this->rating           = filter_var($this->rating          , FILTER_SANITIZE_SPECIAL_CHARS);
        $this->affiliation      = filter_var($this->affiliation     , FILTER_SANITIZE_SPECIAL_CHARS);

        foreach($this->past_assignments as &$pa) {
            $pa = filter_var($pa, FILTER_SANITIZE_SPECIAL_CHARS);
        }
    }

    function setTeamID($new_team_id) {
        $this->team_id = filter_var($new_team_id, FILTER_SANITIZE_SPECIAL_CHARS);
    }
};

// 最終的なチーム分け表示のためのソートに使用
function cmpUserInfo($a, $b) {
    // レートが高い順
    if($a->rating != $b->rating) {
        return ($a->rating < $b->rating) ? 1 : -1;
    }
    // 辞書順で小さい順
    if($a->handle != $b->handle) {
        return ($a->handle < $b->handle) ? -1 : 1;
    }
    return 0;
}

// 所属被りを計算するためのソートに使用
function cmpUserAffil($a, $b) {
    // 所属が辞書順で小さい順
    if($a->affiliation != $b->affiliation) {
        return ($a->affiliation < $b->affiliation) ? -1 : 1;
    }
    return 0;
}

// チーム分け全体のソートに使用
function cmpTeamInfo($a, $b) {
    $sz = min(count($a), count($b));
    for($i=0; $i<$sz; $i++) {
        if($a[$i]->rating != $b[$i]->rating) {
            return ($a[$i]->rating < $b[$i]->rating) ? 1 : -1;
        }
        if($a[$i]->handle != $b[$i]->handle) {
            return ($a[$i]->handle < $b[$i]->handle) ? -1 : 1;
        }
    }
    if(count($a) != count($b)) {
        return (count($a) < count($b)) ? -1 : 1;
    }
    else return 0;
}

?>