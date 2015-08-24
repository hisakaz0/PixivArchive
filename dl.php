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
$log_file = $dir . '/dl.log';
if ( ! MakeDirectory( $dir ) ){
  Msg( "error", "Couldn't make the directory " . $dir . "'\n" );
  exit( 1 );
}


# クッキーの処理
CookieLogin( );

# csvファイルの読み込み
$userlist = ReadCsv( $userlist_file );

# 童貞が喜ぶぐへへな画像をdl
# 世界の中心はここね❤
PixivArtWorkDownload( $userlist, $userlist_file );

exit( 0 );

?>

