
<?php

require_once dirname(__file__) . '/src/Csv.php';

$a = array( 'a', 'b', 'c', 'd' );


while( $i < count($a) ){
  if ( $a[$i] == 'b' ){
    array_splice( $a, $i, 1 );
  } else {
    $i = $i + 1;
  }
}

?>

