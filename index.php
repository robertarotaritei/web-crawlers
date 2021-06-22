<?php

$start = "test.html";
$already_crawled = array();
$crawling = array();

function get_doc($url) {
    $options = array(
        'http'=>array(
            'method'=>"GET", 
            'headers'=>"User-Agent: howBot/0.1\n"
        ), 
        "ssl"=>array(
            "verify_peer"=>false,
            "verify_peer_name"=>false,
        )
    );
	$context = stream_context_create($options);
	$doc = new DOMDocument();
	@$doc->loadHTML(@file_get_contents($url, false, $context));

    return $doc;
}

function get_details($url) {
	$doc = get_doc($url);

	$title = $doc->getElementsByTagName("title");
	$title = $title->item(0)->nodeValue;
	$keywords = "";
	$metas = $doc->getElementsByTagName("meta");
	for ($i = 0; $i < $metas->length; $i++) {
		$meta = $metas->item($i);
		if (strtolower($meta->getAttribute("name")) == "keywords")
			$keywords = $meta->getAttribute("content");

	}
	return '{ "Title": "'.str_replace("\n", "", $title).'", "Keywords": "'.str_replace("\n", "", $keywords).'", "URL": "'.$url.'"},';

}

function follow_links($url) {
    global $already_crawled;
	global $crawling;
    $doc = get_doc($url);

    $linklist = $doc->getElementsByTagName("a");
    foreach ($linklist as $link) {
        $l =  $link->getAttribute("href");

		if (substr($l, 0, 1) == "/" && substr($l, 0, 2) != "//") {
			$l = parse_url($url)["scheme"]."://".parse_url($url)["host"].$l;
		} else if (substr($l, 0, 2) == "//") {
			$l = parse_url($url)["scheme"].":".$l;
		} else if (substr($l, 0, 2) == "./") {
			$l = parse_url($url)["scheme"]."://".parse_url($url)["host"].dirname(parse_url($url)["path"]).substr($l, 1);
		} else if (substr($l, 0, 1) == "#") {
			$l = parse_url($url)["scheme"]."://".parse_url($url)["host"].parse_url($url)["path"].$l;
		} else if (substr($l, 0, 3) == "../") {
			$l = parse_url($url)["scheme"]."://".parse_url($url)["host"]."/".$l;
		} else if (substr($l, 0, 11) == "javascript:") {
		    continue;
		} else if (substr($l, 0, 5) != "https" && substr($l, 0, 4) != "http") {
			$l = parse_url($url)["scheme"]."://".parse_url($url)["host"]."/".$l;
		}
	
		if (!in_array($l, $already_crawled)) {
				$already_crawled[] = $l;
				$crawling[] = $l;
                echo get_details($l)."\n";
		}
    }
    array_shift($crawling);
	foreach ($crawling as $site) {
		follow_links($site);
	}
}

follow_links($start);