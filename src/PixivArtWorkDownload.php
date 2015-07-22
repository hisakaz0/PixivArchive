<?php


function PixivArtWorkDownload ( $userlist, $userlist_file ){

  for ( $i = 0; $i < count( $userlist ); $i++ ){ // 一つ一つ取り出し

    // ユーザ情報を user_id, last_artwork_id, display_nameに分解
    $user_id         = $userlist[$i]['user_id'];
    @$last_artwork_id = $userlist[$i]['last_artwork_id'];
    @$display_name    = $userlist[$i]['display_name'];

    if ( $display_name == '' ){ //ディスプレイネームが設定されていない
      list( $user_exist, $display_name ) = UserCheck( $user_id );
    } else { // されている
      list( $user_exist, $display_name ) = UserCheck( $user_id );
      $display_name = $userlist[$i]['display_name'];
    }

    if ( $user_exist == 0 ){ // user exsit

      if ( $last_artwork_id == '' ){ // last_artwork_idがnull 初めてのご利用
        $dir = '.images/' . $user_id; // ユーザのディレクトリ
        if ( ! file_exists( $dir ) ){ // フォルダが作られているか
          mkdir( $dir, 0777, true ) // なかったら作る
            or die("Interrupt: Can't mkdir " . $dir ."'\n"); // 事故があったらえんだー
        }
        $page = 1; // 最新のページ
        $current_artwork_id = GetFirstArtWorkId( $user_id, $page ); //処女get
        DownloadArtWork( // 先頭の作品をdl
          $current_artwork_id, $user_id );
      } else {
        $current_artwork_id = $last_artwork_id; // 注目している作品
      }

      $last_artwork_id = AllDownloadArtWork(
        $current_artwork_id, $user_id ); // 最新の作品までdonwnload

      $userlist[$i]['last_artwork_id'] = $last_artwork_id;

      WriteCsv ( $userlist, $userlist_file ); //書き込み
    }
  }


}

function UserCheck( $user_id ){

  $url = 'http://www.pixiv.net/member.php?id=' . $user_id;
  $log_file_name = 'user_check_' . $user_id ;

  list( $html, $info ) = @Curl( $url, $log_file_name ); // urlからcontentを引っ張ってくる
  HtmlDump( $html, $log_file_name );

  if ( $info['http_code'] != '404' ){ // ユーザが存在するかどうか

    $q = '//a[ @class = "user-link" ]/h1[ @class = "user" ]';
    $res = HtmlParse( $html, $q );

    if ( $res->length == 1 ){
      foreach ( $res as $node ){
        $display_name = $node->textContent;
      }
    }

    fputs( STDERR,
      "Succeed: user_id '$user_id' / display_name '$display_name' is exist!.\n"); //いた
    fputs( STDERR,
      "Start: Download artworks of user_id '$user_id'.\n"); //いた

    return array( 0, $display_name );

  } else { // ユーザが存在しない

    fputs( STDERR, "Error: user_id '$user_id' is not exsit!\n"); // そんなユーザいねぇ
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
    HtmlDump( $html, $log_file_name );


    $q = '//ul[ @class = "page-list" ]/li[last()]/a'; // 辿れる最後のページを取得
    $res = HtmlParse( $html, $q );

    if ( $res->length != 0 ){ // 次のページがある場合
      foreach ( $res as $node ){
        $page = $node->textContent; // page番号を取得
      }
    } else { //次のページがない場合. つまり,最後のページの場合
      $q = '//ul[ @class = "_image-items" ]/li[ last() ]/a[ @class ]'; // 最後の作品をGet
      $res = HtmlParse( $html, $q );
      foreach( $res as $node ){
        $matchs = array(); //マッチした全体は0,あとは括弧の数だけ要素が増えていく
        preg_match( '/illust_id=(\d+)/', $node->getAttribute("href"), $matchs );
      }

      fputs( STDERR, "Succeed: Get a first artwork with user_id '" . $user_id . "' .\n" );
      return $matchs[1]; // 作品のidだけ返す
    }
  }
}

