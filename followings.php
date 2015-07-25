#!/usr/bin/php
<?php

require dirname(__file__) . '/src/PixivArtWorkDownload.php';

$cookie_file = $argv[1];

date_default_timezone_set( 'Asia/Tokyo' );
$session_id = date( 'ymdHis' );
mkdir( 'log/dl/' . $session_id, 0777, true );
$log_file = 'log/dl/' . $session_id . '/dl.log';

function GetFollowings( ){

  global $cookie_file;
  $url = 'http://www.pixiv.net/bookmark.php?type=user';
  $followings = array();

  list( $html, $info  ) = @Curl( $url , '' );

  $q = '//section/div[ @class = "members" ]/ul/li/div[ @class = "usericon" ]/a';
  $res = HtmlParse( $html, $q );

  if ( $res->length != 0 ){
    foreach ( $res as $node ){
      $href = $node->getAttribute('href'); //  srcは実際に表示されているとき
      $matchs = array();
      preg_match( '/\w+\.\w+\?\w+=(\d+)/', $href, $matchs );
      array_push( $followings, $matchs[1] );
    }
  } else {
    return false;
  }
  return $followings;
}

$followings = GetFollowings();
if ( $followings == false  ){
  Msg( 'error', "Coundn't get your followings!\n" );
  exit( 1 );
} else {
  foreach ( $followings as $following ){
    print $following . "\n";
  }
}

exit( 0 );

?>
