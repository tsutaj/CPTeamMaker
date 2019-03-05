<?php

// 1 行あたりの情報の列数
const NUM_OF_COLS = 4;

// CSV ファイルを読み込み、配列に変換
function getCSVFile($src) {
    // CSV が SJIS だとうくちゃん
    // ref: https://www.marineroad.com/staff-blog/12831.html
    $unknown_encoding_data = file_get_contents($src);
    if($unknown_encoding_data === false) return false;
    
    $utf8_data = mb_convert_encoding($unknown_encoding_data, "UTF-8", "auto");

    $temp = tmpfile();
    $meta = stream_get_meta_data($temp);
    fwrite($temp, $utf8_data);
    rewind($temp);

    $obj_file = new SplFileObject($meta['uri'], "r");
    $obj_file->setFlags(SplFileObject::READ_CSV);
    
    $res = array();
    foreach($obj_file as $row) {
        // 最後の行は NULL になってしまうみたい
        if(count($row) != NUM_OF_COLS) continue;
        
        $null_count = 0;
        foreach($row as &$elem) {
            $elem = filter_var($elem, FILTER_SANITIZE_SPECIAL_CHARS);
            $null_count += (empty($elem));
        }

        if($null_count != NUM_OF_COLS) {
            array_push($res, $row);
        }
    }

    fclose($temp);
    return $res == array() ? false : $res;
}

?>