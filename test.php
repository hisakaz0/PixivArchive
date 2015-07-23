
<?php

require_once dirname(__file__) . '/src/Csv.php';


$file = $argv[1];
$userlist =  ReadCsv( $file );

for ( $i = 0; $i < count( $userlist ); $i++ ){ // 一つ一つ取り出し

  // ユーザ情報を user_id, last_artwork_id, display_nameに分解
  $user_id         = $userlist[$i]['user_id'];
  @$last_artwork_id = $userlist[$i]['last_artwork_id'];
  @$display_name    = $userlist[$i]['display_name'];

  $last_artwork_id = 'hisa';
  $display_name    = 'kuso';

  $userlist[$i]['last_artwork_id'] = $last_artwork_id;
  $userlist[$i]['display_name']    = $display_name;

  print_r( $userlist );
  WriteCsv ( $userlist, $file ); //書き込み
  print_r( $userlist );
}


?>

