<?php

	error_reporting(E_ALL);
	ini_set('display_errors', 'On');
	ini_set('max_execution_time', 0);

	header('Content-Type: text/html; charset=utf-8');

	require_once 'config.php';
	require_once 'class/allegro.class.php';
	require_once 'class/db.class.php';

	$db = new db($config['db']['host'], $config['db']['user'], $config['db']['password'], $config['db']['dbname']);

	$allegro = new allegro;
	$allegro->setUserId($config['allegro']['userid']);
	$allegro->setUrl($config['allegro']['url']);
	$allegro->start(1);

	/* pobieranie wszystkich linków do aukcji allegro*/
	$auctionsLinks = $allegro->getLinks();

	/* pobieranie wszystkich produktów z bazy danych */
	$allProductsUrl = $db->getAllProductsUrl();

	/* porówanie url aukcji allegro z url produktów z bazy danych, usuwamy rekordy z bazy, ktorych nie ma w tablicy $auctionsLinks */
	foreach($allProductsUrl as $k => $v) {
		if(!in_array(preg_replace('!http://allegro.pl!', '', $v['url']), $auctionsLinks))
			$db->deleteProduct($v['id']);
	}

	/* pobieranie info o produkcie */
	foreach($auctionsLinks as $k => $v) {
		$allegro->setAuctionData($v);
		$product = $allegro->getAuctionData();

		if(!$db->categoryExists($product['category'])) {
			$db->addCategory($product['category']);
		}

		if($db->productExists($product['url'])) {

			$productFromDb = $db->getProduct($product['url'], 1);
			if(md5($productFromDb['title']) != md5($product['title'])
				|| md5($productFromDb['img']) != md5($product['mainImage'])
				|| md5($productFromDb['price']) != md5($product['price'])
				|| md5($productFromDb['description']) != md5($product['description'])) {

				$db->updateProduct($productFromDb['id'], $product);

			}
		} else {
			$db->addProduct($product);
		}

		flush();
	}
	

?>