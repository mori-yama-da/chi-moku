<?php
require('vendor/autoload.php');
require_once(dirname(__FILE__) . "/vendor/autoload.php"); //ライブラリの読み込み
/* 環境変数の読み込み */
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
/* ***** */

function sendMessage($post_data) {
    // この関数はいじらなくてOK
    $accessToken = getenv('CHANNEL_ACCESS_TOKEN');
    $ch = curl_init("https://api.line.me/v2/bot/message/reply");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
     'Content-Type: application/json; charser=UTF-8',
     'Authorization: Bearer ' . $accessToken
     ));
    $result = curl_exec($ch);
    curl_close($ch);
}


// この関数だけ使うことも可能
// 引数にメッセージを入力して使う
// 例：call_chatGPT('おはよう')
function call_chatGPT($prompt) {
    $OPENAI_API_KEY = getenv("CHATGPT_API_KEY");

    $ch = curl_init();
    $headers  = [
        'Accept: application/json',
        'Content-Type: application/json',
        'Authorization: Bearer ' . $OPENAI_API_KEY
    ];

    // 送るメッセージや使うモデルを設定
    // max_tokens 省略したらエラーが戻ってきたので入れといたほうがいいかも
    $postData = [
        'model' => "gpt-4-1106-preview",// "gpt-3.5-turbo",
        'messages' => [
            [
                'role' => 'user',
                'content' => $prompt,
            ]
        ],
        'max_tokens' => 3000,
    ];

    // データを送信
    curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    // 成功した場合はメッセージを失敗した場合はfalseを返す

    $result = curl_exec($ch);

    if ($result === false) {
        return false;
    }

    $decoded_json = json_decode($result, true);
    return $decoded_json["choices"][0]["message"]["content"];
};

function writeLog_gpt($institution, $login_num, $message_from_gpt){
    $txt_name = $institution . $login_num;
    $filename = './log/gpt/'. $txt_name .'.txt';

    // ファイルを開く（'w'は書き込みモード）
    $fp = fopen($filename, 'a');

    // ファイルに書き込む
    $data = "\n". $institution ."\n". $login_num ."\n" . date('Y-m-d H:i:s') ."\n";
    $data = $data. $message_from_gpt ."\n"."\n"."\n"."\n";
    fputs($fp, $data);

    // ファイルを閉じる
    fclose($fp);
}
