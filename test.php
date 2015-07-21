<?php

$html_file = $argv[1];
$content = file_get_contents( $html_file );
$matchs = array();
$pattern = '/pixiv\.context\.ugokuIllustFullscreenData\s*=\s*{"?src"?:"?([\/\\\\\-:\.\w]+)"?,/';

preg_match( $pattern, $content, $matchs );

print_r( $matchs );

?>
