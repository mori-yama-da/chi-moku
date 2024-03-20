<?php
require('vendor/autoload.php');
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

function callApi($url) {
    // この関数はいじらなくてOK
    $ch = curl_init(); //開始

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // 証明書の検証を行わない
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);  // curl_execの結果を文字列で返す

    $response =  curl_exec($ch);
    $result = json_decode($response, true);

    curl_close($ch); //終了

    return $result;
}

function getLatestEarthquake() {
    // この中身は好きに変えてOK
    $result = callApi("https://api.p2pquake.net/v2/jma/quake");
    $earthquake =  $result[0]["earthquake"]; // ここを変えると色々好きな情報を取ってこられる．https://www.p2pquake.net/json_api_v2/
    return $earthquake;
}