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

exit;


// ログフォルの作成
date_default_timezone_set( 'Asia/Tokyo' );
$session_id = date( 'ymdHis' );
mkdir( 'log/dl/' . $session_id, 0777, true );
$log_file = 'log/dl/' . $session_id . '/dl.log';


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

?>

