<?php

require_once dirname(__file__) . '/src/PixivArtWorkDownload.php';

$cookie_file = $argv[1];
$artwork_id  = $argv[2];

$url = 'http://www.pixiv.net/member_illust.php?mode=medium&illust_id=' . $artwork_id;

$ch = curl_init($url); // curlの初期設定
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // redirectionを有効化
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // プレーンテキストで出力
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file); // cookie情報を読み込む
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file); // cookie情報を保存する
$html = curl_exec($ch);
$info = curl_getinfo($ch); // 実行結果
curl_close($ch); // curl終了

print_r( $info );

$matchs = array();
$pattern = '/pixiv\.context\.ugokuIllustFullscreenData\s*=\s*{"?src"?:"?([\/\\\\\-:\.\w]+)"?,/';
preg_match( $pattern, $html , $matchs );
print_r( $matchs );

$referer = $url;
$ugoira_url = preg_replace( '/\\\\/', '', $matchs[1] ); // うごいらのzip のurl
$order = '';
$file_path = 'ugoira.zip';

DownloadImage(
  $artwork_id, $ugoira_url, $referer, $order, $file_path, $cookie_file );
$zip = new ZipArchive;
if ($zip->open($file_path) === TRUE) {
  $zip->extractTo('./');
  $zip->close();
  echo '成功\n';
  unlink( $file_path );
  echo "Remove a zip file '$file_path'.\n";
} else {
  echo '失敗';
}

?>
