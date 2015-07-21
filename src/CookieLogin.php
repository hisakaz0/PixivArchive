<?php

function CookieLogin ( $cookie_file, $html_file ){ // cookie_fileでログインできるか?

  $url       = 'https://www.secure.pixiv.net/login.php';
  $dump_file = 'log/cookie_login.log';

  $handle = fopen($html_file, 'w');
  $ch = curl_init($url); // curlの初期設定
  curl_setopt($ch, CURLOPT_FILE, $handle);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // プレーンテキストで出力
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // redirectionを有効化
  curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file); // cookie情報を書き込む
  curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file); // cookie情報を読み取る
  $html_file = curl_exec($ch);
  fclose($handle);
  $info = curl_getinfo($ch); // 実行結果
  curl_close($ch); // curl終了

  $res = print_r($info, true);
  $handle = fopen($dump_file, 'w');
  fputs($handle, $res);
  fclose($handle);

  if ( $info['url'] == 'http://www.pixiv.net/'){ // 成功
    fputs(STDERR, "Your login is successful!\n");
    return 0;
  }else{ // 失敗
    fputs(STDERR, "Faile: your login...\n");
    fputs(STDERR, "Please pass a login with 'login.php' before execution $argv[0].\n");
    return 1;
  }
}
?>



