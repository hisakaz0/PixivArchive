<?php


function CookieLogin (){ // cookie_fileでログインできるか?

  global $session_id;

  $url           = 'https://www.secure.pixiv.net/login.php';
  $log_file_name = 'cookie_login';

  list( $html, $info ) = @Curl( $url ); // urlからcontentを引っ張ってくる
  HtmlDump( $html, $log_file_name );

  if ( $info['url'] == 'http://www.pixiv.net/' ){ // 成功
    Msg( 0, "Your login is successful!\n" );
    return 0;
  }else{ // 失敗
    Msg( 'error', "Failed your login...\n", 'error' );
    Msg( 0, "Please pass a login with 'login.php' before execution $argv[0].\n" );
    exit( 1 ); // 失敗したらオシマイ
  }
}
?>
