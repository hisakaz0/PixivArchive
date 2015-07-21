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
  array_pop( $dumplist ); //最後の改行で空の要素が入るから削除

  foreach ( $dumplist as $row ){

    $user_id         = $row[0];
    $last_artwork_id = $row[1];

    // Error Cacht
    if ( $user_id != '' ){  // 空じゃないとき
      if ( @preg_grep( '/^\d+$/', $user_id ) ){ // user_idは数字だけのときだけ許す
        fputs(STDERR, "Error: user_id is only digit.\n");
        exit( 1 );
      }
    } else { // user_id が空の場合は終了
      fputs( STDERR, "Interrupt: Must write user_id field.\n" );
      exit( 1 );
    }

    if( $last_artwork_id != '' ){   // last_artwork_idがからのとき
      if ( @preg_grep( '/^\d+$/', $last_artwork_id ) ){ // last_artwork_idは数字だけ
        fputs(STDERR, "Error: last_artwork_id is only digit.\n");
        exit( 1 );
      }
    }

    $user = array( 'user_id' => $user_id, 'last_artwork_id' => $last_artwork_id );
    array_push( $userlist, $user );
  }

  fputs( STDERR, "Succeed: Read csv file '" . $file. "'.\n" );
  return( $userlist );

}
function WriteCsv ( $userlist, $userlist_file ){

  $handle = fopen( $userlist_file, 'w' );

  fputs( $handle, "user_id,last_artwork_id\n" ); // 列名を初めにかく
  foreach( $userlist as $user ){
    fputs( $handle, $user[0] . ',' . $user[1] . "\n" ); // 書き出し
    fputs( STDERR, "user_id: $user[0] / last_artwork_id: $user[1]\n" );
  }

  fclose( $handle );

  fputs( STDERR, "Write: updated userlist\n" );
  return 0;
}
