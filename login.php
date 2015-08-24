#!/usr/bin/php
<?php

require_once dirname(__file__) . '/src/PixivArtWorkDownload.php';

function login($pixiv_id, $password ){

  global $cookie_file;
  $url   = 'https://www.secure.pixiv.net/login.php';
  $param = array( // loginするのためのparameters
    'mode' => 'login',
    'pixiv_id' => $pixiv_id,
    'pass' => $password,
    'submit' => 'ログイン',
    'skip' => '1'
  );


  date_default_timezone_set('Asia/Tokyo');
  $session_id = date('ymdHis'); // セッションごとにlogフォルダを生成

  $dump_file = 'log/login/login_' . $session_id . '.log';
  $html_file = 'log/login/login_' . $session_id . '.html';

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
    Msg( 0, "Your login is successful!.\n");
    Msg( 0, "Cookie file is " . dirname(__file__) . "/" . $cookie_file ."\n");
  }else{
    Msg( 0, "Failed your login...\n");
  }
}

// main

list( // パラメータの設定
  $image_dir,
  $link_dir,
  $cookie_file,
  $userlist_file
) = SetParam();


$dir = 'log/login/';
if ( ! MakeDirectory( $dir ) ){
  Msg( "error", "Couldn't make the directory " . $dir . "'\n" );
  exit( 1 );
}
date_default_timezone_set( 'Asia/Tokyo' );
$log_file =  $dir . date( 'ymdHis' ) . '.log';


if ( $argc != 3 ){
  Msg( 0, "usage: $argv[0] <pixiv_id> <password>\n" );
  exit( 1 );
}


$pixiv_id = $argv[1];
$password = $argv[2];

login( $pixiv_id, $password );

?>
