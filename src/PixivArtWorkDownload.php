<?php

function PixivArtWorkDownload ( $userlist, $cookie_file ){

  $store_userlist = array();

  foreach ( $userlist as $user ){ // 一つ一つ取り出し

    // ユーザ情報を user_id, last_artwork_id, display_nameに分解
    $user_id = $user['user_id'];
    $last_artwork_id = $user['last_artwork_id'];

    if ( $user['display_name'] == '' ){ //ディスプレイネームが設定されていない
      list( $user_exist, $display_name ) = UserCheck( $user_id, $cookie_file );
    } else { // されている
      list( $user_exist, $display_name ) = UserCheck( $user_id, $cookie_file );
      $display_name = $user['display_name'];
    }

    if ( $user_exist == 0 ){ // user exsit

      if ( $last_artwork_id == '' ){ // last_artwork_idがnull 初めてのご利用
        $dir = 'images/' . $user_id; // ユーザのディレクトリ
        if ( ! file_exists( $dir ) ){ // フォルダが作られているか
          mkdir( $dir, 0777, true ) // なかったら作る
            or die("Interrupt: Can't mkdir " . $dir ."'\n"); // 事故があったらえんだー
        }
        $page = 1; // 最新のページ
        $current_artwork_id = GetFirstArtWorkId( $user_id, $page, $cookie_file ); //処女get
        DownloadArtWork( // 先頭の作品をdl
          $current_artwork_id, $user_id, $cookie_file );
      } else {
        $current_artwork_id = $last_artwork_id; // 注目している作品
      }

      $last_artwork_id = AllDownloadArtWork(
        $current_artwork_id, $user_id, $cookie_file ); // 最新の作品までdonwnload

      $user = array( // last_artwork_idを更新する.
        $user_id, $last_artwork_id, $display_name );
      array_push( $store_userlist, $user ); // store_userlistに更新
    }
  }

  return $store_userlist;
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

  if ( $info['http_code'] != '404' ){ // ユーザが存在するかどうか

    $dom = new DOMDocument;
    $dom->preserveWhiteSpace = false;
    @$dom->loadHTML($html);
    $xp = new DOMXPath($dom);

    $q = '//a[ @class = "user-link" ]/h1[ @class = "user" ]';
    $res = $xp->query( $q );

    if ( $res->length == 1 ){
      foreach ( $res as $node ){
        $display_name = $node->textContent;
      }
    }

    fputs( STDERR, "Succeed: user_id '$user_id' / display_name '$display_name' is exist!.\n"); //いた
    fputs( STDERR, "Start: Download artworks of user_id '$user_id'.\n"); //いた
    return array( 0, $display_name );
  } else { // ユーザが存在しない
    fputs( STDERR, "Error: user_id '$user_id' is not exsit!\n"); // そんなユーザいねぇ
    return array( 1, '' );
  }
}

function AllDownloadArtWork( $current_artwork_id, $user_id, $cookie_file ){

  while( true ){
    //  次の作品のidを拾ってくる ない場合は今見ている作品のid
    $next_artwork_id = NextArtworkExist( $current_artwork_id, $cookie_file );
    if ( $next_artwork_id != $current_artwork_id ){ // 次の作品があるとき
      DownloadArtWork( $next_artwork_id, $user_id, $cookie_file );
      $current_artwork_id = $next_artwork_id; // 注目点を次に移す
    } else { // 次の作品が無いとき
      return $current_artwork_id; // 現在のartwork_idを返却
    }
  }
}

function GetFirstArtWorkId( $user_id, $page, $cookie_file ){

  while ( true ){

    $url = 'http://www.pixiv.net/member_illust.php?' // urlの設定
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

    $dom = new DOMDocument; // dom解析の初期設定
    $dom->preserveWhiteSpace = false;
    @$dom->loadHTML($html);
    $xp = new DOMXPath($dom);

    $q = '//div[ @class = "pager-container" ]/span[ @class = "next" ]/a';
    $res = $xp->query( $q );
    if ( $res->length != 0 ){ // 次のページがある場合
      $q = '//ul[ @class = "page-list" ]/li[last()]/a'; // 辿れる最後のページを取得
      $res = $xp->query( $q );
      foreach ( $res as $node ){
        $page = $node->textContent; // page番号を取得
      }
    } else { //次のページがない場合. つまり,最後のページの場合
      $q = '//ul[ @class = "_image-items" ]/li[ last() ]/a[ @class ]'; // 最後の作品をGet
      $res = $xp->query( $q );
      foreach( $res as $node ){
        $matchs = array(); //マッチした全体は0,あとは括弧の数だけ要素が増えていく
        preg_match( '/illust_id=(\d+)/', $node->getAttribute("href"), $matchs );
      }
      fputs( STDERR, "Succeed: Get a first artwork with user_id '" . $user_id . "' .\n" );
      return $matchs[1]; // 作品のidだけ返す
    }
  }
}

