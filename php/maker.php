<?php

// 外部ファイルのインポート
require_once "./utils.php";
require_once "./user_class.php";
require_once "./user_array.php";
require_once "./core.php";

// 他のサイトでインラインフレーム表示を禁止する（クリックジャッキング対策）
header('X-FRAME-OPTIONS: SAMEORIGIN');

// セッション開始
session_start();

// HTML特殊文字をエスケープする関数
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

?>

<!DOCTYPE html>
<html>
    <head>
        <title>Competitive Programming Team Maker</title>
        <meta charset="UTF-8">
        <meta name="keywords" content="競技プログラミング">
        <meta name="description" content="競技プログラミングのチーム編成をサポートする Web アプリケーションです。">
        <meta name="author" content="tsutaj">
        <meta http-equiv="content-language" content="ja">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

        <!-- css -->
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">
        <link rel="stylesheet" href="../lib/main.css">

        <!-- fonts -->
        <link href="https://fonts.googleapis.com/css?family=Oxygen" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css?family=M+PLUS+Rounded+1c" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css?family=Lato:400,700" rel="stylesheet"> 
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css" integrity="sha384-UHRtZLI+pbxtHCWp1t77Bi1L4ZtiqrqD80Kn4Z8NTSRyMA2Fd33n5dQ8lWUE00s/" crossorigin="anonymous">

        <!-- javascript -->
        <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
        <script type="text/javascript" src="../js/detail_anime.js"></script>
        <script type="text/javascript" src="../js/table_operation.js"></script>
        <script type="text/javascript" src="../js/import_csv.js"></script>
    </head>
    <body>
        <!-- main-container -->
        <div class="container" id="main-container">
            <!-- navigation -->
            <nav class="navbar navbar-expand-lg fixed-top navbar-dark bg-primary">
                <a class="navbar-brand" href="../index.php">TeamMaker</a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav">
                        <li class="nav-item active">
                            <a class="nav-link" href="../index.php">Home <span class="sr-only">(current)</span></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../about.html">About</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../contact.html">Contact</a>
                        </li>
                    </ul>
                </div>
            </nav>
            <!-- navigation end -->

            <h1>Competitive Programming Team Maker</h1>

            <?php
            $token = filter_input(INPUT_POST, 'token', FILTER_DEFAULT, FILTER_FLAG_STRIP_LOW);
            $invalid_token = false;

            // トークンが適切な状態でない場合 (空であるか、一致していない)
            if(empty($token) or !hash_equals($token, $_SESSION['token'])) {
                $invalid_token = true;
            }
            else{                
                // 再実行フラグに関する処理 (boolean 値を入れるようにする)
                $re_execute = filter_input(INPUT_POST, 're_execute', FILTER_VALIDATE_BOOLEAN);
                if(empty($re_execute)) $re_execute = false;
                
                // 1. 再実行フラグが立っているなら前の情報を使う
                if($re_execute !== false) {
                    foreach(array('user_array', 'error_array') as $str) {
                        foreach($_SESSION[$str] as &$user) $user->escape();
                    }
                    $user_array = $_SESSION['user_array'];
                    $error_array = $_SESSION['error_array'];
                }
                // 2. 再実行フラグが立っていなければ POST の情報を受け取る
                else {
                    // POST で送られた情報をフィルタリングしつつ受け取る
                    $args = array();
                    foreach(array('take_user', 'handle', 'user_id', 'affiliation') as $v) {
                        // special chars を除去・配列に限定
                        $args[$v] = array('filter' => FILTER_SANITIZE_SPECIAL_CHARS,
                                          'flags'  => FILTER_REQUIRE_ARRAY);
                    }
                    // extract で各変数をバラバラにできる
                    $filter_info = filter_input_array(INPUT_POST, $args);
                    extract($filter_info);

                    $tables = getUserArray($take_user, $handle, $user_id, $affiliation);
                    list($user_array, $error_array) = $tables;
                    $_SESSION['user_array'] = $user_array;
                    $_SESSION['error_array'] = $error_array;     
                }

                // 最終的な割当を得る
                $final_assignment = getAssignments($user_array);
                $_SESSION['final_assignment'] = $final_assignment;
                $num_of_column = 0;

                // 所属の被りがあるかどうか
                $exist_dbl_affiliation = false;

                // かぶっているかどうかを判定
                $has_dbl = array();
                foreach($final_assignment as $team) {
                    usort($team, "cmpUserAffil");
                    $last_affil = $has_dbl_team = false;
                    foreach($team as $member) {
                        $affil = $member->affiliation;
                        if($affil == NONE_AFFIL) continue;
                        if($last_affil == $affil) {
                            $has_dbl_team = true;
                            $exist_dbl_affiliation = true;
                        }
                        $last_affil = $affil;
                    }
                    array_push($has_dbl, $has_dbl_team);
                }

                // 列の数
                foreach($final_assignment as $team) {
                    $num_of_column = max($num_of_column, count($team));
                }
            }

            ?>

            <?php if($invalid_token) : ?>
                <div class="alert alert-danger" role="alert">
                    <span class="fas fa-exclamation-triangle"></span> 不正なアクセスです。
                </div>

                <section>
                    <div class="form" role="form" style="text-align:center;">
                        <div class="row">
                            <div class="col-sm">
                                <button type="button" class="btn btn-primary btn-block mb-3" onClick="location.href='../index.php'">トップに戻る</button>
                            </div>
                        </div>
                    </div>
                </section>
            <?php elseif($final_assignment == array()) : ?>
                <div class="alert alert-danger" role="alert">
                    <span class="fas fa-exclamation-triangle"></span> チーム分けの対象となるユーザーが存在しません。
                </div>

                <section>
                    <div class="form" role="form" style="text-align:center;">
                        <div class="row">
                            <div class="col-sm">
                                <button type="button" class="btn btn-primary btn-block mb-3" onClick="location.href='../index.php'">トップに戻る</button>
                            </div>
                        </div>
                    </div>
                </section>
            <?php else : ?>
                <section>
                    <form action="./maker.php" method="post" role="form" style="text-align:center;">
                        <div class="row">
                            <div class="col-sm">
                                <!-- ボタンが押されたら再実行フラグを立てて遷移 -->
                                <input type="hidden" name="token" value="<?php echo h($token); ?>">
                                <input type="hidden" name="re_execute" value="1">
                                <button type="submit" class="btn btn-primary btn-block mb-3" onClick="location.href='./maker.php'">もう一度実行</button>
                            </div>
                            <div class="col-sm">
                                <button type="button" class="btn btn-primary btn-block mb-3" onClick="location.href='./json_download.php'">JSON として保存</button>
                            </div>
                            <div class="col-sm">
                                <button type="button" class="btn btn-primary btn-block mb-3" onClick="location.href='../index.php'">トップに戻る</button>
                            </div>
                        </div>
                    </form>
                </section>
                
                <div class="card mb-3">
                    <div class="card-header">
                        <span class="fa fa-info-circle"></span> 結果について
                    </div>
                    <div class="card-body">
                        「JSON として保存」をクリックすることで、下に表示されているチーム分けの結果を JSON 形式で保存することができます。
                        <!-- この JSON ファイルを次のチーム分けの機会のために取っておき、トップページの「高度な設定」内でインポートすることで、今回のチーム分けで同一のチームに割り当てられたメンバーが再び同一のチームに割り当てられることがないようにチーム分けすることができます。 -->
                        この JSON ファイルを次のチーム分けの機会のために取っておき、トップページ内でインポートすることで、今回のチーム分けで同一のチームに割り当てられたメンバーが再び同一のチームに割り当てられることがないようにチーム分けする機能を追加予定です。
                    </div>
                </div>
                
                <?php if($exist_dbl_affiliation) : ?>
                    <div class="alert alert-danger" role="alert">
                        <span class="fas fa-exclamation-triangle"></span> 背景色が赤色である行で、所属の重複が発生しています。「もう一度実行」をクリックすることで、同条件でもう一度チーム分けを実行できます。
                    </div>
                <?php endif; ?>
                
                <table class="table table-striped mb-3">
                    <thead class="thead-light">
                        <tr>
                            <th scope="col">#</th>
                            <?php
                            for($i=1; $i<=$num_of_column; $i++) {
                                echo("<th scope=\"col\">Member " . (string)$i . "</th>");
                            }
                            ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $current_idx = 0;
                        for($k=0; $k<count($final_assignment); $k++) {
                            $team = $final_assignment[$k];
                            $current_idx++;
                            $num_of_members = count($team);

                            // 所属が重複しているなら強調
                            if($has_dbl[$k]) {
                                echo("<tr class=\"table-danger\">");
                            }
                            else {
                                echo("<tr>");
                            }
                            
                            echo("<td style=\"text-align:center;\">Team " . (string)$current_idx . "</td>");
                            for($i=0; $i<$num_of_column; $i++) {
                                echo("<td><div style=\"text-align:center;\">");
                                if($i < $num_of_members) {
                                    $color_code = getColorCode($team[$i]->rating);
                                    
                                    // ハンドルネームとユーザー ID
                                    echo($team[$i]->handle);
                                    if($team[$i]->user_name !== "") {
                                        echo(" (<span class=\"atcoder_user_name\" style=\"font-weight:bold;color:" . $color_code . ";\">" . $team[$i]->user_name . "</span>)");
                                    }
                                    echo("<br />");
                                    // 所属
                                    echo($team[$i]->affiliation);
                                }
                                echo("</div></td>");
                            }
                            echo("</tr>");
                        }
                        ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <?php if(!empty($error_array)) : ?>
                <div class="card border-info mb-3">
                    <div class="card-header bg-info border-info text-white">
                        <span class="fa fa-info-circle"></span> AtCoder ID が不正な値である、もしくは接続がタイムアウトしたため、以下の項目は無視されました。
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php
                            foreach($error_array as $user) {
                                $error_str = $user->handle . " (" . $user->user_name . "), " . $user->affiliation;
                                echo("<li class=\"list-group-item\">" . $error_str . "</li>");
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <!-- main-container end -->

        <!-- bootstrap (head で読み込もうとしたらダメだった、ふしぎ) -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.6/umd/popper.min.js" integrity="sha384-wHAiFfRlMFy6i5SRaxvfOCifBUQy1xHdJ/yoi7FRNXMRBu5WHdZYu1hA6ZOblgut" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/js/bootstrap.min.js" integrity="sha384-B0UglyR+jN6CkvvICOB2joaf5I4l3gm9GU6Hc1og6Ls7i6U/mkkaduKaBhlAXv9k" crossorigin="anonymous"></script>
        <!-- bootstrap end -->        
    </body>
</html>
