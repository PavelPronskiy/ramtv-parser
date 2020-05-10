<?php

namespace Parser;

use \Carbon\Carbon;

class Controller
{
	function __construct() {
		$this->curl = \curl_init();
		$this->dom = new \DOMDocument;
		$this->config = Config::getConfig();
		// $this->config = $config;
		$this->parseArchive();
	}

	function __destruct()
	{
		curl_close($this->curl);
	}

	private function get($url)
	{
		curl_setopt($this->curl, CURLOPT_URL, $url);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->config->headers);
		curl_setopt($this->curl, CURLOPT_HEADER, true);
		// curl_setopt($this->curl, CURLOPT_ENCODING, "gzip");

		if (VERBOSE) {
		   curl_setopt($this->curl, CURLOPT_VERBOSE, true);
		}

		$ctype = curl_getinfo($this->curl, CURLINFO_CONTENT_TYPE);
		$results = curl_exec($this->curl);

		$charset = null;

		if ($ctype !== null && preg_match('%charset=([\w-]+)%i', $ctype, $matches)) {
			$charset = $matches[1];
		}

		if ($charset && strtoupper($charset) !== 'UTF-8') {
			$results = \iconv($charset, 'UTF-8', $results);
		}

		// var_dump($results);
		return $results;
	}

	private function parseArchive()
	{

		$exporter = new \Exporter\Controller($this->config);
		$archive_links = $this->getArchiveLinksRange();
		$maxID = (int) $exporter->getPostsMaxID() + 1;

		foreach ($archive_links as $link) {
		// var_dump($maxID);
			$date_links = $this->getArchiveLinksByDate($this->qp($link));
			foreach ($date_links as $link_item) {
				$item = $this->getArchiveNewsItem($this->qp($link_item));
				
				if (!isset($item->image_url)) {
					continue;
				}

				$item->ID = $maxID++;
				
				if ($exporter->checkExistPost($item->ID)) {
					echo 'Exists post_id: ' . $item->ID . PHP_EOL;
					continue;
				}
				
				$item->guid = 'https://ramtv.ru/?post_type=news&p=' . $item->ID;
				if (DRY_RUN === false) {
					$exporter->insertPost($item);
					$exporter->insertPostTermRelationShips($item);
					$exporter->insertPostMeta($item);
					$exporter->insertImageAttach($item);
					$image_meta = $exporter->saveImageAttach($item);
					$exporter->insertPostmetaImageAttach($item, $image_meta);
					$exporter->updateSearchFilterTermResults($item);
					$exporter->insertSearchFilterCache($item);
					$maxID = (int) $exporter->getPostsMaxID() + 1;
					$category = $item->video ? 'video' : 'text';
					echo 'Inserted postID: ' . $item->ID . ' ' . $item->post_date . ' ' . $category . PHP_EOL;
				} else {
					// var_dump($item);
					echo '[DRY RUN] postID: ' . $item->ID . ' ' . $item->post_date .PHP_EOL;
				}
			}
		}
	}

	private function bq($query, $path = '') : string
	{
		return $this->config->host . $path . '?' . http_build_query($query);
	}

	private function qp($query) : object
	{
		$query = preg_replace('/^\?/', '', $query);
		parse_str($query, $result);
		return (object) $result;
	}

	private function parseHTML($data_results, $pattern)
	{
		@$this->dom->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . $data_results);
		@$this->dom->saveHTML();
		$xpath = new \DOMXPath($this->dom);
		return $xpath->query($pattern);
	}

	private function getArchiveLinks()
	{
		$data_results = $this->get(
			$this->bq($this->config->links->list)
		);

		$parseHTML = $this->parseHTML($data_results, '//table[@id="border_news"]//a/@href');
		foreach ($parseHTML as $value) {
			$links[] = $value->nodeValue;
		}

		exit;

		return $links;
	} 

	private function getArchiveLinksRange()
	{
		$data_results = $this->get(
			$this->bq($this->config->links->list)
		);

		$parseHTML = $this->parseHTML($data_results, '//table[@id="border_news"]//a/@href');

		foreach ($parseHTML as $value) {
			
			if (ARCHIVE_DATE_RANGE) {
				$date = $this->qp($value->nodeValue)->adate;
				$range = explode('-', ARCHIVE_DATE_RANGE);
				$range_start = strtotime('00.' . $range[0]);
				$range_end = strtotime('00.' . $range[1]);
				$range_last = strtotime('00.' . $date);
				if ($range_last >= $range_start && $range_last <= $range_end) {
					$links[$range_last] = $value->nodeValue;
				}
			} else {
				$links[] = $value->nodeValue;
			}
		}

		ksort($links);
		return array_values($links);
	} 

	private function getArchiveLinksByDate($query) : array
	{
		$links = [];
		$data_results = $this->get(
			$this->bq($query)
		);

		$parseHTML = $this->parseHTML($data_results, '//td[@id="border_news_r"]//a/@href');
		foreach ($parseHTML as $value) {
			$links[] = $value->nodeValue;
		}

		return $links;
	} 

	private function getArchiveNewsItemImage($value) : string
	{
		if (isset($value->getElementsByTagName('img')[0])) {
			$src = $this->config->host . '/' . $value->getElementsByTagName('img')[0]->getAttribute('src');
		} else {
			$src = false;
			// $src = $this->config->host . '/' . 'no_image.jpg';
		}

		return $src;
	}

	private function getArchiveNewsItemVideo($value)
	{
		$video_query = [];
		$video_url = '';

		if (isset($value->getElementsByTagName('p')[1]) && isset($value->getElementsByTagName('p')[1]->getElementsByTagName('a')[0])) {
			$video = $value->getElementsByTagName('p')[1]->getElementsByTagName('a')[0]->getAttribute('href');
			$video = str_replace("javascript:viewnew('", "", $video);
			$video = str_replace("');", "", $video);
			$video = explode("','", $video);
			$video_query = [
				'date' => $video[0],
				'n' => $video[1]
			];
		}

		if (count($video_query) > 0) {
			$data_results = $this->get($this->bq($video_query, '/' . $this->config->links->video->url));
			$parseHTML = $this->parseHTML($data_results, '//video/source')[0]->getAttribute('src');
			
			if (!empty($parseHTML)) {
				$video_url = $this->config->host . $parseHTML;
			}
		}

		return $video_url;
	}

	private function getVideoDimensions($video_url)
	{
		$ffprobe = \FFMpeg\FFProbe::create();
		return $ffprobe
		->streams($video_url)
		->videos()
		->first()
		->getDimensions();
	}

	private function getVideoType($video_url)
	{
		return pathinfo($video_url)['extension'];
	}

	private function getArchiveNewsItemText($value) : string
	{
		if (isset($value->getElementsByTagName('p')[0])) {
			$text = $value->getElementsByTagName('p')[0]->textContent;
		} else {
			$text = '';
		}

		return trim($text);
	}

	private function getNewUrl($value) : string
	{
		return preg_replace('/\./', '-', $value->date . '-' . $value->n);
	}

	private function getArchiveNewsItem($query)
	{
		$data_results = $this->get($this->bq($query));
		$parseTitle = $this->parseHTML($data_results, '//td[@id="border_news"]/center/b');
		$parseHTML = $this->parseHTML($data_results, '//td[@id="border_news_l_r"]');
		$entities = (object) [];

		foreach ($parseHTML as $value) {
			$date = strtotime($query->date);
			$format_date = date("Y-m-d H:i:s", $date + ($query->n . '0000') );

			$carbon_date = Carbon::parse($format_date, 'GMT+4');
			$localize_date = $carbon_date->locale('ru')->isoFormat('Do MMMM, YYYY');
			// var_dump($localize_date);
			// exit;

			$entities->post_name = $this->getNewUrl($query);
			// $entities->newurl = $this->getNewUrl($query);
			$entities->post_date = $format_date;
			$entities->post_date_gmt = '0000-00-00 00:00:00';
			$img = $this->getArchiveNewsItemImage($value);
			
			if ($img === false) {
				echo 'Break material with no image: ' . $query->date . ' ' . $query->n . PHP_EOL;
				continue;
			}

			$img_parse_str = parse_url($img);
			// $img_replaced_path = str_replace('archive/', '', $img_parse_str['path']);
			$entities->image_new_path_name = '/wp-content/uploads/' .
				date("Y", $date) . '/' .
				date("m", $date) . '/' .
				basename($img_parse_str['path']);
			
			$entities->image_new_path = '/wp-content/uploads/' .
				date("Y", $date) . '/' .
				date("m", $date);

			$img_pathinfo = pathinfo($img_parse_str['path']);
			$entities->image_name = $img_pathinfo['filename'];
			$entities->attached_file = 'wp-content/uploads/' .
				date("Y", $date) . '/' .
				date("m", $date) . '/' .
				basename($img_parse_str['path']);

			$entities->image_date_path = date("Y", $date) . '/' . date("m", $date);
/*			$entities->image_orig_path = date("Y", $date) .
				'/' . date("m", $date) .
				'/' . basename($img_parse_str['path']);*/
			
			// var_dump($entities->image_name);
			// exit;
			$video = $this->getArchiveNewsItemVideo($value);
			$text = $this->getArchiveNewsItemText($value);

			// $video_dimensions = $this->getVideoDimensions($video);
			// $video_width = $video_dimensions->getWidth();
			// $video_height = $video_dimensions->getHeight();
			// var_dump($video_mimetype);
			// $entities->post_content .= '<div class="post_video">[video width="' . $video_width . '" height="' . $video_height . '" ' . $video_mimetype . '="' . $video . '"][/video]</div>';
			$entities->post_content = '<div class="post_content-wrapper">';
			
			$entities->image_url = $img;

			if (!empty($video)) {
				$video_type = $this->getVideoType($video);
				$entities->post_content .= '<div class="post_video">[video width="720" height="404" ' . $video_type . '="' . $video . '"][/video]</div>';
				$entities->video = true;
				$entities->post_title = $localize_date . ' Видеосюжет с номером: ' . $query->n;
			} else {
				$entities->video = false;
				$entities->post_title = $localize_date . ' Текстовая новость с номером: ' . $query->n;
			}

			// $entities->post_content .= '<div class="post_name"><h4>' . $entities->post_title . '</h4></div>';
			$entities->post_content .= '<div class="post_text"><p>' . $text . '</p></div>';
			// $entities->post_content .= '<div class="post_image"><img src="' . $img . '" alt="" /></div>';
			$entities->post_content .= '</div>';
			// var_dump($this->textShorter($text, 80, '...'));
			// exit;
			$entities->post_title = $this->textShorter($text, 80, '...');
			echo 'Image: ' . $entities->image_url . PHP_EOL;

		}
		return $entities;
	} 

	public function textShorter($string, $max_length, $end_substitute = null, $html_linebreaks = true)
	{
	    if($html_linebreaks) $string = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
	    $string = strip_tags($string); //gets rid of the HTML

	    if(empty($string) || mb_strlen($string) <= $max_length) {
	        if($html_linebreaks) $string = nl2br($string);
	        return $string;
	    }

	    if($end_substitute) $max_length -= mb_strlen($end_substitute, 'UTF-8');

	    $stack_count = 0;
	    while($max_length > 0){
	        $char = mb_substr($string, --$max_length, 1, 'UTF-8');
	        if(preg_match('#[^\p{L}\p{N}]#iu', $char)) $stack_count++; //only alnum characters
	        elseif($stack_count > 0) {
	            $max_length++;
	            break;
	        }
	    }
	    $string = mb_substr($string, 0, $max_length, 'UTF-8').$end_substitute;
	    if($html_linebreaks) $string = nl2br($string);

	    return trim($string);

	}
}
