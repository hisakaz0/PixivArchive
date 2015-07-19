<?php

function PixivArtWorkDownload ( $userlist, $cookie_file ){

  foreach ( $userlist as $user ){

    list( $user_id, $last_artwork_id, $display_name ) = $user;

    if ( ! UserCheck( $user_id, $cookie_file ) ){ // user exsit
      if ( $last_artwork_id == '' ){ // last_artwork_idがnull
        $page = 1; // 最新のページ
        $current_artwork_id = GetFirstArtWorkId( $user_id, $page, $cookie_file ); //処女get
        DownloadArtWork(  $current_artwork_id ); // 先頭はdlする
      } else {
        $current_artwork_id = $last_artwork_id; // 注目している作品
      }
      AllDownloadArtWork( $current_artwork_id ); // 最新の作品までdonwnload
    } else { // Not exsit this user
      fputs(STDERR, "Interrupt: user_id '$user_id' is not exsit!\n");
    }
  }

}

function UserCheck( $user_id, $cookie_file ){

  $url = 'http://www.pixiv.net/member.php?id=' . $user_id;
  $dump_file = 'log/user_check_' . $user_id . '.log';
  $html_file = 'log/user_check_' . $user_id . '.html';

  $ch = curl_init($url); // curlの初期設定
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // redirectionを有効化
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // プレーンテキストで出力
  curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file); // cookie情報を保存する
  curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file); // cookie情報を保存する
  $html = curl_exec($ch);
  $info = curl_getinfo($ch); // 実行結果
  curl_close($ch); // curl終了
  $res = print_r($info, true);

  $handle = fopen($html_file, 'w'); // dump html source
  fputs( $handle, $html);
  fclose($handle);

  $handle = fopen($dump_file, 'w'); // dump curl log
  fputs( $handle, $res );
  fclose($handle);

  if ( $info['http_code'] != '404' ){ // ユーザが存在する
    return 0;
  } else { // ユーザが存在しない
    return 1;
  }
}

function AllDownloadArtWork( $user_id, $current_artwork_id ){
  $next_artwork_id = NextArtwork( $user_id, $current_artwork_id );

  if ( $next_artwork_id != '' ){ // 次の作品があるとき
    DownloadArtWork( $user_id, $next_artwork_id );
    AllDownloadArtWork( $user_id, $next_artwork_id );
  } else { // 次の作品が無いとき
    return 0;
  }
}

function GetFirstArtWorkId( $user_id, $page, $cookie_file ){

  $url = 'http://www.pixiv.net/member_illust.php?'
    . 'id=' . $user_id . '&type=all' . '&p=' . $page;

  $dump_file = 'log/first_artwork_id' . $user_id . '.log';
  $html_file = 'log/first_artwork_id' . $user_id . '.html';

  $ch = curl_init($url); // curlの初期設定
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // redirectionを有効化
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // プレーンテキストで出力
  curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file); // cookie情報を保存する
  curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file); // cookie情報を保存する
  $html = curl_exec($ch);
  $info = curl_getinfo($ch); // 実行結果
  curl_close($ch); // curl終了
  $res = print_r($info, true);

  $handle = fopen($html_file, 'w'); // dump html source
  fputs( $handle, $html);
  fclose($handle);

  $handle = fopen($dump_file, 'w'); // dump curl log
  fputs( $handle, $res );
  fclose($handle);

  // print( $info['url'] . "\n" );

  $dom = new DOMDocument;
  $dom->preserveWhiteSpace = false;
  @$dom->loadHTML($html);
  $xp = new DOMXPath($dom);

  $q = '//div[ @class = "pager-container" ]/span[ @class = "next" ]/a';
  $res = $xp->query( $q );
  if ( $res->length != 0 ){ // 次のページがある場合

    $q = '//ul[ @class = "page-list" ]/li[last()]/a'; // 辿ることができる最後のページを取得
    $res = $xp->query( $q );

    foreach ( $res as $node ){
      $page = $node->textContent;
    }

    GetFirstArtWorkId( $user_id, $page, $cookie_file );

  } else { //次のページがない場合. つまり,最後のページの場合
    $q = '//ul[ @class = "_image-items" ]/li[ last() ]/a[ @class ]';
    $res = $xp->query( $q );

    foreach( $res as $node ){
      $matchs = array(); //マッチした全体は0,あとは括弧の数だけ要素が増えていく
      preg_match( '/illust_id=(\d+)/', $node->getAttribute("href"), $matchs );
    }

    return $matchs[1]; // 作品のidだけ返す
  }
}

function DownloadArtWork( $artwork_id, $cookie_file ){

  $url = 'http://www.pixiv.net/member_illust.php?mode=medium&illust_id=' . $artwork_id;

  $dump_file = 'log/download_artwork' . $artwork_id . '.log';
  $html_file = 'log/download_artwork' . $artwork_id . '.html';

  $ch = curl_init($url); // curlの初期設定
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // redirectionを有効化
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // プレーンテキストで出力
  curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file); // cookie情報を読み込む
  curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file); // cookie情報を保存する
  $html = curl_exec($ch);
  $info = curl_getinfo($ch); // 実行結果
  curl_close($ch); // curl終了
  $res = print_r($info, true);

  $handle = fopen($html_file, 'w'); // dump html source
  fputs( $handle, $html);
  fclose( $handle );

  $handle = fopen($dump_file, 'w'); // dump curl log
  fputs( $handle, $res );
  fclose( $handle );

  $dom = new DOMDocument;
  $dom->preserveWhiteSpace = false;
  @$dom->loadHTML($html);
  $xp = new DOMXPath($dom);


  // 日時データを取得
  $q = '//ul[ @class = "meta" ]/li[1]';
  $res = $xp->query( $q );
  foreach ( $res as $node ){ // なんちゃって1つだけ
    $date = $node->textContent; // 最終的なmetaは$dateです.
    $matchs = array();
    preg_match_all( '/\d+/', $date, $matchs );
    $date = sprintf( "%d_%02d%02d_%02d%02d",
      $matchs[0][0], $matchs[0][1], $matchs[0][2], $matchs[0][3], $matchs[0][4] );
  }


  // タイトル回収
  $q = '//div[ @class = "ui-expander-target" ]/h1';
  $res = $xp->query( $q );
  foreach ( $res as $node ){ // なんちゃって1つだけ
    $title = $node->textContent; // 最終的なmetaは$dateです.
  }


  // イラスト検索
  $q = '//div[ @class = "works_display" ]/div/img';
  $res = $xp->query( $q );
  if ( $res->length == 1 ){
    $mode = 'illust';
    foreach ( $res as $node ){
      $url = $node->getAttribute('src');
    }
  }

  // マンガ検索
  $q = '//div[ @class = "works_display" ]/a';
  $res = $xp->query( $q );
  if ( $res->length == 1 ){
    $mode = 'manga';
    foreach ( $res as $node ){
      $url = $node->getAttribute('href');
    }
    $url = 'http://www.pixiv.net/' . $url;
  }


  print $mode . "\n";

  // if ( $mode == 'manga' ) { // manga
  // } elseif ( $mode == 'ugoira' ) { // ugoira
  // } else { // illust
  // }

  print $date . '_' . $title . "\n";
  print $url . "\n";
  return 0;
}

?>
