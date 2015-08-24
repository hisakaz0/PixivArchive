<?php

require dirname(__file__) . '/src/PixivArtWorkDownload.php';
require dirname(__file__) . '/src/CookieLogin.php';
require dirname(__file__) . '/src/Csv.php';

list( // パラメータの設定
  $image_dir,
  $link_dir,
  $cookie_file,
  $userlist_file
) = SetParam();

date_default_timezone_set( 'Asia/Tokyo' );
$session_id = date( 'ymdHis' );
mkdir( 'log/dl/' . $session_id, 0777, true );
$log_file = 'log/dl/' . $session_id . '/dl.log';

CookieLogin( );

$followings = @GetFollowings();
if ( $followings == false  ){  //  ログイン出来なかったら
  Msg( 'error', "Coundn't get your followings!\n" );
  exit( 1 );
}

$userlist = ReadCsv( $userlist_file );
WriteCsv(
  SortUserlist( UpdateFollowgins( $userlist, $followings ) ),
  $userlist_file );

// WriteCsv( UpdateFollowgins( $userlist, $followings ), $userlist_file );
exit( 0 );


function SortUserlist ( $userlist ){

  $id_list = array_column( $userlist, 'user_id' ); // idだけ抜き出す
  natsort( $id_list ); // 昇順に並び替え
  $sorted_list = array();

  foreach ( $id_list as $key => $id ){
    $user = array(
      'user_id'         => $userlist["$key"]['user_id'],
      'last_artwork_id' => $userlist["$key"]['last_artwork_id'],
      'display_name'    => $userlist["$key"]['display_name']
    );
    array_push( $sorted_list, $user );
  }

  return $sorted_list;
}
function UpdateFollowgins( $userlist, $followings ){

  $id_list = array_column( $userlist, 'user_id' ); // idだけ抜き出す
  $new_followings_id = array_diff( $followings, $id_list );

  foreach ( $new_followings_id as $id ){
    $new['user_id'] = $id;
    array_push( $userlist, $new );
  }

  return $userlist;
}

function GetFollowings( $pages ){

  global $cookie_file;

  $pages      = 1;
  $followings = array();

  while ( true ){

    Msg( 0, "Page number is '$pages'.\n" );
    $url = 'http://www.pixiv.net/bookmark.php?type=user&rest=show&p=' . $pages;
    list( $html, $info  ) = @Curl( $url , '' );


    # 今見ているページのfollowingsを取得
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


    # 次のページありゅ?
    $q = '//section/div[ @class = "pages" ]/ol/li/a[ @rel = "next"]'; // next page
    $res = HtmlParse( $html, $q );

    if ( $res->length == 2 ){
      $pages = $pages + 1;
    } else {
      return $followings;
    }

  }
}

?>
