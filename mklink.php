<?php

require_once dirname(__file__) . '/src/Csv.php';
require_once dirname(__file__) . '/src/PixivArtWorkDownload.php';


$log_file = 'mklink';
list( // パラメータの設定
  $image_dir,
  $link_dir,
  $cookie_file,
  $userlist_file
) = SetParam();

CreateLink( ReadCsv( $userlist_file ) );

function CreateLink ( $userlist ){

  global $image_dir, $link_dir;
  MakeDirectory( "${link_dir}_windows" );
  $handle = fopen( 'mklink.bat', 'w');

  foreach( $userlist as $user ){
    @$user_id         = $user['user_id'];
    @$display_name    = $user['display_name'];
    fwrite( $handle,
      "mklink /d ${link_dir}_windows\\$display_name $image_dir\\$user_id" . "\r\n" );
  }

  fclose( $handle );
  Msg( 'succeed', "Create bat file 'mklink.bat'.\n" );

}

?>
