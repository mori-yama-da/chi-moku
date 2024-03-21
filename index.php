<?php
require('api.php');

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
            if($text == "目標達成") { // 「地震」というメッセージがユーザから来たとき
                $userId = $event->{'source'}->{'userId'};
                //if (!empty($variable)) {
                //    $messages.array_push($messages, ["type" => "text", "text" => "現状確認" ]);// 変数が空でない場合の処理
                //} else {
                //    $messages.array_push($messages, ["type" => "text", "text" => $text ]);// 変数が空の場合の処理
                //}
                
                $messages.array_push($messages, ["type" => "text", "text" => $userId ]);
            } else if($text == "現状確認") { // 「地震」というメッセージがユーザから来たとき
                
            } else if($text == "目標変更") { // 「地震」というメッセージがユーザから来たとき
                
            } else {
                $userId = $event->{'source'}->{'userId'};
                $pronpt = "あなたは私のコーチです．
                    \n目標：毎日1km以上ランニングする
                    \n\n小目標1：靴を履く
                    \n小目標2：外に出る
                    \n小目標3：10歩歩く
                    \n小目標4：10歩走る
                    \n小目標5：100歩歩く
                    \n小目標6：100歩走る
                    \n小目標7：500歩歩く
                    \n小目標8：500歩走る
                    \n小目標9：1km歩く
                    \n小目標10：1km走る
                    \n\n目標：毎日3時間以上英語の勉強をする
                    \n\n小目標1：毎日5分間、教科書を開いて眺める
                    \n小目標2：毎日5分間、単語リストを読む
                    \n小目標3：毎日5分間、英語での聞き取り練習をする
                    \n小目標4：毎日1つの新しい単語を学び、ノートに記録する
                    \n小目標6：毎日5分間、簡単な英語の記事を読む
                    \n小目標7：毎日英語で日記を1文書く
                    \n小目標8：毎日5分間、英語で音読する
                    \n小目標9：毎日10分間の英語学習セッションを行う
                    \n小目標10：毎日15分間の英語学習セッションを行う
                    \n\n目標：腹筋100回する
                    \n\n小目標1：運動用のマットを購入する
                    \n小目標2：運動のための時間を設定する
                    \n小目標3：運動着を用意する
                    \n小目標4：ストレッチを始める
                    \n小目標5：腹筋運動を10回行う
                    \n小目標6：腹筋運動を20回行う
                    \n小目標7：腹筋運動を30回行う
                    \n小目標8：腹筋運動を50回行う
                    \n小目標9：腹筋運動を70回行う
                    \n小目標10：腹筋運動を100回行う
                    \n\nこのように，目標に向けて，笑えるくらい初歩的なステップから始め，各小目標を3日間継続できたら次のステップに進み，前の小目標は行わない，もしくは取り入れた形で10の小目標を設定してください．最終的には小目標10で設定した目標を達成できるようにしてください．また，抽象的な小目標ではなく，数値目標にしてください．前置きは80字程度にしてください．
                    \n\n上記のことを踏まえて，下記の目標達成に向けて小目標を設定してください．
                    \n\n目標：" . $text;
                // $answer = call_chatGPT($pronpt); // chatGPTにメッセージを送信して返答を取得
                // $messages.array_push($messages, ["type" => "text", "text" =>  $answer ]);
                $messages.array_push($messages, ["type" => "text", "text" => $text]); // 適当にオウム返し
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

main();