<?php
require('api.php');

require('vendor/autoload.php');
require_once(dirname(__FILE__) . "/vendor/autoload.php"); //ライブラリの読み込み
/* 環境変数の読み込み */
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
/* ***** */

function main() {
    /* イベント（ユーザからの何らかのアクション）を取得．特にいじらなくてOK． */
    $json_string = file_get_contents('php://input');
    $jsonObj = json_decode($json_string);
    $events = $jsonObj->{"events"};
    /* ***** */

    // ユーザから来たメッセージを1件ずつ処理
    foreach($events as $event) {
        $replyToken = $event->{"replyToken"}; // メッセージを返すのに必要
        $type = $event->{"message"}->{"type"}; // メッセージタイプ
        $messages = [];

        if($type == "text") { // メッセージがテキストのとき
            $text = $event->{"message"}->{"text"}; // ユーザから送信されたメッセージテキスト
            $userId = $event->{'source'}->{'userId'}; // ユーザIDを取得
            if($text == "ちいもく達成") { // 「目標達成」というメッセージがユーザから来たとき
                // データベースに接続
                // $messages.array_push($messages, ["type" => "text", "text" =>  $answer ]);
                $dbdsn = getenv("DB_DSN");
                $userName = getenv("DB_USER");
                $pass = getenv("DB_PASSWORD");
                $dbname = getenv("DB_NAME");
                
                $conn = new mysqli($dbdsn, $userName, $pass, $dbname);
                
                
                // 接続をチェック
                if ($conn->connect_error) {
                    //エラーメッセージを表示
                    $messages.array_push($messages, ["type" => "text", "text" =>  "エラー" ]);
                } else {
                    $messages.array_push($messages, ["type" => "text", "text" =>  "つながりました" ]);
                }

                // $userIdの目標1を取得
                $sql = "UPDATE goals SET continuity = continuity + 1 WHERE user_id = \"$userId\"";
                // プリペアドステートメントを使用してSQLインジェクションを防ぐ
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    //エラーメッセージを表示
                    $messages.array_push($messages, ["type" => "text", "text" =>  $conn->error ]);
                } else {
                    $messages.array_push($messages, ["type" => "text", "text" =>  "インジェクションのとこ成功" ]);
                }

                // パラメータをバインドする
                $stmt->bind_param("s", $userId);

                // SQL文を実行する
                if ($stmt->execute()) {
                    $messages.array_push($messages, ["type" => "text", "text" =>   "実行成功" ]);
                } else {
                    echo "Error: " . $stmt->error;
                    $messages.array_push($messages, ["type" => "text", "text" =>  "実行エラー" ]);
                }   
                $conn->close();





            } 
            else if($text == "現状確認") { // 「現状確認」というメッセージがユーザから来たとき
                //データベースからユーザの達成度合いを取得
                $continuity = getContinuity($userId);
                $messages.array_push($messages, ["type" => "text", "text" =>  $continuity ]);
                //データベースからユーザの目標を取得
                $goaln = getgoaln($continuity, $userId);
                $messages.array_push($messages, ["type" => "text", "text" =>  $goaln ]);
            } 
            else if ($text == "目標入力") { // 「目標変更」というメッセージがユーザから来たとき
                //ユーザIDが存在するか確認
                // データベースに接続    
                $conn = connectDB();
                
                // $userIdがDBに存在するか確認
                $sql_check_userID = "SELECT COUNT(*) FROM goals WHERE user_id = \"$userId\"";

                // プリペアドステートメントを使用してSQLインジェクションを防ぐ
                $stmt = $conn->prepare($sql_check_userID);
                // パラメータをバインドする
                $stmt->bind_param("s", $userId);
                // SQL文を実行する
                $stmt->execute();
                // 結果をバインド
                $stmt->bind_result($userId_count);
                // 結果をフェッチして表示
                $stmt->fetch();
                //データベース接続を閉じる
                $conn->close();

                //ユーザIDが存在しない場合
                if ($userId_count == 0) {
                    // データベースに接続    
                    $conn = connectDB();
                    
                    // $userIdがDBに存在するか確認
                    $sql_add_user = "INSERT INTO goals (user_id, status) VALUES (\"$userId\", 1)";

                    // プリペアドステートメントを使用してSQLインジェクションを防ぐ
                    $stmt = $conn->prepare($sql_add_user);
                    // パラメータをバインドする
                    $stmt->bind_param("s", $userId);
                    // SQL文を実行する
                    $stmt->execute();
                    // // 結果をバインド
                    // $stmt->bind_result($userId_count);
                    // // 結果をフェッチして表示
                    // $stmt->fetch();
                    //データベース接続を閉じる
                    $conn->close();
                } else {
                    //変更していいかどうかをフレックスメッセージで確認
                    $messages = [
                        [
                            "type" => "flex",
                            "altText" => "目標変更確認",
                            "contents" => [
                                "type" => "bubble",
                                "body" => [
                                    "type" => "box",
                                    "layout" => "vertical",
                                    "contents" => [
                                        [
                                            "type" => "text",
                                            "text" => "目標変更",
                                            "weight" => "bold",
                                            "size" => "xl"
                                        ],
                                        [
                                            "type" => "text",
                                            "text" => "目標を変更しますか？",
                                            "margin" => "lg",
                                            "wrap" => true
                                        ]
                                    ]
                                ],
                                "footer" => [
                                    "type" => "box",
                                    "layout" => "horizontal",
                                    "contents" => [
                                        [
                                            "type" => "button",
                                            "action" => [
                                                "type" => "message",
                                                "label" => "はい",
                                                "text" => "はい"
                                            ],
                                            "style" => "primary"
                                        ],
                                        [
                                            "type" => "button",
                                            "action" => [
                                                "type" => "message",
                                                "label" => "いいえ",
                                                "text" => "いいえ"
                                            ],
                                            "style" => "secondary"
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ];
                }


                

                
                // // $prompt =
                // //$target = call_chatGPT($prompt); // chatGPTにメッセージを送信して返答を取得
                // $target = "小目標1：毎日、英語の教材を机の上に置く。\n
                // 小目標2：英語の本をパラパラめくる。\n
                // 小目標3：英語の教材を読む。\n
                // 小目標4：英字新聞を読む。\n
                // 小目標5：毎日、英語の音楽を聴く。\n
                // 小目標6：英語で日記を書く。\n
                // 小目標7：英語の映画を見る。\n
                // 小目標8：毎日1時間以上英語の勉強をする。"
                // // $continuityを0と定義
                // $continuity = 0;

                // // $targetから目標を抽出して配列に格納
                // $target_lines = explode("\n", $target);

                // // SQLのUPDATEステートメントを用意
                // $sql = "UPDATE goals SET user_id = \"$userID\", goal1 = \"$target_lines[0]\", goal2 = \"$target_lines[0]\", goal3 = \"$target_lines[0]\", goal4 = \"$target_lines[0]\", goal5 = \"$target_lines[0]\", goal6 = \"$target_lines[0]\", goal7 = \"$target_lines[0]\", goal8 = \"$target_lines[0]\", continuity = 0 WHERE user_id = \"$userID\"";

                // // プリペアドステートメントを準備
                // $stmt = $conn->prepare($sql);

                // // SQLエラー処理
                // if ($stmt === false) {
                //     echo "Prepare error: " . $conn->error;
                // } else {
                //     // パラメータをバインド (今回はcontinuityも含めます)
                //     $stmt->bind_param("ssssssssii", $target_lines[0], $target_lines[1], $target_lines[2], $target_lines[3], $target_lines[4], $target_lines[5], $target_lines[6], $target_lines[7], $continuity, $userId);

                //     // SQL文を実行
                //     if ($stmt->execute()) {
                //         echo "Record updated successfully";
                //     } else {
                //         echo "Update error: " . $stmt->error;
                //     }
                // }

                // // データベース接続を閉じる
                // $conn->close();

            } 
            else {
                //statusを確認
                // データベースに接続    
                $status = getStatus($userId);
                
                if ($status == 1) {

                    $prompt = "あなたは私のコーチです．\n\n

                        目標：毎日1km以上ランニングする\n
                        小目標1：ランニング用の靴を選び、準備する。\n
                        小目標2：ランニングする時間帯を決め、その時間に外に出る習慣をつける。\n
                        小目標3：毎日、200mだけ歩いて慣れる。\n
                        小目標4：毎日、200m歩いた後に100m走る。\n
                        小目標5：毎日、500mを歩く習慣を身につける。\n
                        小目標6：毎日、500m歩いた後に300m走る。\n
                        小目標7：毎日、200m歩いてから500m走る。\n
                        小目標8：毎日1km連続で走る。\n\n
                        
                        目標：毎日1時間以上英語の勉強をする\n
                        小目標1：毎日、英語の教材を机の上に置く。\n
                        小目標2：英語の本をパラパラめくる。\n
                        小目標3：英語の教材を読む。\n
                        小目標4：英字新聞を読む。\n
                        小目標5：毎日、英語の音楽を聴く。\n
                        小目標6：英語で日記を書く。\n
                        小目標7：英語の映画を見る。\n
                        小目標8：毎日1時間以上英語の勉強をする。\n\n
                        
                        目標：腹筋100回する\n\n
                        小目標1：腹筋運動のためのマットを用意する。\n
                        小目標2：マットの上に寝転がる。\n
                        小目標3：寝転がった状態から腹筋の姿勢で起き上がる。\n
                        小目標4：毎日10回の腹筋運動を継続する。\n
                        小目標5：毎日20回の腹筋運動に挑戦する。\n
                        小目標6：毎日40回の腹筋運動をこなす。\n
                        小目標7：毎日70回の腹筋運動を実施する。\n
                        小目標8：毎日100回の腹筋運動を目指す。\n
                        このように，目標に向けて，8この小目標を設定してください．\n\n
                        
                        以下，条件です．\n\n
                        
                        ・笑えるくらい初歩的なステップから始める．例えば，「毎日１万歩歩く」なら「靴を履く」レベルのことから始める．「3食自炊する」なら「キッチンに立つ」レベルのことから始める．\n
                        ・各小目標を3日間継続できたら次のステップに進み，前の小目標は行わない，もしくは取り入れた形で小目標を設定．\n
                        ・最終的には小目標8で設定した目標を達成できるようにする．\n
                        ・小目標は抽象的ではなく、数値目標や具体例を提示する。例えば「目標：毎日3食自炊する」だと，具体例として「トースト」や「パスタ」などを提示する．\n
                        ・小目標だけを提示するようにする．目標は提示しない．
                        \n\n目標：" . $text;
                    // // $answer = call_chatGPT($prompt); // chatGPTにメッセージを送信して返答を取得
                    // $answer = "小目標1：運動用のマットを購入する\n小目標2：運動のための時間を設定する\n小目標3：運動着を用意する\n小目標4：ストレッチを始める\n小目標5：腹筋運動を10回行う\n小目標6：腹筋運動を30回行う\n小目標7：腹筋運動を50回行う\n小目標8：腹筋運動を100回行う";
                    
                    
                    // // データベースに接続
                    // // $messages.array_push($messages, ["type" => "text", "text" =>  $answer ]);
                    // $dbdsn = getenv("DB_DSN");
                    // $userName = getenv("DB_USER");
                    // $pass = getenv("DB_PASSWORD");
                    // $dbname = getenv("DB_NAME");
                    
                    // $conn = new mysqli($dbdsn, $userName, $pass, $dbname);
                    // // 接続をチェック
                    // if ($conn->connect_error) {
                    //     //コンソールにエラーメッセージを表示
                    //     echo "Connection failed: " . $conn->connect_error;
                    //     $messages.array_push($messages, ["type" => "text", "text" =>  $conn->connect_error ]);
                    //     $messages.array_push($messages, ["type" => "text", "text" =>  "エラー" ]);
                    // } else {
                    //     $messages.array_push($messages, ["type" => "text", "text" =>  "つながりました" ]);
                    // }

                    // $output_lines = explode("\n", $answer);// 出力を一行ごとに分割して配列に格納

                    // $messages.array_push($messages, ["type" => "text", "text" =>  gettype($userId) ]);
                    // $sql = "INSERT INTO goals (user_id, goal1, goal2, goal3, goal4, goal5, goal6, goal7, goal8,continuity) VALUES (\"$userId\" , \"$output_lines[0]\", \"$output_lines[1]\", \"$output_lines[2]\", \"$output_lines[3]\", \"$output_lines[4]\", \"$output_lines[5]\", \"$output_lines[6]\", \"$output_lines[7]\", $continuity )";

                    // // プリペアドステートメントを使用してSQLインジェクションを防ぐ
                    // $stmt = $conn->prepare($sql);
                    // if ($stmt === false) {
                    //     //エラーメッセージを表示
                    //     echo "". $conn->error;
                    //     $messages.array_push($messages, ["type" => "text", "text" =>  $conn->error ]);
                    // } else {
                    //     $messages.array_push($messages, ["type" => "text", "text" =>  "実行" ]);
                    // }

                    // // パラメータをバインドする
                    // $stmt->bind_param("sssssssssi",$userId, $output_lines[0], $output_lines[1], $output_lines[2], $output_lines[3], $output_lines[4], $output_lines[5], $output_lines[6], $output_lines[7], $continuity);

                    // // SQL文を実行する
                    // if ($stmt->execute()) {
                    //     echo "New records created successfully";
                    //     $messages.array_push($messages, ["type" => "text", "text" =>  "実行成功" ]);
                    // } else {
                    //     echo "Error: " . $stmt->error;
                    //     $messages.array_push($messages, ["type" => "text", "text" =>  "実行エラー" ]);
                    // }
                    // // if ($conn->query($sql) === TRUE) {
                    // //     $messages.array_push($messages, ["type" => "text", "text" =>  "成功" ]);
                    // // } else {
                    // //     $messages.array_push($messages, ["type" => "text", "text" =>  "エラー" ]);
                    // // }

                    // // データベース接続を閉じる
                    // $conn->close();
                } else {
                    $messages.array_push($messages, ["type" => "text", "text" => "継続は力なり"]); // 適当にコメントを返す
                }

                

                

                
            }

        } else if($type == "sticker") { // メッセージがスタンプのとき
            $messages.array_push($messages, ["type" => "sticker", "packageId" => "446", "stickerId" => "1988"]); // 適当なステッカーを返す

        } else { // その他は無視．必要に応じて追加．
            return;
        }

        sendMessage([
            "replyToken" => $replyToken,
            "messages" => $messages
        ]);
    }
}
function getContinuity($userID)
{
    $local_continuity = -1;
    // データベースに接続    
    $conn = connectDB();

    // $userIdの目標1を取得
    $sql = "SELECT continuity FROM goals WHERE user_id = \"$userID\"";
    // プリペアドステートメントを使用してSQLインジェクションを防ぐ
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        //エラーメッセージを表示
        echo "". $conn->error;
    } else {
        // $messages.array_push($messages, ["type" => "text", "text" =>  "インジェクションのとこ成功" ]);
    }

    // パラメータをバインドする
    $stmt->bind_param("s", $userID);

    // SQL文を実行する
    if ($stmt->execute()) {
        // 結果をバインド
        $stmt->bind_result($continuity);
        
        // 結果をフェッチして表示
        $stmt->fetch();

        $local_continuity = $continuity;
    
    } else {
        echo "Error: " . $stmt->error;
    }   
    $conn->close();
    return $local_continuity;
}
function getgoaln($quotient, $userID)
{
    // データベースに接続
    $conn = connectDB();

    // $userIdの継続数に基づく目標を取得
    $goaln = "goal".((int)($$quotient / 3 + 1));
    $sql_goaln = "SELECT $goaln FROM goals WHERE user_id = \"$userID\"";


    // プリペアドステートメントを使用してSQLインジェクションを防ぐ
    $stmt_goaln = $conn->prepare($sql_goaln);
    if ($stmt_goaln === false) {
        // //エラーメッセージを表示
        // echo "". $conn->error;
        // $messages.array_push($messages, ["type" => "text", "text" =>  $conn->error ]);
    } else {
        // $messages.array_push($messages, ["type" => "text", "text" =>  "インジェクションのとこ成功" ]);
    }

    // パラメータをバインドする
    $stmt_goaln->bind_param("s", $userID);

    // SQL文を実行する
    if ($stmt_goaln->execute()) {
        // echo "New records created successfully";
        // $messages.array_push($messages, ["type" => "text", "text" =>  "実行成功" ]);
        // 結果をバインド
        $stmt_goaln->bind_result($goal);
        
        // 結果をフェッチして表示
        $stmt_goaln->fetch();
        // echo "取得成功";
        // $messages.array_push($messages, ["type" => "text", "text" =>  $goal ]);
    } else {
        echo "Error: " . $stmt_goaln->error;
        // $messages.array_push($messages, ["type" => "text", "text" =>  "実行エラー" ]);
    }   

    $conn->close();

    return $goal;

}
function getStatus($userID)
{
    // データベースに接続    
    $conn = connectDB();

    // $userIdの目標1を取得
    $sql_get_status = "SELECT status FROM goals WHERE user_id = \"$userId\""; //NULLだとエラーが出るので注意
    // プリペアドステートメントを使用してSQLインジェクションを防ぐ
    $stmt = $conn->prepare($sql_get_status);
    if ($stmt === false) {
        //エラーメッセージを表示
        echo "". $conn->error;
    } else {
        $messages.array_push($messages, ["type" => "text", "text" =>  "インジェクションのとこ成功" ]);
    }

    // パラメータをバインドする
    $stmt->bind_param("s", $userID);

    // SQL文を実行する
    if ($stmt->execute()) {
        // 結果をバインド
        $stmt->bind_result($status);
        
        // 結果をフェッチして表示
        $stmt->fetch();

    
    } else {
        echo "Error: " . $stmt->error;
        $messages.array_push($messages, ["type" => "text", "text" =>  $stmt->error ]);
    }   
    $conn->close();
    return $status;
}

function connectDB(){
    // データベースに接続
    // $messages.array_push($messages, ["type" => "text", "text" =>  $answer ]);
    $dbdsn = getenv("DB_DSN");
    $userName = getenv("DB_USER");
    $pass = getenv("DB_PASSWORD");
    $dbname = getenv("DB_NAME");
    
    $conn = new mysqli($dbdsn, $userName, $pass, $dbname);
                
    // // 接続をチェック
    // if ($conn->connect_error) {
    //     //コンソールにエラーメッセージを表示
    //     echo "Connection failed: " . $conn->connect_error;
    //     $messages.array_push($messages, ["type" => "text", "text" =>  $conn->connect_error ]);
    //     $messages.array_push($messages, ["type" => "text", "text" =>  "エラー" ]);
    // } else {
    //     $messages.array_push($messages, ["type" => "text", "text" =>  "つながりました" ]);
    // }

    return $conn;
}


main();