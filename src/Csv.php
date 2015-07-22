<?php

function ReadCsv ( $file ){

  $csv  = new splfileobject($file);
  $csv->setFlags(SplFileObject::READ_CSV);
  $dumplist = array();
  $userlist = array();

  foreach ( $csv as $row ){
    array_push( $dumplist, $row );
  }

  array_shift( $dumplist ); // 列名の行は削除

  foreach ( $dumplist as $row ){

    @$user_id         = $row[0];
    @$last_artwork_id = $row[1];
    @$display_name    = $row[2];

    // Error Cacht
    if ( empty( $user_id ) != true ){  // 空じゃないとき
      if ( @preg_grep( '/^\d+$/', $user_id ) ){ // user_idは数字だけのときだけ許す
        fputs(STDERR, "Error: user_id is only digit.\n");
        exit( 1 );
      }
      if ( @preg_grep( '/^\d+$/', $last_artwork_id ) ){ // last_artwork_idは数字だけ
        fputs(STDERR, "Error: last_artwork_id is only digit.\n");
        exit( 1 );
      }

      $user = array(
        'user_id' => $user_id,
        'last_artwork_id' => $last_artwork_id,
        'display_name' => $display_name
      );
      array_push( $userlist, $user );
    } // 先頭が空の時は無視する
  }

  fputs( STDERR, "Succeed: Read csv file '" . $file. "'.\n" );
  return( $userlist );

}
function WriteCsv ( $userlist, $userlist_file ){

  $handle = fopen( $userlist_file, 'w' );

  fputs( $handle, "user_id,last_artwork_id,display_name\n" ); // 列名を初めにかく
  foreach( $userlist as $user ){
    fputs( $handle,
      $user['user_id'] . ',' . 
      @$user['last_artwork_id'] . ',' .
      @$user['display_name'] .  "\n" 
    ); // 書き出し
  }

  fclose( $handle );

  fputs( STDERR, "Write: updated userlist\n" );
  return 0;
}
