<?php

// POST で送られた情報をフィルタリングしつつ受け取る
$args = array();
foreach(array('team_id', 'handle', 'user_id', 'affiliation') as $v) {
    // special chars を除去・配列に限定
    $args[$v] = array('filter' => FILTER_SANITIZE_SPECIAL_CHARS,
                      'flags'  => FILTER_REQUIRE_ARRAY);
}
// extract で各変数をバラバラにできる
$filter_info = filter_input_array(INPUT_POST, $args);
extract($filter_info);

$tables = array();
for($i=0; $i<count($handle); $i++) {
    // ハンドルネームがない行は無視
    if(empty($handle[$i])) continue;
    array_push($tables, array($team_id[$i], $handle[$i], $user_id[$i], $affiliation[$i]));
}

ob_start();
$fp = fopen('php://output', 'w');
foreach ($tables as $line) {
    fputcsv($fp, $line);
}
$csv = ob_get_clean();
$csv = mb_convert_encoding($csv, "UTF-8", "auto");

$filename = "export";
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename='.$filename.'.csv');
echo $csv;

?>