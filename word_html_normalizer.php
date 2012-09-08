<?php
libxml_use_internal_errors(true);
set_time_limit(0);

//Get File
if(!isset($argv[1]) || !file_exists($argv[1]) || !is_readable($argv[1])){
	print "[Error] ファイルが取得できませんでした。\n";
	die();
}

//File contents
$file = mb_convert_encoding(file_get_contents($argv[1]), 'utf-8', 'sjis-win');
//echo mb_substr($file, 0, 10000, 'utf-8');
//die();

//強調をemに変える
$file = preg_replace("/<span(\t|\n)style='font-emphasize:accent'>([^<]*)<\/span>/u", '<em>$2</em>', $file);

//明朝体を消す
$file = preg_replace("/\"ＭＳ 明朝\"/u", "ＭＳ明朝", $file);

//メタタグを消す
$file = preg_replace("/<meta[^>]+>\n/u", "", $file);

//リンクタグを直す
$file = preg_replace("/<link([^>]+)>/u", "<link$1 />", $file);

//スタイル属性がおかしい
if(preg_match("/ style='/", $file)){
	print "スタイル属性を消去します...\n";
	$file = preg_replace("/(\n| )?style='([^']+)'/um", "", $file);
	print "スタイル属性の消去が完了しました。\n";
}

//タグの途中で切れてるものを修正
$file = preg_replace("/<(br|span)\n/u", "<$1 ", $file);

//brを修正
$file = preg_replace("/<br([^>]*?)>/", "<br$1 />", $file);

//nbspを修正
$file = preg_replace("/&nbsp;/", ' ', $file);

$file = preg_replace("/&ocirc;/", 'ô', $file);

//属性値
$reg = "/(clear|rel|class|name|content|align|lang)=([^> ]+)/";
if(preg_match($reg, $file, $match)){
	print "属性値が整形式ではありません。修正します...\n";
	//属性値を消す
	$file = preg_replace($reg, "$1=\"$2\"", $file);
	
	$file = preg_replace("/;\n/u", ";", $file);
	//lang属性消す
	$file = preg_replace("/ ?lang=\"[^\"]+\"/u", "", $file);
	print "属性値の修正が完了しました。\n";
}

//spanタグを消す
$file = preg_replace("/<\/?span>/u", "", $file);

//rpタグを消す
$file = preg_replace("/<rp>[^<]*?<\/rp>/u", "", $file);

//o:pを消す
$file = preg_replace("/<\/?o:p>/u", "", $file);

//pタグの全角スペースを消す
$file = preg_replace("/(<p[^>]*?>)　([^　])/u", "$1$2", $file);

$simple_dom = simplexml_load_string('<?xml version="1.0" encoding="utf-8" ?>'.$file);
/*
 * $dom = new DOMDocument();
 * $dom->loadHTML($file);
 */
if(!$simple_dom || !($dom = dom_import_simplexml($simple_dom))){
	print "エラーが発生しました\n";
	foreach(libxml_get_errors() as $error) {
		echo "\t{$error->message}\n";
	}
	exit;
}else{
	print "XMLとしてファイルを読み込みました。\n";
}

//pタグのスペースを消す
/* @var $dom DOMElement*/
$ps = $dom->getElementsByTagName('p');
foreach($ps as $p){
	/* @var $p DOMElement */
	$text = $p->textContent;
	if(preg_match("/^(「|『|（)/u", $text)){
		$p->setAttribute('class', 'no-indent');
	}else{
		$p->setAttribute('class', 'indent');
	}
}


$new_name = dirname(realpath($argv[1]))."/newtxt.html";
file_put_contents($new_name, $dom->C14N());

echo "処理を終了しました\n";

exit;