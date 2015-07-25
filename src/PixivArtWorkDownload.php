<?php

require_once dirname(__file__) . '/../lib/ansi-color.php';
use PhpAnsiColor\Color;

function PixivArtWorkDownload ( $userlist, $userlist_file ){

  $index = 0;
  while(  $index < count( $userlist ) ){ // 一つ一つ取り出し

    // ユーザ情報を user_id, last_artwork_id, display_nameに分解
    @$user_id         = $userlist[$index]['user_id'];
    @$last_artwork_id = $userlist[$index]['last_artwork_id'];
    @$display_name    = $userlist[$index]['display_name'];

    if ( $display_name == '' ){ //ディスプレイネームが設定されていない
      list( $user_exist, $display_name ) = UserCheck( $user_id ); // ユーザがいるか?
    } else { // されている
      list( $user_exist, $display_name ) = UserCheck( $user_id );
      $display_name = $userlist[$index]['display_name'];
    }

    if ( $user_exist == 0 ){ // user exsit
      if ( $last_artwork_id == '' ){ // last_artwork_idがnull 初めてのご利用
        $dir = '.images/' . $user_id;
        if ( ! MakeDirectory( $dir ) ) { // ユーザのディレクトリ作成
          $current_artwork_id = 1; // 空に設定 条件フラグの役割
          Msg('error', "failed make directory in $dir.\n"); // 作れなかった報告
        } else { 
          $current_artwork_id = GetFirstArtWorkId( $user_id, 1 ); //処女get
          if ( $current_artwork_id != 1 ){ // 失敗したらオシマイ
            DownloadArtWork( $current_artwork_id, $user_id ); // 先頭の作品をdl
          }
        }
      } else {
        $current_artwork_id = $last_artwork_id; // またのご来店
      }

      if ( $current_artwork_id != 1 ){ // 成功してたら
        $last_artwork_id = AllDownloadArtWork(
          $current_artwork_id, $user_id ); // 最新の作品までdonwnload
      } else { // 失敗してたら
        Msg('interrupt', "download artwork with user_id $user_id.\n"); //dlしないと報告
        $last_artwork_id = ''; // 空に設定
      }

      $userlist[$index]['last_artwork_id'] = $last_artwork_id;
      $userlist[$index]['display_name']    = $display_name;

      $index = $index + 1;

    } else { // userが存在してない場合
      Msg( 0, "Delete the user_id '" . $user_id . "'.\n" );
      array_splice( $userlist, $index, 1 );
    }

    WriteCsv ( $userlist, $userlist_file ); //書き込み
  }
}

function UserCheck( $user_id ){

  $url = 'http://www.pixiv.net/member.php?id=' . $user_id;
  $log_file_name = 'user_check_' . $user_id ;

  list( $html, $info ) = @Curl( $url, $log_file_name ); // urlからcontentを引っ張ってくる

  if ( $info['http_code'] != '404' ){ // ユーザが存在するかどうか

    $q = '//a[ @class = "user-link" ]/h1[ @class = "user" ]';
    $res = HtmlParse( $html, $q );

    if ( $res->length == 1 ){
      foreach ( $res as $node ){
        $display_name = $node->textContent;
      }
    }

    Msg( "succeed", "user_id '$user_id' / display_name '$display_name' is exist!.\n" );
    Msg( "started", "Download artworks of user_id '$user_id'.\n" ); //いた
    return array( 0, $display_name );

  } else { // ユーザが存在しない

    Msg( "error", "user_id '$user_id' is not exsit!\n" ); // そんなユーザいねぇ
    return array( 1, '' );

  }
}

function AllDownloadArtWork( $current_artwork_id, $user_id ){

  while( true ){
    //  次の作品のidを拾ってくる ない場合は今見ている作品のid
    $next_artwork_id = NextArtWorkExist( $current_artwork_id );
    if ( $next_artwork_id != $current_artwork_id ){ // 次の作品があるとき
      DownloadArtWork( $next_artwork_id, $user_id );
      $current_artwork_id = $next_artwork_id; // 注目点を次に移す
    } else { // 次の作品が無いとき
      return $current_artwork_id; // 現在のartwork_idを返却
    }
  }
}

