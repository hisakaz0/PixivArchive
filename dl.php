#!/usr/bin/php
<?php

require_once dirname(__file__) . '/src/CookieLogin.php';
require_once dirname(__file__) . '/src/Csv.php';
require_once dirname(__file__) . '/src/PixivArtWorkDownload.php';


list( // パラメータの設定
  $image_dir,
  $link_dir,
  $cookie_file,
  $userlist_file
) = SetParam();


// ログフォルの作成
date_default_timezone_set( 'Asia/Tokyo' );
$dir = 'log/dl/' . date( 'ymdHis' );
if ( ! MakeDirectory( $dir ) ){
  Msg( "error", "Couldn't make the directory " . $dir . "'\n" );
  exit( 1 );
}
$log_file = 'log/dl/' . $session_id . '/dl.log';


# クッキーの処理
CookieLogin( );

# csvファイルの読み込み
$userlist = ReadCsv( $userlist_file );

# 童貞が喜ぶぐへへな画像をdl
# 世界の中心はここね❤
PixivArtWorkDownload( $userlist, $userlist_file );

exit( 0 );

?>

