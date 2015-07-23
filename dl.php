#!/usr/bin/php

<?php

require_once dirname(__file__) . '/src/CookieLogin.php';
require_once dirname(__file__) . '/src/Csv.php';
require_once dirname(__file__) . '/src/PixivArtWorkDownload.php';
require_once dirname(__file__) . '/lib/ansi-color.php';

use PhpAnsiColor\Color;

$cookie_file            = $argv[1]; // login.phpで作成したcookie_file
$userlist_file          = $argv[2]; // imageをdlをするための設定ファイル群

date_default_timezone_set( 'Asia/Tokyo' );
$session_id = date( 'ymdHis' );
mkdir( 'log/dl/' . $session_id, 0777, true );
$log_file = 'log/dl/' . $session_id . '/dl.log';

if ( ! file_exists( '.images' ) ){ // フォルダが作られているか
  mkdir( '.images', 0777, true );
}

// Error Caught / ハートキャッチプリキュア
if ( $argv[2] == '' ){
  Msg( 0, "usage: $argv[0] <cookie_file> <userlist_file>\n" );
  Msg( 0, "Login to pixiv with 'login.php' before execution $argv[0].\n" );
  exit( 1 );
}

if ( ! file_exists( $argv[1] ) ){
  Msg( 'error', "The '$argv[1]' is not exist!\n" );
  exit( 1 );
}

if ( ! file_exists( $argv[2] ) ){
  Msg( "error", "The '$argv[2]' is not exist!\n" );
  exit( 1 );
}

# クッキーの処理
if ( CookieLogin( ) ){
  Msg( "interrupt", "the '$argv[0]' execution.\n" );
  exit( 1 );
}

# csvファイルの読み込み
$userlist = ReadCsv( $userlist_file );

# 童貞が喜ぶぐへへな画像をdl
# 世界の中心はここね❤
PixivArtWorkDownload( $userlist, $userlist_file );

exit( 0 );

function Msg( $type, $msg ){

  global $log_file;

  $ann = array(
    'started'   => Color::set("Started", "yellow+bold"),
    'succeed'   => Color::set("Succeed", "green+bold"),
    'error'     => Color::set("Error", "red+bold+underline"),
    'interrupt' => Color::set("Interrupt", "blue+bold")
  );

  @$out = $ann["$type"] . ": " . $msg;
  fputs( STDERR, $out );

  @$out = $type . ": " . $msg;
  $handle = fopen( $log_file, 'a' );
  fputs( $handle, $out);
  fclose( $handle );

}
?>