function DownloadArtWork( $artwork_id, $user_id, $cookie_file ){

  $url = 'http://www.pixiv.net/member_illust.php?mode=medium&illust_id=' . $artwork_id;

  $dump_file = 'log/download_artwork_' . $artwork_id . '.log';
  $html_file = 'log/download_artwork_' . $artwork_id . '.html';

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

  fputs( STDERR, "Start: Download a artwork with artwork_id '" . $artwork_id . "'.\n" );

  $dom = new DOMDocument;
  $dom->preserveWhiteSpace = false;
  @$dom->loadHTML($html);
  $xp = new DOMXPath($dom);


  // 日時データを取得
  $q = '//ul[ @class = "meta" ]/li[1]';
  $res = $xp->query( $q );
  if ( $res->length == 1 ){
    foreach ( $res as $node ){ // なんちゃって1つだけ
      $date = $node->textContent; // 最終的なmetaは$dateです.
      $matchs = array();
      preg_match_all( '/\d+/', $date, $matchs );
      $date = sprintf( "%d_%02d%02d_%02d%02d", // year month day hour minitu
        $matchs[0][0], $matchs[0][1], $matchs[0][2], $matchs[0][3], $matchs[0][4] );
    }
  } else { fputs( STDERR, "Error Couldn't get artwork uploaded date.\n" ); }


  // タイトル回収
  $q = '//div[ @class = "ui-expander-target" ]/h1'; // 1つめの
  $res = $xp->query( $q );
  if ( $res->length == 1 ){
    foreach ( $res as $node ){ // なんちゃって1つだけ
      $title = $node->textContent; // 最終的なmetaは$dateです.
    }
  }

  $q = '//section[ @class = "work-info" ]/h1'; // 2つめの
  $res = $xp->query( $q );
  if ( $res->length == 1 ){
    foreach ( $res as $node ){ // なんちゃって1つだけ
      $title = $node->textContent; // 最終的なmetaは$dateです.
    }
  }

  if ( $title == '' ){ //タイトルないとの報告
    fputs( STDERR, "Error Couldn't get artwork title.\n" );
  } else { // ファイル名に使えない文字を置換
    $title = preg_replace( '/(\\\|\/)/', '_', $title ); // スラ系はアンダーバーに変換
  }


  $artwork_stored_name = $date . '_' . $title; // 作品のファイル又はディレクトリ名


  // イラストか判定 イラストだったらdlしてreturn 0
  $q = '//div/div[ @class = "wrapper" ]/img'; // original url先を取得
  $res = $xp->query( $q );
  if ( $res->length == 1 ){
    fputs( STDERR, "Mode is illust.\n" );
    $referer = $url; // refererのせってい
    foreach ( $res as $node ){
      $img_url = $node->getAttribute('data-src'); // srcはクリックしないと表示されない
      $matchs = array();
      preg_match( '/\.(\w+)$/', $img_url, $matchs ); // 拡張子取り出し
    }
    $suffix = $matchs[1];
    $file_path = 'images/' . $user_id . '/' . $artwork_stored_name . '.' . $suffix;
    $order = '';
    DownloadImage( $artwork_id, $img_url, $referer, $order, $file_path, $cookie_file );
    return 0;
  }


  // マンガ検索 マンガだったらかDownloadMangaを行って return 0
  $q = '//div[ @class = "works_display" ]/a';
  $res = $xp->query( $q );
  if ( $res->length == 1 ){
    DownloadManga( $artwork_id, $user_id, $artwork_stored_name, $cookie_file );
    return 0;
  }

  // うごいら判定 うごいらだったらDwonloadUgoiraを行って return 0
  $matchs = array();
  $pattern = '/pixiv\.context\.ugokuIllustFullscreenData\s*=\s*{"?src"?:"?([\/\\\\\-:\.\w]+)"?,/';
  preg_match( $pattern, $html , $matchs );
  if ( $matchs[1] != '' ){ // urlが獲得できたとき
    fputs( STDERR, "Mode is ugoira.\n" );
    $referer = $url;
    $ugoira_url = preg_replace( '/\\\\/', '', $matchs[1] ); // うごいらのzip のurl
    $order = ''; // うごいらに順番なんてない
    $dir_path  = 'images/' . $user_id. '/' . $artwork_stored_name;
    $file_path =  $dir_path . '/ugoira.zip';
    mkdir( $dir_path, 0777, true ) //フォルダ作成
      or die("Interrupt: Can't mkdir ". $dir_path ."'\n");
    DownloadImage(
      $artwork_id, $ugoira_url, $referer, $order, $file_path, $cookie_file );
    $zip = new ZipArchive;
    if ( $zip->open( $file_path ) === TRUE ) { // ファイルオープンが成功
      $zip->extractTo( $dir_path ); // 解凍先
      $zip->close();
      fputs( STDERR, "Succeed: Extract a zip file '$file_path'\n\tto '$dir_path'.\n" );
      unlink( $file_path );
      fputs( STDERR, "Succeed: Remove a zip file '$file_path'.\n" );
    } else {
      fputs( STDERR, "Error: Couldn't remove a zip file '$file_path'.\n" );
    }
    return 0;
  }


  fputs( STDERR,
    "Error: cannot download the artwork with artwork_id '" . $artwork_id . "'\n" );
  return 1;
}