function GetFirstArtWorkId( $user_id, $page ){

  while ( true ){

    $url = 'http://www.pixiv.net/member_illust.php?' // urlの設定
      . 'id=' . $user_id . '&type=all' . '&p=' . $page;
    $log_file_name = 'first_artwork_id_' . $user_id;

    list( $html, $info ) = @Curl( $url, $log_file_name ); // urlからcontentを引っ張ってくる


    $q = '//ul[ @class = "page-list" ]/li[last()]/a'; // 辿れる最後のページを取得
    $res = HtmlParse( $html, $q );

    if ( $res->length != 0 ){ // 次のページがある場合
      foreach ( $res as $node ){
        $page = $node->textContent; // page番号を取得
      }
    } else { //次のページがない場合. つまり,最後のページの場合
      $q = '//ul[ @class = "_image-items" ]/li[ last() ]/a[ @class ]'; // 最後の作品をGet
      $res = HtmlParse( $html, $q );
      if ( $res->length == 1 ){
        foreach( $res as $node ){
          $matchs = array(); //マッチした全体は0,あとは括弧の数だけ要素が増えていく
          preg_match( '/illust_id=(\d+)/', $node->getAttribute("href"), $matchs );
        }
        Msg( "succeed", "Get a first artwork with user_id '" . $user_id . "' .\n" );
        return $matchs[1]; // 作品のidだけ返す
      } else { // なんかおかしい場合
        CurlDump( $info, $log_file_name );
        Msg( "error", "failed get a first artwork with user_id '" . $user_id . "' .\n" );
        return 1;
      }

    }
  }
}

