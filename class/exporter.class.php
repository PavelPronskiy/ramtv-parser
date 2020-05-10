<?php

namespace Exporter;

class Controller {

	function __construct($config) {
		$this->config = $config;
		// self::$instance = $this->instance();
	}

	public function getPosts()
	{
		return self::query([
			'type' => \PDO::FETCH_OBJ,
			'mode' => 'all',
			'prepare' => "SELECT *
				FROM " . $this->config->sql->tables->posts . " AS p ORDER BY p.ID desc limit 2",
			'execute' => []
		]);
	}

	public function getPost($post_id)
	{
		return self::query([
			'type' => \PDO::FETCH_OBJ,
			'mode' => 'row',
			'prepare' => "SELECT *
				FROM " . $this->config->sql->tables->posts . " AS p
					WHERE p.ID = :post_id",
			'execute' => [
				':post_id' => $post_id
			]
		]);
	}

	public function checkExistPost($post_id) : bool
	{
		return $this->getPost($post_id) ? true : false;
	}

	public function relativeNewsCategory($value)
	{
		return $value->video ? $this->config->news_video_id : $this->config->news_text_id;
	}

	public function updateSearchFilterTermResults($value)
	{
		$sft_news_ids = self::query([
			'type' => \PDO::FETCH_COLUMN,
			'mode' => 'row',
			'prepare' => "SELECT result_ids
				FROM " . $this->config->sql->tables->search_term_results . " AS p 
					WHERE field_name = :field_name
					AND field_value = :field_value",
			'execute' => [
				':field_name' => '_sft_news_categories',
				':field_value' => $this->relativeNewsCategory($value)
			]
		]);

		$sft_news_ids_int = [];
		
		foreach (explode(',', $sft_news_ids) as $sf) {
			$sft_news_ids_int[] = (int) $sf;
		}

		$sft_news_ids_int[] = (int) $value->ID;
		$search_term_results_imp = implode(',', $sft_news_ids_int);
		// var_dump($search_term_results_imp);

		self::query([
			'prepare' => "UPDATE " . $this->config->sql->tables->search_term_results
				. " SET result_ids = :result_ids
				WHERE id = :id",
			'execute' => [
				':id' => 40,
				':result_ids' => $search_term_results_imp
			]
		]);

	}

