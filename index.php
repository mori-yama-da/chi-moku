<?php
//ライブラリの読み込み
require('api.php');
require('vendor/autoload.php');
require_once(dirname(__FILE__) . "/vendor/autoload.php");

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

            // 目標を達成したとき
            if($text == "ちいもく達成！") {
                //今日の日付を取得
                $today = date("Y-m-d");

                // データベースから前回の達成日時を取得
                $achievement_date = getAchievementDate($userId);

                //前回の達成日が今日でない場合
                if (/*$achievement_date != $today*/true) {
                    //データベースのcontinuityを更新
                    updateContinuity($userId);
                    //データベースからユーザの継続数を取得
                    $now_continuity = getContinuity($userId);
                    
                    //現在何個目の目標か
                    $quotient = (int)($now_continuity / 3);
                    //同じ目標を継続した日数
                    $remainder = (int)($now_continuity % 3);

                    //3回継続した場合，次の目標に移る
                    if ($remainder == 0) {
                        $messages.array_push($messages, ["type" => "text", "text" => "3回達成しました！" ]);

                        //目標達成時の画像名
                        $completion_goaln = "achieve_goal".((int)($quotient));

                        //データベースから目標を取得
                        $goal = getGoal($userId);
                        
                        //最終目標達成以外を達成したとき
                        if($completion_goaln < 8) {
                            //データベースから次の目標を取得
                            $goaln = getGoaln($now_continuity, $userId);
                            //データベースから前回の目標を取得
                            $old_goaln = getGoaln($now_continuity - 1, $userId);
                            //最終目標達成時のメッセージ送信
                            $messages.array_push($messages, ["type" => "text", "text" => $old_goaln . "\nを達成しました！" ]);
                            //最終目標達成時の画像送信
                            $messages.array_push($messages, ["type" => "image", "originalContentUrl" => "https://www3.yoslab.net/~ida/chatbot_www3_ida/img/" . $completion_goaln . ".png", "previewImageUrl" => "https://www3.yoslab.net/~ida/chatbot_www3_ida/img/" . $completion_goaln . ".png"]);
                            //次の目標入力を促すメッセージの送信
                            $messages.array_push($messages, ["type" => "text", "text" => "次の目標は\n" . $goaln . "\nです！引き続き頑張りましょう" ]);
                        }
                        //最終目標達成時
                        else {
                            //小目標達成時のメッセージ送信
                            $messages.array_push($messages, ["type" => "text", "text" => "おめでとうございます\n" . $goal . "\nを達成しました！" ]);
                            //目標達成時の画像送信
                            $messages.array_push($messages, ["type" => "image", "originalContentUrl" => "https://www3.yoslab.net/~ida/chatbot_www3_ida/img/" . $completion_goaln . ".png", "previewImageUrl" => "https://www3.yoslab.net/~ida/chatbot_www3_ida/img/" . $completion_goaln . ".png"]);
                            //次の目標を通知
                            $messages.array_push($messages, ["type" => "text", "text" => "次の目標を設定しよう！\nメニューから登録できるよ！" ]);
                        } 
                                            
                       
                    }
                    //継続回数が1回目であることを通知
                    else if ($remainder == 1) {
                        $messages.array_push($messages, ["type" => "text", "text" => "1回達成しました！" ]);
                        $messages.array_push($messages, ["type" => "text", "text" => "あと2回達成で次の目標です" ]);
                    }
                    //継続回数が2回目であることを通知
                    else if($remainder == 2) {
                        $messages.array_push($messages, ["type" => "text", "text" => "2回達成しました！" ]);
                        $messages.array_push($messages, ["type" => "text", "text" => "あと1回達成で次の目標です" ]);
                    }
                    //通るはずのない道
                    else {
                        //これには五条悟もぶち切れ
                        $messages.array_push($messages, ["type" => "text", "text" => "んなわけねぇだろ！！" ]);
                    }

                    //前回の達成日を今日にする
                    setAchievementDate($userId);
                    
                    /** 動作確認してないから問題あるならここ **/
                    // // データベースに接続
                    // $conn = connectDB();
                    // $sql = "UPDATE goals SET achievement_date = \"$today\" WHERE user_id = \"$userId\"";
                    // $stmt = $conn->prepare($sql);
                    // $stmt->bind_param("s", $today);
                    // $stmt->execute();
                    // $conn->close();

                } else {
                    $messages.array_push($messages, ["type" => "text", "text" =>  "今日はもう達成しています！" ]);
                }

            } 
            else if($text == "目標を確認したい") { // 「現状確認」というメッセージがユーザから来たとき
                //ユーザIDが存在するか確認
                $userId_count = countUserID($userId);
                
                //ユーザIDが存在しない場合
                if ($userId_count == 0) {
                    $messages.array_push($messages, ["type" => "text", "text" => "まずは目標を設定してね"]); // 目標入力を促す
                } else {
                    //データベースからユーザの達成度合いを取得
                    $continuity = getContinuity($userId);
                    //ユーザが目標を達成しているか確認
                    if ($continuity >= 24) {
                        $messages.array_push($messages, ["type" => "text", "text" => "目標を達成したよ\n新しい目標を設定してね"]); // 達成していた場合，新たな目標入力を促す
                    } else {
                        //データベースから目標を取得
                        $goal = getGoal($userId);
                        //データベースから現在の小目標を取得
                        $goaln = getGoaln($continuity, $userId);
                        //現在の目標を通知
                        $messages.array_push($messages, ["type" => "text", "text" =>  "現在の目標は\n" . $goal  ]);
                        $messages.array_push($messages, ["type" => "text", "text" =>  $goaln  ]);

                        //目標達成途中の画像を送信
                        $going_goaln = "going_goal".((int)($continuity / 3 + 1));
                        $messages.array_push($messages, ["type" => "image", "originalContentUrl" => "https://www3.yoslab.net/~ida/chatbot_www3_ida/img/" . $going_goaln . ".png", "previewImageUrl" => "https://www3.yoslab.net/~ida/chatbot_www3_ida/img/" . $going_goaln . ".png"]);
                    }
                }
            } 
            else if ($text == "目標を入力したい") { // 「目標変更」というメッセージがユーザから来たとき
                //ユーザIDが存在するか確認
                $userId_count = countUserID($userId);

                //ユーザIDが存在しない場合
                if ($userId_count == 0) {
                    // // データベースに接続    
                    // $conn = connectDB();
                    
                    // // $userIdとstatusをデータベースに登録
                    // $sql_add_user = "INSERT INTO goals (user_id, status) VALUES (\"$userId\", 1)";

                    // // プリペアドステートメントを使用してSQLインジェクションを防ぐ
                    // $stmt = $conn->prepare($sql_add_user);
                    // // パラメータをバインドする
                    // $stmt->bind_param("s", $userId);
                    // // SQL文を実行する
                    // $stmt->execute();
                    // //データベース接続を閉じる
                    // $conn->close();
                    $messages.array_push($messages, ["type" => "text", "text" => "目標を入力してね"]); // 目標入力を促す
                } else {
                    //フレックスメッセージをjsonファイルから取得
                    //$changeConfirmation_json = file_get_contents('flexMessages/changeConfirmation_postback.json');
                    $changeConfirmation_json = file_get_contents('flexMessages/changeConfirmation.json');
                    //JSONをPHPの配列に変換
                    $changeConfirmationMessage = json_decode($changeConfirmation_json);
                    
                    //変更していいかどうかをフレックスメッセージで確認
                    $messages.array_push($messages, $changeConfirmationMessage);
                }
            } 
            else if ($text == '変更する') {
                //statusを1に変更
                setStatus($userId, 1);
                $messages.array_push($messages, ["type" => "text", "text" => "目標を入力してね"]); // 目標入力を促す
            }
            else if ($text == '変更しない') {
                $messages.array_push($messages, ["type" => "text", "text" => "引き続き目標達成に向けてガンバ！"]); // 適当にコメントを返す
            }
            else if ($text == '使い方を教えて') {
                //$messages.array_push($messages, ["type" => "text", "text" => "ちいもくは．．．説明めんどくさいな"]); // 適当にコメントを返す
                //フレックスメッセージをjsonファイルから取得
                $howToUse_json = file_get_contents('flexMessages/howToUse.json');
                //JSONをPHPの配列に変換
                $howToUseMessage = json_decode($howToUse_json);
                
                //使い方をフレックスメッセージで説明
                $messages.array_push($messages, $howToUseMessage);
            }
            else {
                //statusを確認
                $status = getStatus($userId);
                //satausが1（目標入力状態）の場合，目標を設定
                if ($status == 1) {
                    //入力された目標を小さくしてデータベースに登録
                    $messages.array_push($messages, ["type" => "text", "text" => "目標：". $text . "を分割します！"]); // 適当にコメントを返す
                    //データベースに目標を登録
                    $answer = setGoals($userId,$text);
                    
                    //statusを0に変更
                    setStatus($userId, 0);
                    $messages.array_push($messages, ["type" => "text", "text" => $answer]);
                    
                    //目標設定時の画像送信
                    $messages.array_push($messages, ["type" => "image", "originalContentUrl" => "https://www3.yoslab.net/~ida/chatbot_www3_ida/img/set_goals.png", "previewImageUrl" => "https://www3.yoslab.net/~ida/chatbot_www3_ida/img/set_goals.png" ]);
                    
                    //goal1を通知
                    $goaln = getGoaln(0, $userId);
                    $messages.array_push($messages, ["type" => "text", "text" => "初めの目標は\n" . $goaln . "です．\n目標達成に向けて頑張りましょう！"]);
                           
                } else {
                    //適当に励ますコメントを返す
                    // 励ましのコメントリスト
                    $comments = [
                        "継続は力なり！頑張り続ければ、きっと良い結果が待っています。",
                        "コツコツと積み重ねることが、大きな成果につながります。",
                        "毎日少しずつ進歩を重ねていきましょう。継続は力なり！",
                        "挑戦を続けることで、自分自身の成長につながります。",
                        "一歩一歩前に進むことが、大きな飛躍への道です。"
                    ];

                    // ランダムにコメントを選んで送信
                    $selected_comment = $comments[array_rand($comments)];
                    $messages.array_push($messages, ["type" => "text", "text" => $selected_comment]);
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

/**
 * データベースの継続回数を+1更新
 * データベース上で$userIdと一致するユーザのcontinuityを+1する
 * 
 * @param string $userId
 * @return void
 */
function updateContinuity($userId)
{
    // データベースに接続    
    $conn = connectDB();

    // $userIdのcontinuityに+1する
    $sql = "UPDATE goals SET continuity = continuity + 1 WHERE user_id = \"$userId\"";

    // プリペアドステートメントを使用してSQLインジェクションを防ぐ
    $stmt = $conn->prepare($sql);

    // パラメータをバインドする
    $stmt->bind_param("s", $userId);

    // SQL文を実行する
    $stmt->execute();

    //データベース接続を閉じる
    $conn->close();
}

/**
 * データベースの継続回数を取得
 * データベース上で$userIdと一致するユーザのcontinuityを取得して返す
 * 
 * @param string $userId
 * @return int $continuity
 */
function getContinuity($userID)
{
    // データベースに接続    
    $conn = connectDB();

    // $userIdと一致するユーザのcontinuityを取得
    $sql = "SELECT continuity FROM goals WHERE user_id = \"$userID\"";
    // プリペアドステートメントを使用してSQLインジェクションを防ぐ
    $stmt = $conn->prepare($sql);

    // パラメータをバインドする
    $stmt->bind_param("s", $userID);

    // SQL文を実行する
    $stmt->execute();

    // 結果をバインド
    $stmt->bind_result($continuity);
        
    // 結果をフェッチ
    $stmt->fetch();
    
    //データベース接続を閉じる
    $conn->close();

    //継続回数を返す
    return $continuity;
}

/**
 * データベースの目標を取得
 * データベース上で$userIdと一致するユーザの$continuityに基づく目標を取得して返す
 * 
 * @param int $continuity
 * @param string $userId
 * @return string $goal
 */
function getGoaln($continuity, $userID)
{
    // データベースに接続
    $conn = connectDB();

    // $userIdの継続数に基づく目標を取得
    $goaln = "goal".((int)($continuity / 3 + 1));
    $sql_goaln = "SELECT $goaln FROM goals WHERE user_id = \"$userID\"";


    // プリペアドステートメントを使用してSQLインジェクションを防ぐ
    $stmt_goaln = $conn->prepare($sql_goaln);
    
    // パラメータをバインドする
    $stmt_goaln->bind_param("s", $userID);

    // SQL文を実行する
    $stmt_goaln->execute();

    // 結果をバインド
    $stmt_goaln->bind_result($goal);
        
    // 結果をフェッチ
    $stmt_goaln->fetch();

    //データベース接続を閉じる
    $conn->close();

    //目標を返す
    return $goal;

}
/**
 * データベースの目標を取得
 * データベース上で$userIdと一致するユーザの$continuityに基づく目標を取得して返す
 * 
 * @param string $userId
 * @return string $goal
 */
function getGoal($userID)
{
    // データベースに接続
    $conn = connectDB();

    // $userIdの継続数に基づく目標を取得
    $sql_goal = "SELECT goal FROM goals WHERE user_id = \"$userID\"";


    // プリペアドステートメントを使用してSQLインジェクションを防ぐ
    $stmt_goaln = $conn->prepare($sql_goal);
    
    // パラメータをバインドする
    $stmt_goaln->bind_param("s", $userID);

    // SQL文を実行する
    $stmt_goaln->execute();

    // 結果をバインド
    $stmt_goaln->bind_result($goal);
        
    // 結果をフェッチ
    $stmt_goaln->fetch();

    //データベース接続を閉じる
    $conn->close();

    //目標を返す
    return $goal;

}

/**
 * データベースのstatusを取得
 * データベース上で$userIdと一致するユーザのstatusを取得して返す
 * 
 * @param string $userId
 * @return int $status
 */
function getStatus($userId)
{
    // データベースに接続    
    $conn = connectDB();

    // $userIdと一致するユーザのstatusを取得
    $sql_get_status = "SELECT status FROM goals WHERE user_id = \"$userId\""; //NULLだとエラーが出るので注意
    // プリペアドステートメントを使用してSQLインジェクションを防ぐ
    $stmt = $conn->prepare($sql_get_status);

    // パラメータをバインドする
    $stmt->bind_param("s", $userId);

    // SQL文を実行する
    $stmt->execute();

    // 結果をバインド
    $stmt->bind_result($status);
    
    // 結果をフェッチ
    $stmt->fetch();
    
    //データベース接続を閉じる
    $conn->close();

    //statusを返す
    return $status;
}

/**
 * データベースに目標を登録
 * 目標をChatGPTによって小目標に分割し，
 * データベース上で$userIdと一致するユーザの目標・小目標を登録する．
 * 一応分割された目標を返す．
 * 
 * @param string $userId
 * @param string $goal
 * @return string $answer
 */
function setGoals($userId, $goal)
{
    //プロンプトをテキストファイルから取得
    // $prompt_txt = file_get_contents('prompt.txt');
    //プロンプトを作成
    // $prompt = $prompt_txt . $goal;

    //プロンプトを作成
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
        \n\n目標：" . $goal;

    // chatGPTにプロンプトを送信して返答を取得
    // $answer = call_chatGPT($prompt);
    $answer = "小目標1：運動用のマットを購入するsmile\n小目標2：運動のための時間を設定するhappy\n小目標3：運動着を用意するsmile\n小目標4：ストレッチを始める\n小目標5：腹筋運動を10回行う\n小目標6：腹筋運動を30回行う\n小目標7：腹筋運動を50回行う\n小目標8：腹筋運動を100回行う";
    
    
    // データベースに接続    
    $conn = connectDB();

    // 小目標を一行ごとに分割して配列に格納
    $output_lines = explode("\n", $answer);

    // 達成日を初期化
    $ancient_date = '2024-03-03';

    // $userIdと一致するユーザの目標・小目標，達成日の初期値を登録
    $sql = "UPDATE goals SET goal = \"$goal\", goal1 = \"$output_lines[0]\", goal2 = \"$output_lines[1]\", goal3 = \"$output_lines[2]\", goal4 = \"$output_lines[3]\", goal5 = \"$output_lines[4]\", goal6 = \"$output_lines[5]\", goal7 = \"$output_lines[6]\", goal8 = \"$output_lines[7]\", continuity = 0, achievement_date = \"$ancient_date\" WHERE user_id = \"$userId\"";
    
    // プリペアドステートメントを使用してSQLインジェクションを防ぐ
    $stmt = $conn->prepare($sql);

    // パラメータをバインドする
    $stmt->bind_param("sssssssssss", $userId, $goal, $output_lines[0], $output_lines[1], $output_lines[2], $output_lines[3], $output_lines[4], $output_lines[5], $output_lines[6], $output_lines[7], $ancient);

    // SQL文を実行する
    $stmt->execute();

    // データベース接続を閉じる
    $conn->close();

    //分割された目標を返す
    return $answer;
}

/**
 * 初期値を設定
 * ユーザIDが存在しないユーザに対して，IDとstatusをデータベースに登録する
 * 
 * @param string $userId
 * @return void
 */
function setInitialValue($userId) {
    // データベースに接続    
    $conn = connectDB();
    
    // $userIdとstatusをデータベースに登録
    $sql_add_user = "INSERT INTO goals (user_id, status) VALUES (\"$userId\", 1)";

    // プリペアドステートメントを使用してSQLインジェクションを防ぐ
    $stmt = $conn->prepare($sql_add_user);

    // パラメータをバインドする
    $stmt->bind_param("s", $userId);

    // SQL文を実行する
    $stmt->execute();

    //データベース接続を閉じる
    $conn->close();
}

/**
 * データベースのstatusを変更
 * データベース上で$userIdと一致するユーザのstatusを変更する
 * 
 * @param string $userId
 * @param int $status
 * @return void
 */
function setStatus($userId, $status)
{
    // データベースに接続    
    $conn = connectDB();

    // $userIdと一致するユーザのstatusを変更
    $sql_set_status = "UPDATE goals SET status = \"$status\" WHERE user_id = \"$userId\""; //NULLだとエラーが出るので注意

    // プリペアドステートメントを使用してSQLインジェクションを防ぐ
    $stmt = $conn->prepare($sql_set_status);

    // パラメータをバインドする
    $stmt->bind_param("s", $userId);

    // SQL文を実行する
    $stmt->execute();

    // 結果をバインド
    $stmt->bind_result($status);
    
    // 結果をフェッチ
    $stmt->fetch();
    
    //データベース接続を閉じる  
    $conn->close();
}

/**
 * データベース上のあるuserIdの数を取得
 * データベース上で$userIdと一致するユーザの数を取得して返す
 * 
 * @param string $userId
 * @param int $status
 * @return int $userId_count
 */
function countUserID($userId)
{
    // データベースに接続    
    $conn = connectDB();
                
    // $userIdがDBに存在するか確認
    $sql_count_userID = "SELECT COUNT(*) FROM goals WHERE user_id = \"$userId\"";

    // プリペアドステートメントを使用してSQLインジェクションを防ぐ
    $stmt = $conn->prepare($sql_count_userID);
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
    //userIdの数を返す
    return $userId_count;
}


/**
 * データベースから目標達成日を取得
 * データベース上で$userIdと一致するユーザの目標を取得して返す
 * 
 * @param string $userId
 * @return string $goal
 */
function getAchievementDate($userId) {
    // データベースから前回の達成日時を取得
    $conn = connectDB();

    // $userIdと一致するユーザの達成日を取得
    $sql = "SELECT achievement_date FROM goals WHERE user_id = \"$userId\"";

    // プリペアドステートメントを使用してSQLインジェクションを防ぐ
    $stmt = $conn->prepare($sql);

    // パラメータをバインドする
    $stmt->bind_param("s", $userId);

    // SQL文を実行する
    $stmt->execute();

    // 結果をバインド
    $stmt->bind_result($achievement_date);

    // 結果をフェッチ
    $stmt->fetch();

    //データベース接続を閉じる
    $conn->close();

    //達成日を返す
    return $achievement_date;
}

/**
 * データベースの目標達成日を変更
 * データベース上で$userIdと一致するユーザの目標達成日を今日に変更する
 * 
 * @param string $userId
 * @return void
 */
function setAchievementDate($userId) {
    //今日の日付を取得
    $today = date("Y-m-d");

    // データベースに接続
    $conn = connectDB();

    // $userIdと一致するユーザの達成日を今日に変更
    $sql = "UPDATE goals SET achievement_date = \"$today\" WHERE user_id = \"$userId\"";

    // プリペアドステートメントを使用してSQLインジェクションを防ぐ
    $stmt = $conn->prepare($sql);

    // パラメータをバインドする
    $stmt->bind_param("s", $today);

    // SQL文を実行する
    $stmt->execute();

    //データベース接続を閉じる
    $conn->close();
}

/**
 * データベースに接続
 * データベースに接続するための情報を環境変数から取得し，データベースに接続する
 * 
 * @return mysqli $conn
 */
function connectDB(){
    // データベースに接続するための情報
    $dbdsn = getenv("DB_DSN");
    $userName = getenv("DB_USER");
    $pass = getenv("DB_PASSWORD");
    $dbname = getenv("DB_NAME");
    
    // データベースに接続
    $conn = new mysqli($dbdsn, $userName, $pass, $dbname);

    return $conn;
}

//mainを実行
main();