function DownloadArtWork( $artwork_id, $user_id ){

  $url = 'http://www.pixiv.net/member_illust.php?mode=medium&illust_id=' . $artwork_id;
  $log_file_name = 'download_artwork_' . $artwork_id;

  list( $html, $info ) = @Curl( $url, $log_file_name ); // urlからcontentを引っ張ってくる

  Msg( "started", "Download a artwork with artwork_id '" . $artwork_id . "'.\n" );


  // 日時データを取得
  $q = '//ul[ @class = "meta" ]/li[1]';
  $res = HtmlParse( $html, $q );
  if ( $res->length == 1 ){
    foreach ( $res as $node ){ // なんちゃって1つだけ
      $date = $node->textContent; // 最終的なmetaは$dateです.
      $matchs = array();
      preg_match_all( '/\d+/', $date, $matchs );
      $date = sprintf( "%d_%02d%02d_%02d%02d", // year month day hour minitu
        $matchs[0][0], $matchs[0][1], $matchs[0][2], $matchs[0][3], $matchs[0][4] );
    }
  } else {
    Msg( "error", "Couldn't get artwork uploaded date.\n" );
  }


  // タイトル回収
  $q = '//div[ @class = "ui-expander-target" ]/h1'; // 1つめの
  $res = HtmlParse( $html, $q );
  if ( $res->length == 1 ){
    foreach ( $res as $node ){ // なんちゃって1つだけ
      $title = $node->textContent; // 最終的なmetaは$dateです.
    }
  }

  $q = '//section[ @class = "work-info" ]/h1'; // 2つめの
  $res = HtmlParse( $html, $q );
  if ( $res->length == 1 ){
    foreach ( $res as $node ){ // なんちゃって1つだけ
      $title = $node->textContent; // 最終的なmetaは$dateです.
    }
  }

  if ( $title == '' ){ //タイトルないとの報告
    Msg( "error", "Couldn't get artwork title.\n" );
  } else { // ファイル名に使えない文字を置換
    $title = preg_replace( '/(\\\|\/)/', '_', $title ); // スラ系はアンダーバーに変換
  }


  $artwork_stored_name = $date . '_' . $title; // 作品のファイル又はディレクトリ名


  // イラストか判定 イラストだったらdlしてreturn 0
  $q = '//div/div[ @class = "wrapper" ]/img'; // original url先を取得
  $res = HtmlParse( $html, $q );
  if ( $res->length == 1 ){
    Msg( 0, "Mode is illust.\n" );
    $referer = $url; // refererのせってい
    foreach ( $res as $node ){
      $url = $node->getAttribute('data-src'); // srcはクリックしないと表示されない
      $matchs = array();
      preg_match( '/\.(\w+)$/', $url, $matchs ); // 拡張子取り出し
    }
    $suffix = $matchs[1];
    $file_path = '.images/' . $user_id . '/' . $artwork_stored_name . '.' . $suffix;
    $order = '';
    DownloadContent( $artwork_id, $url, $referer, $order, $file_path );
    return 0;
  }


  // マンガ検索 マンガだったらかDownloadMangaを行って return 0
  $q = '//div[ @class = "works_display" ]/a';
  $res = HtmlParse( $html, $q );
  if ( $res->length == 1 ){
    Msg( 0, "Mode is mange.\n" );
    DownloadManga( $artwork_id, $user_id, $artwork_stored_name );
    return 0;
  }

  // うごいら判定 うごいらだったらDwonloadUgoiraを行って return 0
  $matchs = array();
  $pattern =
    '/pixiv\.context\.ugokuIllustFullscreenData\s*=\s*{"?src"?:"?([\/\\\\\-:\.\w]+)"?,/';
  preg_match( $pattern, $html , $matchs );
  if ( $matchs[1] != '' ){ // urlが獲得できたとき
    Msg( 0, "Mode is ugoira.\n" );
    $referer = $url;
    $url = preg_replace( '/\\\\/', '', $matchs[1] ); // うごいらのzip のurl
    $order = ''; // うごいらに順番なんてない
    $dir  = '.images/' . $user_id. '/' . $artwork_stored_name;
    $file =  $dir . '/ugoira.zip';

    if ( ! MakeDirectory( $dir ) ){
      Msg( "error", "Couldn't make the directory " . $dir . "'\n" );
      return 1;
    }

    DownloadContent(
      $artwork_id, $url, $referer, $order, $file );
    $zip = new ZipArchive;
    if ( $zip->open( $file ) === TRUE ) { // ファイルオープンが成功
      $zip->extractTo( $dir ); // 解凍先
      $zip->close();
      Msg( "succeed", "Extract a zip file '$file'\n\tto '$dir'.\n" );
      unlink( $file ); // ファイル削除
      Msg( "succeed", "Remove a zip file '$file'.\n" );
    } else {
      Msg( "error", "Couldn't remove a zip file '$file'.\n" );
    }

    return 0;
  }

  CurlDump( $info, $log_file_name );
  Msg( "error", "Couldn't  download the artwork with artwork_id '" . $artwork_id . "'\n" );
  return 1;
}

function DownloadContent( $artwork_id, $url, $referer, $order, $file_path ){

  if ( $order == '' ){
    $log_file_name = 'download_image_' . $artwork_id;
  } else {
    $log_file_name = 'download_image_' . $artwork_id . '_' . $order;
  }

  list( $html, $info ) =
    @Curl( $url, $log_file_name, $referer ); // urlからcontentを引っ張ってくる

  if ( $info['http_code'] == 200 ){

    $handle = fopen( $file_path, 'w' ); // 画像やzipを書き込み
    fputs( $handle, $html );
    fclose( $handle );

    Msg( "succeed", "Downloaded a content in $file_path\n" );
    return 0;

  } else {
    CurlDump( $info, $log_file_name );
    Msg( "error", "failed a download image with artwork_id " . $artwork_id . "\n" );
    return 1;
  }
}

