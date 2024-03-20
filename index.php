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
            if($text == "地震") { // 「地震」というメッセージがユーザから来たとき
                $earthquake = getLatestEarthquake(); // 最新の地震情報を一件取得（api.phpの中に書いてある関数）$earthquakeの中身はここ→ https://www.p2pquake.net/json_api_v2/
                $messages.array_push($messages, [
                    "type" => "location",
                    "title" => "最新の地震情報",
                    "address" => "場所：" . $earthquake["hypocenter"]["name"],
                    "latitude" => $earthquake["hypocenter"]["latitude"],
                    "longitude" => $earthquake["hypocenter"]["longitude"]
                ]);
                // 一度に送れるメッセージは5件までなので注意
                $messages.array_push($messages, ["type" => "text", "text" => "最新の地震情報です。"]);
                $messages.array_push($messages, ["type" => "text", "text" => "場所は" . $earthquake["hypocenter"]["name"] . "です。"]);
                $messages.array_push($messages, ["type" => "text", "text" => "震度は" . $earthquake["hypocenter"]["magnitude"] . "です。"]);
                $messages.array_push($messages, ["type" => "text", "text" => "発生日時は" . $earthquake["time"] . "です。"]);
            } else {
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