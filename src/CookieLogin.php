<?php


function CookieLogin (){ // cookie_fileでログインできるか?

  global $session_id;

  $url           = 'https://www.secure.pixiv.net/login.php';
  $log_file_name = 'cookie_login';

  list( $html, $info ) = @Curl( $url, $log_file_name ); // urlからcontentを引っ張ってくる
  HtmlDump( $html, $log_file_name );

  if ( $info['url'] == 'http://www.pixiv.net/' ){ // 成功
    fputs( STDERR, "Your login is successful!\n" );
    return 0;
  }else{ // 失敗
    fputs( STDERR, "Faile: your login...\n" );
    fputs( STDERR,
      "Please pass a login with 'login.php' before execution $argv[0].\n" );
    return 1;
  }
}
?>