function DownloadArtWork( $artwork_id, $user_id ){

  $url = 'http://www.pixiv.net/member_illust.php?mode=medium&illust_id=' . $artwork_id;
  $log_file_name = 'download_artwork_' . $artwork_id;

  list( $html, $info ) = @Curl( $url, $log_file_name ); // urlからcontentを引っ張ってくる
  HtmlDump( $html, $log_file_name );

  fputs( STDERR, "Start: Download a artwork with artwork_id '" . $artwork_id . "'.\n" );


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
    fputs( STDERR, "Error Couldn't get artwork uploaded date.\n" );
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
    fputs( STDERR, "Error Couldn't get artwork title.\n" );
  } else { // ファイル名に使えない文字を置換
    $title = preg_replace( '/(\\\|\/)/', '_', $title ); // スラ系はアンダーバーに変換
  }


  $artwork_stored_name = $date . '_' . $title; // 作品のファイル又はディレクトリ名


  // イラストか判定 イラストだったらdlしてreturn 0
  $q = '//div/div[ @class = "wrapper" ]/img'; // original url先を取得
  $res = HtmlParse( $html, $q );
  if ( $res->length == 1 ){
    fputs( STDERR, "Mode is illust.\n" );
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
    fputs( STDERR, "Mode is mange.\n" );
    DownloadManga( $artwork_id, $user_id, $artwork_stored_name );
    return 0;
  }

  // うごいら判定 うごいらだったらDwonloadUgoiraを行って return 0
  $matchs = array();
  $pattern = '/pixiv\.context\.ugokuIllustFullscreenData\s*=\s*{"?src"?:"?([\/\\\\\-:\.\w]+)"?,/';
  preg_match( $pattern, $html , $matchs );
  if ( $matchs[1] != '' ){ // urlが獲得できたとき
    fputs( STDERR, "Mode is ugoira.\n" );
    $referer = $url;
    $url = preg_replace( '/\\\\/', '', $matchs[1] ); // うごいらのzip のurl
    $order = ''; // うごいらに順番なんてない
    $dir_path  = '.images/' . $user_id. '/' . $artwork_stored_name;
    $file_path =  $dir_path . '/ugoira.zip';
    if ( ! file_exists( $dir_path ) ) {
      mkdir( $dir_path, 0777, true ) //フォルダ作成
        or die("Interrupt: Can't mkdir ". $dir_path ."'\n");
    }
    DownloadContent(
      $artwork_id, $url, $referer, $order, $file_path );
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

function DownloadContent(
  $artwork_id, $url, $referer, $order, $file_path ){

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

    fputs( STDERR, "Succeed: Downloaded a image in $file_path\n" );
    return 0;

  } else {
    fputs( STDERR,
      "Error: failed a download image with artwork_id " . $artwork_id . "\n" );
    return 1;
  }
}

function DownloadManga( $artwork_id, $user_id, $artwork_stored_name ){

  $url     = 'http://www.pixiv.net/member_illust.php?mode=manga&illust_id=' . $artwork_id;
  $referer = 'http://www.pixiv.net/member_illust.php?mode=medium&illust_id=' . $artwork_id;
  $log_file_name = 'donwnload_manga_' . $artwork_id;

  list( $html, $info ) = @Curl( $url, $log_file_name, $referer );
  HtmlDump( $html, $log_file_name );


  $dir = '.images/'. $user_id. '/' . $artwork_stored_name;
  if ( ! file_exists( $dir ) ){ // ファイルが存在するか
    mkdir( $dir, 0777, true ) or die("Interrupt: Can't mkdir ". $dir ."'\n");
  }

  $q = '//section/div[ @class = "item-container" ]/img';// original-img url
  $res = HtmlParse( $html, $q );
  $order = 0; // マンガのページ番号
  $referer = $url; // refererの設定

  foreach ( $res as $node ){

    $url = $node->getAttribute('data-src'); //  srcは実際に表示されているとき
    preg_match( '/\.(\w+)$/', $url, $matchs ); // 拡張子取り出し
    $suffix = $matchs[1];

    $file_path = sprintf( // ファイルパス
      '.images/' . '%s' . '/' . '%s' . '/' . '%03d' . '.%s',
      $user_id, $artwork_stored_name, $order, $suffix);

    DownloadContent( // 各画像をダウンロード
      $artwork_id, $url, $referer, $order, $file_path );
    $order = $order + 1; // ページ番号をインクリメント
  }

  return 0;
}

function NextArtWorkExist( $artwork_id ){

  $url = 'http://www.pixiv.net/member_illust.php?mode=medium&illust_id=' . $artwork_id;
  $log_file_name = 'next_artwork_id_' . $artwork_id;

  list( $html, $info ) = @Curl( $url, $log_file_name ); // urlからcontentを引っ張ってくる
  HtmlDump( $html, $log_file_name );

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
    fputs( STDERR, "This artwork_id '" . $artwork_id . "' is the latest.\n" );
    return $artwork_id; // 同じartwork_idを返す
  }
}

function HtmlDump( $html, $log_file_name ){

  global $session_id;

  $handle = fopen( 'log/dl/' . $session_id . '/' . $log_file_name .'.html', 'w' );
  fputs( $handle, $html );
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
  $info_text = print_r( $info, true );

  $handle = fopen( $log_file, 'w' ); //write curl log
  fputs( $handle, $info_text );
  fclose( $handle );

  return array( $content, $info );

}
?>
