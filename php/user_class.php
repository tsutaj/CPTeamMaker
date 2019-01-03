<?php

// ユーザーのハンドルネーム・ID・レート・所属を管理
class UserInfo {
    public $handle, $user_name, $rating, $affiliation;

    function __construct($handle_, $user_name_, $rating_, $affil_) {
        $this->handle      = $handle_;
        $this->user_name   = $user_name_;
        $this->rating      = $rating_;
        $this->affiliation = $affil_;
        $this->escape();
    }

    function escape() {
        $this->handle      = filter_var($this->handle     , FILTER_SANITIZE_SPECIAL_CHARS);
        $this->user_name   = filter_var($this->user_name  , FILTER_SANITIZE_SPECIAL_CHARS);
        $this->rating      = filter_var($this->rating     , FILTER_SANITIZE_SPECIAL_CHARS);
        $this->affiliation = filter_var($this->affiliation, FILTER_SANITIZE_SPECIAL_CHARS);
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

?>