function DownloadManga( $artwork_id, $user_id, $artwork_stored_name ){

  $url     = 'http://www.pixiv.net/member_illust.php?mode=manga&illust_id=' . $artwork_id;
  $referer = 'http://www.pixiv.net/member_illust.php?mode=medium&illust_id=' . $artwork_id;
  $log_file_name = 'donwnload_manga_' . $artwork_id;

  list( $html, $info ) = @Curl( $url, $log_file_name, $referer );

  $dir = '.images/'. $user_id. '/' . $artwork_stored_name;
  if ( ! MakeDirectory( $dir ) ){
    Msg( "error", "Couldn't make the directory " . $dir . "'\n" );
    return 1;
  }

  $q = '//section/div[ @class = "item-container" ]/img';// original-img url
  $res = HtmlParse( $html, $q );
  $order = 0; // マンガのページ番号
  $referer = $url; // refererの設定

  if ( $res->length != 0  ){
    foreach ( $res as $node ){
      $matchs = array();
      $url = $node->getAttribute('data-src'); //  srcは実際に表示されているとき
      preg_match( '/\.(\w+)$/', $url, $matchs ); // 拡張子取り出し
      $suffix = $matchs[1];

      $file_path = sprintf( // ファイルパス
        '.images/' . '%s' . '/' . '%s' . '/' . '%03d' . '.%s',
        $user_id, $artwork_stored_name, $order, $suffix
      );

      DownloadContent( // 各画像をダウンロード
        $artwork_id, $url, $referer, $order, $file_path );
      $order = $order + 1; // ページ番号をインクリメント
    }
    return 0;
  } else {
    CurlDump( $info, $log_file_name );
    Msg( "error", "Couldn't donwnload the manga with artwork_id " . $artwork_id. "'\n" );
    return 1;
  }
}

function NextArtWorkExist( $artwork_id ){

  $url = 'http://www.pixiv.net/member_illust.php?mode=medium&illust_id=' . $artwork_id;
  $log_file_name = 'next_artwork_id_' . $artwork_id;

  list( $html, $info ) = @Curl( $url, $log_file_name ); // urlからcontentを引っ張ってくる

  $q = '//ul/li[ @class = "before" ]/a'; // 次の作品
  $res = HtmlParse( $html, $q );

  if ( $res->length == 1 ){ // 次のページがあるとき
    foreach( $res as $node ){
      $matchs = array(); // 次のillust_idを探す
      preg_match( '/illust_id=(\d+)/', $node->getAttribute("href"), $matchs );
      $next_artwork_id = $matchs[1];
    }
    return $next_artwork_id;
  } else { // 次のページがないとき
    Msg( 0, "This artwork_id '" . $artwork_id . "' is the latest.\n" );
    return $artwork_id; // 同じartwork_idを返す
  }
}

function HtmlDump( $html, $log_file_name ){

  global $session_id;

  $handle = fopen( 'log/dl/' . $session_id . '/' . $log_file_name .'.html', 'w' );
  fputs( $handle, $html );
  fclose( $handle );
}

function CurlDump( $info, $log_file_name ){

  global $session_id;

  $info_text = print_r( $info, true );

  $handle = fopen( 'log/dl/' . $session_id . '/' . $log_file_name .'.log', 'w' );
  fputs( $handle, $info_text );
  fclose( $handle );
}

function HtmlParse( $html, $q ){

  $dom = new DOMDocument;
  $dom->preserveWhiteSpace = false;
  @$dom->loadHTML( $html );
  $xp = new DOMXPath( $dom );
  $res = $xp->query( $q );
  return $res;
}

function Curl( $url, $log_file_name, $referer ){

  global $session_id, $cookie_file;
  $log_file = 'log/dl/' . $session_id . '/' . $log_file_name . '.log' ;

  $ch = curl_init( $url ); // curlの初期設定
  curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true ); // redirectionを有効化
  curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true ); // プレーンテキストで出力
  curl_setopt( $ch, CURLOPT_COOKIEJAR, $cookie_file ); // cookie情報を読み込む
  curl_setopt( $ch, CURLOPT_COOKIEFILE, $cookie_file ); // cookie情報を保存する

  if ( $referer != '' ){ // refererがあれば
    curl_setopt( $ch, CURLOPT_REFERER, $referer ); // refererを設定
  }

  $content = curl_exec( $ch ); // curlの実行

  $info = curl_getinfo( $ch ); // 実行結果
  curl_close( $ch ); // curl終了

  return array( $content, $info );
}

function MakeDirectory( $dir ){

  if ( ! is_dir( $dir ) ){ // ディレクトリがあるか?
    if ( ! mkdir( $dir, 0777, true ) ) { // なければ作る
      return false; // 作れませんでした.
    }
  }

  return true; //作れました. or ありました.

}

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
