<?php

function ReadCsv ( $file ){

  $csv  = new splfileobject($file);
  $csv->setFlags(SplFileObject::READ_CSV);
  $dumplist = array();
  $userlist = array();

  foreach ( $csv as $row ){
    array_push( $dumplist, $row);
  }
  array_shift($dumplist);


  foreach ( $dumplist as $row ){

    // Error Cacht
    if ( preg_grep( '/^\d+$/', $row[0] ) ){ // user_idは数字だけ
      fputs(STDERR, "Error: user_id is only digit.\n");
      exit( 1 );
    }
    if ( preg_grep( '/^\d+$/', $row[1] ) ){ // last_artwork_idは数字だけ
      fputs(STDERR, "Error: last_artwork_id is only digit.\n");
      exit( 1 );
    }

    if ( $row[0] != null ){ // userlistに格納してく
      $user = array(
        'user_id' => $row[0],
        'last_artwork_id' => $row[1],
        'display_name' => $row[2]
      );
      array_push( $userlist, $user );
    }
  }

  return( $userlist );
}
