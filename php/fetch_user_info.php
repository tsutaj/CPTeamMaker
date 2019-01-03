<?php

// デフォルトのユーザー情報を返す
function getEmptyInfo($user_id) {
    $res = array();
    $res['name'] = $user_id;
    $res['rating'] = -1;
    $res['highest'] = -1;
    return $res;
}

// スクレイピングでユーザー情報を取得 
function getUserRating($user_id) {
    // アルファベット、数字、アンダースコア以外の 1 文字が存在
    if(preg_match('/\W/', $user_id)) return getEmptyInfo($user_id);

    // ユーザーページ取得
    $context = stream_context_create(array('http' => array('ignore_errors' => true)));
    $userpage = @file_get_contents("https://atcoder.jp/users/{$user_id}", false, $context);
    if($userpage === false) return getEmptyInfo($user_id);

    // 200 以外のステータスコードならダメ
    $pos = strpos($http_response_header[0], '200');
    if($pos === false) return getEmptyInfo($user_id);

    $dom = new DOMDocument();
    @$dom->loadHTML($userpage);  // invalid entity とかでめちゃ怒られるらしい
    $xml = new SimpleXMLElement($dom->saveXML());
    $res = array();

    // 名前
    $screen = $xml->xpath('//div/h3')[0];
    $res['name'] = (string)$screen->a->span;  // for case-sensitivity

    // レーティングの値
    $res['rating'] = $res['highest'] = 0;

    // レートがついてない人だと table の要素数が違うらしい？
    if(count($xml->xpath("//div/table")) >= 2) {
        $table = $xml->xpath("//div/table")[1];
        if ($table) {
            $res['rating'] = (string)$table->tr[1]->td[0]->span;
            $res['highest'] = (string)$table->tr[2]->td[0]->span;
        }
    }

    return $res;
}

// miozune さんの API を使って取得
// 若干遅い？あとこれも rated に出ていないとダメそう
// case-sensitivity にきびしい
function getUserRatingbyMiozuneAPI($user_id) {
    $base_url = "https://us-central1-atcoderusersapi.cloudfunctions.net/api/info/username/";
    $url = $base_url . $user_id;
    
    $options = [
        'http' => [
            'method' => 'GET',
            'timeout' => 5,
            ]
        ];

    $json = file_get_contents($url, false, stream_context_create($options));
    // 結果が返って来なかったらダメ
    if($json === false) {
        return getEmptyInfo($user_id);
    }

    // ステータスコードが 200 以外ならダメ
    preg_match('/HTTP\/1\.[0|1|x] ([0-9]{3})/', $http_response_header[0], $matches);
    $statuscode = (int)$matches[1];
    if($statuscode !== 200) {
        return getEmptyInfo($user_id);
    }

    $jsonArray = json_decode($json, true);
    $res = array();
    $res['rating'] = (int)$jsonArray['data']['rating'];
    $res['highest'] = (int)$jsonArray['data']['highest_rating'];
    $res['name'] = $user_id;

    return $res;
}

// AtCoder の history API を利用して取得
// 存在しない ID と一度も参加していない人の結果が同じでア
// case-sensitivity は大丈夫だが正しいものに直す術がない
function getUserRatingByOfficialAPI($user_id) {
    $base_url_1 = "https://atcoder.jp/users/";
    $base_url_2 = "/history/json";
    $url = $base_url_1 . $user_id . $base_url_2;

    $options = [
        'http' => [
            'method' => 'GET',
            'timeout' => 5,
            ]
        ];

    $json = file_get_contents($url, false, stream_context_create($options));
    // 結果が返って来なかったらダメ
    if($json === false) {
        return getEmptyInfo($user_id);
    }

    // ステータスコードが 200 以外ならダメ
    preg_match('/HTTP\/1\.[0|1|x] ([0-9]{3})/', $http_response_header[0], $matches);
    $statuscode = (int)$matches[1];
    if($statuscode !== 200) {
        return getEmptyInfo($user_id);
    }

    $jsonArray = json_decode($json, true);
    $res = array();
    $res['rating'] = (int)end($jsonArray)['NewRating'];

    $res['highest'] = 0;
    // だりーわん
    foreach($jsonArray as $info) {
        $res['highest'] = max($res['highest'], $info['NewRating']);
    }

    $res['name'] = $user_id;

    return $res;
}

?>