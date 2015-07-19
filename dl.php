#!/usr/bin/php

<?php

require_once dirname(__file__) . '/src/CookieLogin.php';
require_once dirname(__file__) . '/src/ReadCsv.php';
require_once dirname(__file__) . '/src/PixivArtWorkDownload.php';

$cookie_file            = $argv[1]; // login.phpで作成したcookie_file
$userlist_file          = $argv[2]; // imageをdlをするための設定ファイル群
$cookie_login_html_file = 'log/cookie_login.html'; // ダンプファイル

// Error Caught / ハートキャッチプリキュア
if ( $argv[2] == '' ){
  fputs(STDERR, "usage: $argv[0] <cookie_file> <userlist_file>\n");
  fputs(STDERR, "Login to pixiv with 'login.php' before execution $argv[0].\n");
  exit( 1 );
}

if ( ! file_exists( $argv[1] ) ){
  fputs(STDERR, "The '$argv[1] is not exist!\n");
  exit( 1 );
}

if ( ! file_exists( $argv[2] ) ){
  fputs(STDERR, "The '$argv[2] is not exist!\n");
  exit( 1 );
}


# クッキーの処理
if ( CookieLogin( $cookie_file, $cookie_login_html_file ) ){
  fputs(STDERR, "Interrupt: the '$argv[0]' execution\n");
  exit( 1 );
}


# csvファイルの読み込み
// $userlist = ReadCsv( $userlist_file );

# 童貞が喜ぶぐへへな画像をdl
# 世界の中心はここね❤
// PixivArtWorkDownload( $userlist, $cookie_file );


$user_id = '2476217';
$cookie_file = 'cookie.txt';
// $artwork_id = '45233363'; // illust
$artwork_id = '41760306'; // manga
// UserCheck( $user_id, $cookie_file );
// GetFirstArtWorkId( $user_id, '1', $cookie_file );
DownloadArtWork( $artwork_id, $cookie_file );

?>

