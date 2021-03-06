<?php

	class allegro {

		private $_userId;
		private $_url;
		private $_pagesCount;
		private $_links;
		private $_auctionData;
		private $_errors = array();

		private function printErrors() {
			if(!empty($this->_errors)) {
				echo '<pre>';
				foreach($this->_errors as $error) {
					echo $error . "\n\n";
				}
				echo '</pre>';
			}
		}

		public function setUserId($userId) {
			if(!is_int($userId))
				$this->_errors[] = 'USER ID musi składać się wyłącznie z cyfr.';

			$this->_userId = (int)$userId;
		}

		private function getUserId() {
			return $this->_userId;
		}

		public function setUrl($url) {
			if(!filter_var($url, FILTER_VALIDATE_URL)) {
				$this->_errors[] = 'Nieprawidłowy adres URL.';
				$this->_url = false;
			} else {
				$this->_url = $url;
			}
		}

		private function getUrl() {
			return $this->_url;
		}

		private function buildQuery($us_id, $p = 1, $limit = 180) {
			$limit = (int)$limit;
			$p = (int)$p;
			$us_id = (int)$us_id;

			$query = array();
			$query['limit'] = $limit;
			$query['p'] = $p;
			$query['us_id'] = $us_id;
			$query['t'] = 1;

			return http_build_query($query);
		}

		private function getSource($url) {
			$source = file_get_contents($url);
			$source = preg_replace("/[\r\n]+/", "\n", $source);
			$source = preg_replace("/\s+/", ' ', $source);

			return $source;
		}

		private function setPagesCount() {
			$url = $this->getUrl() . '?' . $this->buildQuery($this->getUserId());
			$source = $this->getSource($url);

			preg_match_all('!<ul class="pager-nav">(.*?)</ul>!', $source, $out);

			$source = $out[0][1];
			preg_match_all('!<a href="(.*?)">!', $source, $out);

			if(count($out[1]) < 2) {
				$this->_pagesCount = 1;
			} else {
				$url = $out[1][count($out[1])-2];
				preg_match('!&p=(\d+)&!', $url, $out);
				$this->_pagesCount = (int)trim($out[1]);
			}
		}

		private function getPagesCount() {
			return $this->_pagesCount;
		}

		private function setLinks() {
			$this->_links = array();
			$pageLimit = $this->getPagesCount();

			for($i = 0; $i < $pageLimit; $i++) {
				$url = $this->getUrl() . '?' . $this->buildQuery($this->getUserId(), $i+1);
				$source = $this->getSource($url);

				//preg_match('!<section class="offers">(.*?)</section>!', $source, $out);
				//print_r($source);
				preg_match_all('!<header> <h2><a href="(.*?)"!', $source, $links);

				$this->_links = array_merge($this->_links, $links[1]);
			}
		}

		public function getLinks() {
			return $this->_links;
		}

		private function getTitle($source) {
			preg_match('!<meta property="og:title" content="(.*?)" />!', $source, $out);

			return trim($out[1]);
		}

		private function getPrice($source) {
			preg_match('!data-price="(.*?)"!', $source, $out);
			if(!preg_match('!\.!', $out[1])) {
				return trim($out[1]/100);
			} else {
				return $out[1];
			}
		}

		private function getDescription($source) {
			preg_match('!"idItem":(.*?)\,!', $source, $out);
			if(empty($out[1])) {
				preg_match("!'idItem':(.*?)\,!", $source, $out);
			}
			$id = $out[1];
			$source = $this->getSource('http://allegro.pl/showitem/description/legacy/' . $id . '?iframe=iframe_src');
			preg_match('#<div class="right">(.*?)</div> </div> </div> <!-- main-->#', $source, $out);
			if(empty($out[1])) {
				preg_match('#<div class="right" style="text-align: center;">(.*?)</div> </div> </div> <!-- main-->#', $source, $out);
			}
			$out = $out[1];
			$out = preg_replace('!<span class="title red" style="font-size: 18pt;">(.*?)</span>!', '', $out);
			$out = preg_replace('!<p>&nbsp;</p>!', '', $out);
			$out = preg_replace('!<p style="text-align: center;">&nbsp;</p>!', '', $out);
			$out = strip_tags($out, '<br><p>');
			$out = preg_replace('! style="([^"]+)"!', '', $out);
			$out = trim($out);
			$out = preg_replace('!&nbsp;!', '', $out);
			$out = preg_replace('!<p> </p>!', '', $out);
			$out = preg_replace('!<p><br /></p>!', '', $out);

			return $out;
		}

		private function getCategory($source) {
			preg_match("#headNavigation\':\'(.*?)\|#", $source, $out);
			if(empty($out[1])) {
				preg_match('!headNavigation":"(.*?)\|!', $source, $out);
			}
			
			return trim($out[1]);
		}

		private function getMainImage($source) {
			preg_match('!large: \["(.*?)"!', $source, $out);
			if(empty($out[1])) {
				preg_match('!data-img-large="(.*?)"!', $source, $out);
				$out[1] = preg_replace('!//!', 'http://', $out[1]);
			}

			return $out[1];
		}

		public function setAuctionData($link) {
			$source = $this->getSource('http://allegro.pl' . $link);

			$this->_auctionData = array();
			$this->_auctionData['title'] = $this->getTitle($source);
			$this->_auctionData['price'] = $this->getPrice($source);
			$this->_auctionData['description'] = $this->getDescription($source);
			$this->_auctionData['category'] = $this->getCategory($source);
			$this->_auctionData['mainImage'] = $this->getMainImage($source);
			$this->_auctionData['url'] = 'http://allegro.pl' . $link;
		}

		public function getAuctionData() {
			return $this->_auctionData;
		}

		public function start($debug_mode = 0) {
			if($debug_mode)
				$this->printErrors();

			$this->setPagesCount(); // pobieramy ilość stron
			$this->setLinks(); // pobieranie linków do wszystkich aukcji użytkownika
		}

	}


?>