	public function insertSearchFilterCache($value)
	{
		self::query([
			'prepare' => "INSERT INTO " . $this->config->sql->tables->search_filter_cache . " (
				post_id,
				field_name,
				field_value_num,
				post_parent_id,
				term_parent_id
			) VALUES (
				:post_id,
				:field_name,
				:field_value_num,
				:post_parent_id,
				:term_parent_id
			)",
			'execute' => [
				':post_id' => $value->ID,
				':post_parent_id' => 0,
				':field_name' => '_sft_news_categories',
				':field_value_num' => $this->relativeNewsCategory($value),
				':term_parent_id' => 0
			]
		]);
	}

	public function getPostsMaxID()
	{
		return self::query([
			'type' => \PDO::FETCH_COLUMN,
			'mode' => 'row',
			'prepare' => "SELECT max(ID)
				FROM " . $this->config->sql->tables->posts,
			'execute' => []
		]);
	}

	public function insertPost($value)
	{
		self::query([
			'prepare' => "INSERT INTO " . $this->config->sql->tables->posts . " (
				ID,
				post_author,
				post_date,
				post_date_gmt,
				post_content,
				post_title,
				post_excerpt,
				post_status,
				comment_status,
				ping_status,
				post_password,
				post_name,
				to_ping,
				pinged,
				post_modified,
				post_modified_gmt,
				post_content_filtered,
				post_parent,
				guid,
				menu_order,
				post_type,
				post_mime_type,
				comment_count
			) VALUES (
				:ID,
				:post_author,
				:post_date,
				:post_date_gmt,
				:post_content,
				:post_title,
				:post_excerpt,
				:post_status,
				:comment_status,
				:ping_status,
				:post_password,
				:post_name,
				:to_ping,
				:pinged,
				:post_modified,
				:post_modified_gmt,
				:post_content_filtered,
				:post_parent,
				:guid,
				:menu_order,
				:post_type,
				:post_mime_type,
				:comment_count
			)",
			'execute' => [
				':ID' => $value->ID,
				':post_date' => $value->post_date,
				':post_date_gmt' => $value->post_date_gmt,
				':post_modified' => $value->post_date,
				':post_modified_gmt' => $value->post_date_gmt,
				':guid' => $value->guid,
				':post_title' => $value->post_title,
				':post_name' => $value->post_name,
				':post_content' => $value->post_content,
				':post_author' => 1,
				':post_excerpt' => '',
				':post_status' => 'publish',
				':comment_status' => 'closed',
				':ping_status' => 'closed',
				':post_password' => '',
				':to_ping' => '',
				':pinged' => '',
				':post_content_filtered' => '',
				':post_parent' => 0,
				':menu_order' => 0,
				':post_type' => 'news',
				':post_mime_type' => '',
				':comment_count' => 0
			]
		]);
	}

	public function insertImageAttach($value)
	{
		self::query([
			'prepare' => "INSERT INTO " . $this->config->sql->tables->posts . " (
				post_author,
				post_date,
				post_date_gmt,
				post_content,
				post_title,
				post_excerpt,
				post_status,
				comment_status,
				ping_status,
				post_password,
				post_name,
				to_ping,
				pinged,
				post_modified,
				post_modified_gmt,
				post_content_filtered,
				post_parent,
				guid,
				menu_order,
				post_type,
				post_mime_type,
				comment_count
			) VALUES (
				:post_author,
				:post_date,
				:post_date_gmt,
				:post_content,
				:post_title,
				:post_excerpt,
				:post_status,
				:comment_status,
				:ping_status,
				:post_password,
				:post_name,
				:to_ping,
				:pinged,
				:post_modified,
				:post_modified_gmt,
				:post_content_filtered,
				:post_parent,
				:guid,
				:menu_order,
				:post_type,
				:post_mime_type,
				:comment_count
			)",
			'execute' => [
				':post_date' => $value->post_date,
				':post_date_gmt' => $value->post_date_gmt,
				':post_modified' => $value->post_date,
				':post_modified_gmt' => $value->post_date_gmt,
				':guid' => DEFAULT_DOMAIN . $value->image_new_path_name,
				':post_title' => $value->image_name,
				':post_name' => $value->image_name,
				':post_content' => '',
				':post_author' => 1,
				':post_excerpt' => '',
				':post_status' => 'inherit',
				':comment_status' => 'closed',
				':ping_status' => 'closed',
				':post_password' => '',
				':to_ping' => '',
				':pinged' => '',
				':post_content_filtered' => '',
				':post_parent' => $value->ID,
				':menu_order' => 0,
				':post_type' => 'attachment',
				':post_mime_type' => 'image/jpeg',
				':comment_count' => 0
			]
		]);
	}

	public function insertPostTermRelationShips($value)
	{
		self::query([
			'prepare' => "INSERT INTO " . $this->config->sql->tables->term_relationships . " (
				object_id,
				term_taxonomy_id
			) VALUES (
				:object_id,
				:term_taxonomy_id
			)",
			'execute' => [
				':object_id' => $value->ID,
				':term_taxonomy_id' => $this->relativeNewsCategory($value)
			]
		]);
	}

	public function imageResizer($value)
	{

		// medium
		$this->imageResize([
			'orig_file' => $value->image_orig_file,
			'out_file' => $value->thumb_medium_file,
			'width' => $value->thumb_medium_width,
			'height' => $value->thumb_medium_height
		], $value->image_date_path);

		// medium_large
		$this->imageResize([
			'orig_file' => $value->image_orig_file,
			'out_file' => $value->thumb_medium_large_file,
			'width' => $value->thumb_medium_large_width,
			'height' => $value->thumb_medium_large_height
		], $value->image_date_path);
		
		// large
		$this->imageResize([
			'orig_file' => $value->image_orig_file,
			'out_file' => $value->thumb_large_file,
			'width' => $value->thumb_large_width,
			'height' => $value->thumb_large_height
		], $value->image_date_path);
		
		// thumbnail
		$this->imageResize([
			'orig_file' => $value->image_orig_file,
			'out_file' => $value->thumb_thumbnail_file,
			'width' => $value->thumb_thumbnail_width,
			'height' => $value->thumb_thumbnail_height
		], $value->image_date_path);
		
		// img_262x173
		$this->imageResize([
			'orig_file' => $value->image_orig_file,
			'out_file' => $value->thumb_img_262x173_file,
			'width' => $value->thumb_img_262x173_width,
			'height' => $value->thumb_img_262x173_height
		], $value->image_date_path);
		
		// img_555x280
		$this->imageResize([
			'orig_file' => $value->image_orig_file,
			'out_file' => $value->thumb_img_555x280_file,
			'width' => $value->thumb_img_555x280_width,
			'height' => $value->thumb_img_555x280_height
		], $value->image_date_path);
		
		// featured_large
		$this->imageResize([
			'orig_file' => $value->image_orig_file,
			'out_file' => $value->thumb_featured_large_file,
			'width' => $value->thumb_featured_large_width,
			'height' => $value->thumb_featured_large_height
		], $value->image_date_path);
		
		// var_dump($image_meta);

		// exit;

		// $imagick = new \Imagick($value->);
		// $bname = pathinfo($image_file);
		// $new_image_file = $bname['dirname'] . '/' . $bname['filename'] . '-' . $width . 'x' . $height . '.' . $bname['extension'];
		

		// $thumb->readImage($new_image_file);
		// $thumb->destroy();

		// return $new_image_file;
	}

	public function imageResize($image, $date_path)
	{
		$date_path_abs = PATH_WWW . '/wp-content/uploads/';
		$orig_file = $date_path_abs . $date_path . '/' . $image['orig_file'];
		$out_file = $date_path_abs . $date_path . '/' . $image['out_file'];
		
		if (!is_file($out_file)) {
			$imagick = new \Imagick($orig_file);
			$imagick->resizeImage($image['width'], $image['height'], \Imagick::FILTER_LANCZOS,1);
			$imagick->writeImage($out_file);
			$imagick->destroy();
		} else {
			echo 'File exists: ' . $out_file . PHP_EOL;
		}
	}

	public function insertPostmetaImageAttach($value, $image_meta)
	{
		self::query([
			'prepare' => "INSERT INTO " . $this->config->sql->tables->postmeta . " (post_id, meta_key, meta_value) VALUES (:post_id, :meta_key, :meta_value)",
			'execute' => [
				':post_id' => $value->ID,
				':meta_key' => '_thumbnail_id',
				':meta_value' => $value->ID + 1
			]
		]);
		self::query([
			'prepare' => "INSERT INTO " . $this->config->sql->tables->postmeta . " (post_id, meta_key, meta_value) VALUES (:post_id, :meta_key, :meta_value)",
			'execute' => [
				':post_id' => $value->ID + 1,
				':meta_key' => '_wp_attached_file',
				':meta_value' => $value->attached_file
			]
		]);
		self::query([
			'prepare' => "INSERT INTO " . $this->config->sql->tables->postmeta . " (post_id, meta_key, meta_value) VALUES (:post_id, :meta_key, :meta_value)",
			'execute' => [
				':post_id' => $value->ID + 1,
				':meta_key' => '_wp_attachment_metadata',
				':meta_value' => $image_meta
			]
		]);
	}

	public function saveImageAttach($value)
	{
		$get_image_content = file_get_contents($value->image_url);
		
		if (!is_dir(PATH_WWW . $value->image_new_path)) {
			mkdir(PATH_WWW . $value->image_new_path, 0755, true);
		}

		if (!is_file(PATH_WWW . $value->image_new_path_name)) {
			file_put_contents(PATH_WWW . $value->image_new_path_name, $get_image_content);

		}


		$imagick = new \Imagick(PATH_WWW . $value->image_new_path_name);
		$imagick->readImage(PATH_WWW . $value->image_new_path_name);
		$image_size = $imagick->getSize(); 
		$imagick->destroy();
		$image_file_name = pathinfo($value->image_new_path_name);

		$value->image_orig_width = $image_size['columns'];
		$value->image_orig_height = $image_size['rows'];
		$value->image_orig_file = $image_file_name['basename'];
		
		$value->thumb_medium_width = 300;
		$value->thumb_medium_height = 225;
		$value->thumb_medium_file = $image_file_name['filename'] .
			'-' . $value->thumb_medium_width .
			'x' . $value->thumb_medium_height .
			'.' . $image_file_name['extension'];
		
		$value->thumb_large_width = 1024;
		$value->thumb_large_height = 768;
		$value->thumb_large_file = $image_file_name['filename'] .
			'-' . $value->thumb_large_width .
			'x' . $value->thumb_large_height .
			'.' . $image_file_name['extension'];


		$value->thumb_thumbnail_width = 150;
		$value->thumb_thumbnail_height = 150;
		$value->thumb_thumbnail_file = $image_file_name['filename'] .
			'-' . $value->thumb_thumbnail_width .
			'x' . $value->thumb_thumbnail_height .
			'.' . $image_file_name['extension'];


		$value->thumb_medium_large_width = 768;
		$value->thumb_medium_large_height = 576;
		$value->thumb_medium_large_file = $image_file_name['filename'] .
			'-' . $value->thumb_medium_large_width .
			'x' . $value->thumb_medium_large_height .
			'.' . $image_file_name['extension'];

		
		$value->thumb_img_262x173_width = 262;
		$value->thumb_img_262x173_height = 173;
		$value->thumb_img_262x173_file = $image_file_name['filename'] .
			'-' . $value->thumb_img_262x173_width .
			'x' . $value->thumb_img_262x173_height .
			'.' . $image_file_name['extension'];

		
		$value->thumb_img_555x280_width = 555;
		$value->thumb_img_555x280_height = 280;
		$value->thumb_img_555x280_file = $image_file_name['filename'] .
			'-' . $value->thumb_img_555x280_width .
			'x' . $value->thumb_img_555x280_height .
			'.' . $image_file_name['extension'];
		
		$value->thumb_featured_large_width = 640;
		$value->thumb_featured_large_height = 294;
		$value->thumb_featured_large_file = $image_file_name['filename'] .
			'-' . $value->thumb_featured_large_width .
			'x' . $value->thumb_featured_large_height .
			'.' . $image_file_name['extension'];
		
		$this->imageResizer($value);

		return serialize([
			'width' => $value->image_orig_width,
			'height' => $value->image_orig_height,
			'file' => $value->image_date_path . '/' . $value->image_orig_file,
			'sizes' => [
				'medium' => [
					'file' => $value->thumb_medium_file,
					'width' => $value->thumb_medium_width,
					'height' => $value->thumb_medium_height,
					'mime-type' => 'image/jpeg',
				],
				'medium_large' => [
					'file' => $value->thumb_medium_large_file,
					'width' => $value->thumb_medium_large_width,
					'height' => $value->thumb_medium_large_height,
					'mime-type' => 'image/jpeg',
				],
				'large' => [
					'file' => $value->thumb_large_file,
					'width' => $value->thumb_large_width,
					'height' => $value->thumb_large_height,
					'mime-type' => 'image/jpeg',
				],
				'thumbnail' => [
					'file' => $value->thumb_thumbnail_file,
					'width' => $value->thumb_thumbnail_width,
					'height' => $value->thumb_thumbnail_height,
					'mime-type' => 'image/jpeg',
				],
				'img_262x173' => [
					'file' => $value->thumb_img_262x173_file,
					'width' => $value->thumb_img_262x173_width,
					'height' => $value->thumb_img_262x173_height,
					'mime-type' => 'image/jpeg',
				],
				'img_555x280' => [
					'file' => $value->thumb_img_555x280_file,
					'width' => $value->thumb_img_555x280_width,
					'height' => $value->thumb_img_555x280_height,
					'mime-type' => 'image/jpeg',
				],
				'featured_large' => [
					'file' => $value->thumb_featured_large_file,
					'width' => $value->thumb_featured_large_width,
					'height' => $value->thumb_featured_large_height,
					'mime-type' => 'image/jpeg',
				],
			],
			'image_meta' => [
				'aperture' => '0',
				'credit' => '',
				'camera' => '',
				'caption' => '',
				'created_timestamp' => '0',
				'copyright' => '',
				'focal_length' => '0',
				'iso' => '0',
				'shutter_speed' => '0',
				'title' => '',
				'orientation' => '0',
				'keywords' => [],
			]
		]);
	}

	public function insertPostMeta($value)
	{
		// cid
		self::query([
			'prepare' => "INSERT INTO " . $this->config->sql->tables->postmeta . " (post_id, meta_key, meta_value) VALUES (:post_id, :meta_key, :meta_value)",
			'execute' => [
				':post_id' => $value->ID,
				':meta_key' => 'cid',
				':meta_value' => ''
			]
		]);

		// _cid
		self::query([
			'prepare' => "INSERT INTO " . $this->config->sql->tables->postmeta . " (post_id, meta_key, meta_value) VALUES (:post_id, :meta_key, :meta_value)",
			'execute' => [
				':post_id' => $value->ID,
				':meta_key' => '_cid',
				':meta_value' => 'field_5e4fd69799e6f'
			]
		]);


		// region
		self::query([
			'prepare' => "INSERT INTO " . $this->config->sql->tables->postmeta . " (post_id, meta_key, meta_value ) VALUES (:post_id, :meta_key, :meta_value)",
			'execute' => [
				':post_id' => $value->ID,
				':meta_key' => 'region',
				':meta_value' => 'Раменское'
			]
		]);

		// _region
		self::query([
			'prepare' => "INSERT INTO " . $this->config->sql->tables->postmeta . " (post_id, meta_key, meta_value) VALUES (:post_id, :meta_key, :meta_value)",
			'execute' => [
				':post_id' => $value->ID,
				':meta_key' => '_region',
				':meta_value' => 'field_5e4fd69b99e70'
			]
		]);

		// actual
		self::query([
			'prepare' => "INSERT INTO " . $this->config->sql->tables->postmeta . " (post_id, meta_key, meta_value) VALUES (:post_id, :meta_key, :meta_value)",
			'execute' => [
				':post_id' => $value->ID,
				':meta_key' => 'actual',
				':meta_value' => ''
			]
		]);

		// _actual
		self::query([
			'prepare' => "INSERT INTO " . $this->config->sql->tables->postmeta . " (post_id, meta_key, meta_value) VALUES (:post_id, :meta_key, :meta_value)",
			'execute' => [
				':post_id' => $value->ID,
				':meta_key' => '_actual',
				':meta_value' => 'field_5e097168e7a7b'
			]
		]);

	}

	private function pdoErrorHandler($error)
	{
		switch ($error->getCode()) {
			case 2002: # offline
				$message = 'Project database: ' . $this->config->sql->db . ' is offline.';
				break;
			default:
				$message = 'PDO return unknown error: ' . $error->getMessage();
				break;
		}
		
		throw new \Exception($message);
	}

	public function query(array $param)
	{
		$ret = [];
		try {
			if (empty(self::$instance)) {
				$this->instance = $this->instance();
			}
		} catch (\PDOException $error) {
			return $this->pdoErrorHandler($error);
		}

		$sql = $this->instance->prepare($param['prepare']);

		$execute = isset($param['execute'])
			? $param['execute']
			: [];

		$type = isset($param['type'])
			? $param['type']
			: \PDO::FETCH_OBJ;

		$ret = $sql->execute($execute);

		if (isset($param['lastInsertId'])) {
			$ret = $this->instance->lastInsertId();
		}

		// $err = $sql->errorInfo();
		$errors_detect = $sql->errorInfo();
		if (isset($errors_detect[0]) && $errors_detect[0] != '00000') {
			return $this->registerErrors($errors_detect);
		}

		if (isset($param['mode'])) {
			switch ($param['mode']) {
				case 'all':
					$ret = $sql->fetchAll($type);
					break;
				case 'row':
					$ret = $sql->fetch($type);
					break;
			}
		}

		$sql->closeCursor();
		$execute = null;
		$type = null;
		$param = null;
		$sql = null;
		return $ret;
	}

	public function instance() : object
	{
		return new \PDO(
			'mysql:host=' . $this->config->sql->host . ';dbname=' .
			$this->config->sql->db,
			$this->config->sql->user,
			$this->config->sql->pass,
			[
				\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
				\PDO::ATTR_PERSISTENT => true,
				\PDO::ATTR_TIMEOUT => 200
			]
		);
	}

	public function registerErrors($message) : void
	{
		echo json_encode($message) . PHP_EOL;
		exit;
	}
}