function DownloadImage(
  $artwork_id, $image_url, $referer, $order, $file_path, $cookie_file ){

  if ( $order == '' ){
    $dump_file = 'log/download_image_' . $artwork_id . '.log';
  } else {
    $dump_file = 'log/download_image_' . $artwork_id . '_' . $order . '.log';
  }

  $ch = curl_init($image_url); // curlの初期設定
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // redirectionを有効化
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // プレーンテキストで出力
  curl_setopt($ch, CURLOPT_REFERER, $referer); // refererを設定
  curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file); // cookie情報を読み込む
  curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file); // cookie情報を保存する
  $img = curl_exec($ch);
  $info = curl_getinfo($ch); // 実行結果
  curl_close($ch); // curl終了
  $res = print_r($info, true);

  $handle = fopen($dump_file, 'w'); // dump curl log
  fputs( $handle, $res );
  fclose( $handle );

  $handle = fopen($file_path, 'w'); // dump html source
  fputs( $handle, $img);
  fclose( $handle );

  if ( $info['http_code'] == 200 ){
    fputs( STDERR,
      "Succeed: Downloaded a image in $file_path\n" );
    return 0;
  } else {
    fputs( STDERR,
      "Error: failed a download image with artwork_id " . $artwork_id . "\n" );
    return 1;
  }

}

function DownloadManga( $artwork_id, $user_id, $artwork_stored_name, $cookie_file ){

  fputs( STDERR, "Mode is mange.\n" );
  $url = 'http://www.pixiv.net/member_illust.php?mode=manga&illust_id=' . $artwork_id;

  $dump_file = 'log/download_manga' . $artwork_id . '.log';
  $html_file = 'log/download_manga' . $artwork_id . '.html';

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
  $q = '//section/div[ @class = "item-container" ]/img';
  $res = $xp->query( $q );
  $order = 0; // マンガのページ番号
  $referer = $url; // refererの設定

  $dir = 'images/'. $user_id. '/' . $artwork_stored_name;
  if ( ! file_exists( $dir ) ){ // ファイルが存在するか
    mkdir( $dir, 0777, true )
      or die("Interrupt: Can't mkdir ". $dir ."'\n");
  }

  foreach ( $res as $node ){

    $img_url = $node->getAttribute('data-src'); // srcはクリックしないと表示されない

    $matchs = array();
    preg_match( '/\.(\w+)$/', $img_url, $matchs ); // 拡張子取り出し
    $suffix = $matchs[1];

    $file_path = sprintf( // ファイルパス
      'images/' . '%s' . '/' . '%s' . '/' . '%03d' . '.%s',
      $user_id, $artwork_stored_name, $order, $suffix);

    DownloadImage( // 各画像をダウンロード
      $artwork_id, $img_url, $referer, $order, $file_path, $cookie_file );
    $order = $order + 1; // ページ番号をインクリメント
  }

  return 0;
}

function NextArtworkExist( $artwork_id, $cookie_file ){

  $url = 'http://www.pixiv.net/member_illust.php?mode=medium&illust_id=' . $artwork_id;

  $dump_file = 'log/next_artwork_id_' . $artwork_id . '.log';
  $html_file = 'log/next_artwork_id_' . $artwork_id . '.html';

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

  $q = '//ul/li[ @class = "before" ]/a'; // 次の作品
  $res = $xp->query( $q );


  if ( $res->length == 1 ){ // 次のページがあるとき
    foreach( $res as $node ){
      $matchs = array(); // 次のillust_idを探す
      preg_match( '/illust_id=(\d+)/', $node->getAttribute("href"), $matchs );
      $next_artwork_id = $matchs[1];
    }
    return $next_artwork_id;
  } else { // 次のページがないとき
    fputs( STDERR, "This artwork_id '" . $artwork_id . "' is the latest.\n" );
    return $artwork_id; // 同じartwork_idを返す
  }
}

?>
