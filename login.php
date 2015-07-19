#!/usr/bin/php

<?php
function login($pixiv_id, $password, $cookie_file){

  $url   = 'https://www.secure.pixiv.net/login.php';
  $param = array( // loginするのためのparameters
    'mode' => 'login',
    'pixiv_id' => $pixiv_id,
    'pass' => $password,
    'submit' => 'ログイン'
  );

  $dump_file = 'log/login.log';
  $html_file = 'log/login.html';

  //  cookieの取得
  $handle = fopen($html_file, 'w');
  $ch = curl_init($url); // curlの初期設定
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // redirectionを有効化
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // プレーンテキストで出力
  curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file); // cookie情報を保存する
  curl_setopt($ch, CURLOPT_POST, true); // postを行う
  curl_setopt($ch, CURLOPT_POSTFIELDS, $param); // postするデータを設定
  curl_setopt($ch, CURLOPT_FILE, $handle);
  $html_file = curl_exec($ch);
  fclose($handle);
  $info = curl_getinfo($ch); // 実行結果
  curl_close($ch); // curl終了

  $res = print_r($info, true);
  $handle = fopen($dump_file, 'w');
  fputs($handle, $res);
  fclose($handle);


  if ( $info['url'] == 'http://www.pixiv.net/'){
    fputs(STDERR, "Your login is successful!.\n");
    fputs(STDERR, "Cookie file is " . dirname(__file__) . "/" . $cookie_file ."\n");
  }else{
    fputs(STDERR, "Failed your login...\n");
  }
}

// main

if ( $argv[2] == '' ){
  fputs( STDERR, "usage: $argv[0] <pixiv_id> <password> [cookie_file]\n" );
  exit( 1 );
}

if ( $argv[3] == '' ){ // クッキーファイルに指定がない場合
  $cookie_file = 'cookie.txt'; // デフォルト
} else {
  $cookie_file = $argv[3]; //指定されたクッキーファイル
}

$pixiv_id = $argv[1];
$password = $argv[2];

login( $pixiv_id, $password, $cookie_file );

?>

