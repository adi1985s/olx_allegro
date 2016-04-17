<?php

	class db {

		private static $dbh;
		private $lastId;

		public function __construct($host, $user, $password, $dbname) {

			$dsn = "mysql:dbname={$dbname};host={$host};charset=utf8";
			$options = array(
			  PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
			);
			try {
				self::$dbh = new PDO($dsn, $user, $password, $options);
			} catch (PDOException $e) {
				echo 'Connection failed: ' . $e->getMessage();
			}

		}

		private function setLastId() {
			$this->lastId = self::$dbh->lastInsertId();
		}

		public function getLastId() {
			return $this->lastId;
		}

		public function getAllProductsUrl() {
			$query = 'SELECT id, url FROM products';
			$stmt = self::$dbh->prepare($query);
			$stmt->execute();
			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

			return $result;
		}

		public function productExists($url) {
			$url_md5 = md5($url);
			$query = 'SELECT COUNT(*) FROM products WHERE url_md5 = :url_md5';
			$stmt = self::$dbh->prepare($query);
			$stmt->execute(array(':url_md5' => $url_md5));
			$result = true;

			if($stmt->fetchColumn()) {
				$result = true;
			} else {
				$result = false;
			}

			return $result;
		}

		public function addProduct($product) {
			$data = array(
				':id_cat' => $this->getCategoryId($product['category']),
				':title' => $product['title'],
				':url' => $product['url'],
				':img' => $product['mainImage'],
				':price' => $product['price'],
				':description' => $product['description'],
				':url_md5' => md5($product['url'])
			);

			$query = "INSERT INTO `products` (`id`, `id_cat`, `title`, `url`, `img`, `price`, `description`, `url_md5`) VALUES (NULL, :id_cat, :title, :url, :img, :price, :description, :url_md5)";
			$stmt = self::$dbh->prepare($query);
			$stmt->execute($data);
		}

		public function getProduct($hook, $is_url = false) {
			if(!$is_url) {
				$id = (int)$hook;
				$query = 'SELECT * FROM products WHERE id = :id';
				$stmt = self::$dbh->prepare($query);
				$stmt->execute(array(':id' => $hook));

				$result = $stmt->fetch(PDO::FETCH_ASSOC);
				return $result;
			} else {
				$url_md5 = md5($hook);
				$query = 'SELECT * FROM products WHERE url_md5 = :url_md5';
				$stmt = self::$dbh->prepare($query);
				$stmt->execute(array(':url_md5' => $url_md5));

				$result = $stmt->fetch(PDO::FETCH_ASSOC);
			}

			return $result;
		}

		public function updateProduct($id, $product) {
			$id = (int)$id;

			$data = array(
				':id_cat' => $this->getCategoryId($product['category']),
				':title' => $product['title'],
				':img' => $product['mainImage'],
				':price' => $product['price'],
				':description' => $product['description'],
				':id' => $id
			);

			$query = "UPDATE `products` SET `id_cat` = :id_cat, `title` = :title, `img` = :img, `price` = :price, `description` = :description WHERE `products`.`id` = :id";
			$stmt = self::$dbh->prepare($query);
			$stmt->execute($data);
		}

		public function deleteProduct($id) {
			$id = (int)$id;
			$query = 'DELETE FROM products WHERE id = :id';
			$stmt = self::$dbh->prepare($query);
			$stmt->execute(array(':id' => $id));
		}

		public function getAllCategories() {
			$query = 'SELECT name_md5 FROM categories';
			$stmt = self::$dbh->prepare($query);
			$stmt->execute();
			$result = $stmt->fetchAll(PDO::FETCH_CLASS);

			return $result;
		}

		public function categoryExists($name) {
			$name_md5 = md5($name);
			$query = 'SELECT COUNT(*) FROM categories WHERE name_md5 = :name_md5';
			$stmt = self::$dbh->prepare($query);
			$stmt->execute(array(':name_md5' => $name_md5));
			$result = true;

			if($stmt->fetchColumn()) {
				$result = true;
			} else {
				$result = false;
			}

			return $result;
		}

		public function addCategory($name) { 
			$name_md5 = md5($name);

			$query = "INSERT INTO `categories` (`id`, `name`, `name_md5`) VALUES (NULL, :name, :name_md5)";
			$stmt = self::$dbh->prepare($query);
			$stmt->execute(array(':name' => $name, ':name_md5' => $name_md5));
		}

		public function getCategoryId($name) {
			$name_md5 = md5($name);

			$query = 'SELECT id FROM categories WHERE name_md5 = :name_md5';
			$stmt = self::$dbh->prepare($query);
			$stmt->execute(array(':name_md5' => $name_md5));

			$result = $stmt->fetch();
			return $result['id'];
		}

	}

?>