<?php

require_once dirname(__file__) . '/src/Csv.php';
require_once dirname(__file__) . '/src/PixivArtWorkDownload.php';

date_default_timezone_set( 'Asia/Tokyo' );
$log_dir  = 'log/mklink/';
$log_file =  $log_dir . date( 'ymdHis' ) . ".log";
MakeDirectory( $log_dir );

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
  $file = 'mklink.vbs'; 
  $handle = fopen( $file, 'w');

  $str = "Dim wsh,src\r\n";
  fwrite( $handle, mb_convert_encoding( $str, 'SJIS-win', 'UTF-8' ));
  $str = "Set wsh = CreateObject(\"WScript.Shell\")\r\n";
  fwrite( $handle, mb_convert_encoding( $str, 'SJIS-win', 'UTF-8' ));

  foreach( $userlist as $user ){
    @$user_id         = $user['user_id'];
    @$display_name    = $user['display_name'];

    $str = "Set src = wsh.CreateShortcut(\"E:\\PixivArchive\\" . $link_dir . "_windows\\" . $display_name . ".lnk\")\r\n";
    fwrite( $handle, mb_convert_encoding( $str, 'SJIS-win', 'UTF-8' ));
   
    $str = "src.TargetPath = \"E:\\PixivArchive\\" . $image_dir . "\\" . $user_id . "\"\r\n";
    fwrite( $handle, mb_convert_encoding( $str, 'SJIS-win', 'UTF-8' ));
    
    $str = "src.save\r\n";
    fwrite( $handle, mb_convert_encoding( $str, 'SJIS-win', 'UTF-8' ));
  }
  $str = "Set wsh = nothing \r\n";
  fwrite( $handle, mb_convert_encoding( $str, 'SJIS-win', 'UTF-8' ));
  $str = "Set src = nothing \r\n";
  fwrite( $handle, mb_convert_encoding( $str, 'SJIS-win', 'UTF-8' ));

  fclose( $handle );
  Msg( 'succeed', "Create '" . $file . "'.\n" );

}

?>
