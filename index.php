<?php

// 他のサイトでインラインフレーム表示を禁止する（クリックジャッキング対策）
header('X-FRAME-OPTIONS: SAMEORIGIN');

// セッション開始
session_start();

// HTML特殊文字をエスケープする関数
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// トークンの作成
if(!isset($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

// このトークンが $_SESSION のものと一致してなければ
// maker.php に直接アクセスして悪さされる可能性が・・・
$token = $_SESSION['token'];

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
        <link rel="stylesheet" href="./lib/main.css">

        <!-- fonts -->
        <link href="https://fonts.googleapis.com/css?family=Oxygen" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css?family=M+PLUS+Rounded+1c" rel="stylesheet"> 
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css" integrity="sha384-UHRtZLI+pbxtHCWp1t77Bi1L4ZtiqrqD80Kn4Z8NTSRyMA2Fd33n5dQ8lWUE00s/" crossorigin="anonymous">

        <!-- javascript -->
        <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
        <script type="text/javascript" src="./js/detail_anime.js"></script>
        <script type="text/javascript" src="./js/table_operation.js"></script>
        <script type="text/javascript" src="./js/import_csv.js"></script>
    </head>
    <body>
        <!-- main-container -->
        <div class="container" id="main-container">
            <!-- navigation -->
            <nav class="navbar navbar-expand-lg fixed-top navbar-dark bg-primary">
                <a class="navbar-brand" href="./index.php">TeamMaker</a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav">
                        <li class="nav-item active">
                            <a class="nav-link" href="./index.php">Home <span class="sr-only">(current)</span></a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./about.html">About</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="./contact.html">Contact</a>
                        </li>
                    </ul>
                </div>
            </nav>
            <!-- navigation end -->

            <h1>Competitive Programming Team Maker</h1>
            <div class="alert alert-primary" role="alert">
                Jan 03, 2019: beta 版をリリースしました！
            </div>

            <!-- introduction -->
            <div class="card">
                <div class="card-header">
                    <span class="fas fa-hands-helping"></span> 競技プログラミングのチーム分け補助アプリ
                </div>
                <div class="card-body">
                    これは競技プログラミングのチーム分けをサポートする Web アプリケーションです。ユーザーの情報を入力として受け取り、以下のポリシーに基づきチームへの割当を返します。
                    <ul style="margin: 10px 0;">
                        <li>チーム間の実力差をできるだけ小さくする</li>
                        <li>できるだけ所属が異なる参加者同士でチームを組む</li>
                    </ul>

                    内部で使用している評価関数やアルゴリズムなどの、アプリケーションの詳細については <a href="./about.html">About ページ</a> をご覧ください。
                </div>
            </div>
            <!-- introduction end -->

            <!-- import CSV file -->
            <h3><span class="fas fa-file-csv"></span> CSV ファイルをインポート</h3>

            <div id="csv-card">
                <div class="card how-to-use-detail">
                    <div class="card-header" id="heading-csv">
                        <a data-toggle="collapse" class="text-body" href="#collapse-csv" aria-expanded=false" aria-controls="collapse-csv">
                            <span class="fa fa-info-circle"></span> 詳しい使い方...
                            <span class="fas fa-chevron-down float-right"></span>
                        </a>
                    </div>
                    <div id="collapse-csv" class="collapse" aria-labelledby="headmargin:0ing-csv">
                        <div class="card-body">
                            以下の例で示されるような、「ハンドルネーム, AtCoder ID, 所属」がカンマ区切りで書かれている CSV ファイルを予め用意してください。下の「ファイルを選択」ボタンで CSV ファイルを選択し、「CSV をインポート」ボタンでその内容を表に反映させます。

                            <div class="card" style="margin:10px 10px 0 10px;">
                                <div class="card-body">
                                    <h5 class="card-title">CSV ファイルの例</h5>
                                    <pre style="margin:0;"><code>tsutaj,Tsuta_J,four-t
monkukui,monkukui,ragan
waku,wakuwinmail,Megido</code></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <form role="form" action="./index.php" method="post" enctype="multipart/form-data">
                <!-- 参照とインポートボタンを用意、何を読み込んだか表示すると親切？ -->
                <div>
                    <div class="input-group">
                        <label class="input-group-btn">
                            <span class="btn btn-primary">
                                ファイルを選択<input type="file" name="csv_file" accept="text/csv" style="display:none">
                            </span>
                        </label>
                        <input type="text" class="form-control" readonly="">
                    </div>
                    <button type="submit" class="btn btn-primary mb-2 btn-block">CSV をインポート</button>
                </div>
            </form>

            <?php
            require_once "./php/csv_import.php";
            $csv_array = false;
            if(isset($_FILES["csv_file"]["tmp_name"]) and is_uploaded_file($_FILES["csv_file"]["tmp_name"])) {
                $csv_array = getCSVFile($_FILES["csv_file"]["tmp_name"]);
            }
            ?>
            <!-- import CSV file end -->
            
            <!-- text input forms -->
            <h3><span class="fas fa-pencil-alt"></span> テーブルを直接編集</h3>
            <div id="edit-table-card">
                <div class="card how-to-use-detail">
                    <div class="card-header" id="heading-edit-table">
                        <a data-toggle="collapse" class="text-body" href="#collapse-edit-table" aria-expanded="false" aria-controls="collapse-edit-table">
                            <span class="fa fa-info-circle"></span> 詳しい使い方...
                            <span class="fas fa-chevron-down float-right"></span>
                        </a>
                    </div>
                    <div id="collapse-edit-table" class="collapse" aria-labelledby="heading-edit-table">
                        <div class="card-body">
                            <p>「ハンドルネーム, Team ID, AtCoder ID, 所属」を下の表に書いてください。<p>
                            <ul>
                                <li>Team ID は、部分的にチームが決定している際に使用します。同一の Team ID が入力された人は、チーム分けにおいても必ず同一のチームになります。同一の Team ID を大量に入力した場合など、不正な入力である場合はチーム分けが失敗しますのでご注意ください。</li>
                                <li>AtCoder ID は省略可能です</li>
                                <li>所属になにも記載しなかった場合、無所属として扱われます。無所属同士の重複については考慮されません</li>
                                <li>そのユーザーをチーム分けで使用したくない場合は、"Take" のチェックを無効にします。</li>
                            </ul>
                            <p>行の追加・削除は下にあるボタンで行えます。</p>
                            <p class="mb-1">特定の所属について全てチェックを入れる / 外す処理も下にあるボタンで行えます。完全一致 (ただし case-insensitive) で判定しています。</p>
                        </div>
                    </div>
                </div>
            </div>
            <form id="main_form_table" action="./php/maker.php" method="post">
                <table class="table table-striped mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th scope="col">Take</th>
                            <th scope="col">Team ID</th>
                            <th scope="col">Handle</th>
                            <th scope="col">AtCoder ID</th>
                            <th scope="col">Affiliation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $row_length = 0;
                        if($csv_array) {
                            $row_length = count($csv_array);
                            for($i=0; $i<count($csv_array); $i++) {
                                $row = $csv_array[$i];
                                echo <<<EOT
<tr>
    <td class="slim-cell"><div class="custom-control custom-checkbox slim-form-check"><input type="checkbox" class="custom-control-input" name="take_user[{$i}]" checked="checked" id="take-user-{$i}"><label class="custom-control-label" for="take-user-{$i}"></label></div></td>
    <td class="slim-cell col-sm-1"><div class="form-group slim-form-group"><input type="text" class="form-control" name="team_id[{$i}]" value=""></div></td>
    <td class="slim-cell"><div class="form-group slim-form-group"><input type="text" class="form-control" name="handle[{$i}]" value="{$row[0]}"></div></td>
    <td class="slim-cell"><div class="form-group slim-form-group"><input type="text" class="form-control" name="user_id[{$i}]" value="{$row[1]}"></div></td>
    <td class="slim-cell"><div class="form-group slim-form-group"><input type="text" class="form-control" name="affiliation[{$i}]" id="affiliation-user-{$i}" value="{$row[2]}"></div></td>
</tr>
EOT;
                            }
                        }
                        else {
                            $row_length = 1;
                            echo <<<EOT
<tr>
    <td class="slim-cell">
        <div class="custom-control custom-checkbox slim-form-check">
            <input type="checkbox" class="custom-control-input" name="take_user[0]" checked="checked" id="take-user-0">
            <label class="custom-control-label" for="take-user-0"></label>
        </div>
    </td>
    <td class="slim-cell col-sm-1">
        <div class="form-group slim-form-group">
            <input type="text" class="form-control" name="team_id[0]">
        </div>
    </td>
    <td class="slim-cell">
        <div class="form-group slim-form-group">
            <input type="text" class="form-control" name="handle[0]" placeholder="tsutaj">
        </div>
    </td>
    <td class="slim-cell">
        <div class="form-group slim-form-group">
            <input type="text" class="form-control" name="user_id[0]" placeholder="Tsuta_J">
        </div>
    </td>
    <td class="slim-cell">
        <div class="form-group slim-form-group">
            <input type="text" class="form-control" name="affiliation[0]" id="affiliation-user-0" placeholder="Mitakihara Junior High School">
        </div>
    </td>
</tr>
EOT;
                        }
                        ?>                    
                    </tbody>
                    <script type="text/javascript">
                     // row_length の値を input の方に反映させる
                     $(':hidden[name="row_length"]').val(parseInt(<?php print($row_length - 1) ?>));
                    </script>
                    <tfoot>
                        <tr>
                            <td colspan="5" class="p-0" style="border-style: none;">
                                <div class="btn-toolbar mt-2" role="toolbar" aria-label="Toolbar with button groups">
                                    <div class="btn-group mb-2 mr-2" role="group" aria-label="Group about row operation">
                                        <button id="add_row" class="btn btn-secondary" type="button">行を追加</button>
                                        <button id="del_row" class="btn btn-secondary" type="button">行を削除</button>
                                    </div>
                                    <div class="btn-group mb-2" role="group" aria-label="Group about all selection">
                                        <button id="take_all" class="btn btn-secondary" type="button">全て選択</button>
                                        <button id="remove_all" class="btn btn-secondary" type="button">全て解除</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="5" class="p-0" style="border-style: none;">
                                <div>
                                    所属が 
                                    <input type="text" id="target_affil" class="form-control col-sm-3" style="display: inline;">
                                     であるユーザーに対して
                                    <div class="btn-group mb-2" role="group" aria-label="Group about selection by affiliation">
                                        <button id="check_by_affil"   class="btn btn-secondary" type="button">全てチェック</button>
                                        <button id="uncheck_by_affil" class="btn btn-secondary" type="button">全てチェックを外す</button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
                <!-- text input forms end -->

                <!-- submit form -->
                <input type="hidden" name="row_length">
                <input type="hidden" name="token" value="<?php echo h($token); ?>">
                <input id="run_team_making_btn" type="submit" name="send" class="btn btn-primary mb-2 btn-block" value="チーム分けを実行">
                <input id="csv_export_btn" type="submit" name="send" class="btn btn-primary mb-4 btn-block" value="CSV をエクスポート">
                <!-- submit form end-->
            </form>
        </div>
        <!-- main-container end -->

        <!-- bootstrap (head で読み込もうとしたらダメだった、ふしぎ) -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.6/umd/popper.min.js" integrity="sha384-wHAiFfRlMFy6i5SRaxvfOCifBUQy1xHdJ/yoi7FRNXMRBu5WHdZYu1hA6ZOblgut" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/js/bootstrap.min.js" integrity="sha384-B0UglyR+jN6CkvvICOB2joaf5I4l3gm9GU6Hc1og6Ls7i6U/mkkaduKaBhlAXv9k" crossorigin="anonymous"></script>
        <!-- bootstrap end -->
    </body>
</html>
