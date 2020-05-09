<?php

namespace DunsChecker;

class UpikController
{
	function __construct($config) {
		$this->curl = curl_init();
		$this->config = $config;
		$this->cookie_jar = tempnam('/tmp', $this->config->upik_settings->cookie_file);
		$this->sid = $this->getUpikSID();
	}

	public function getUpikSID()
	{
		$cookies = [];
		
		curl_setopt($this->curl, CURLOPT_URL, $this->config->upik_settings->host);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->curl, CURLOPT_COOKIEJAR, $this->cookie_jar);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->config->headers->sid);
		curl_setopt($this->curl, CURLOPT_HEADER, 1);
		curl_setopt($this->curl, CURLOPT_ENCODING , "gzip");
		$result = curl_exec($this->curl);
		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result, $matches);
		// var_dump($result);
		// exit;
		foreach($matches[1] as $item) {
			parse_str($item, $cookie);
			$cookies = array_merge($cookies, $cookie);
		}
		// curl_close ($ch);
		$cookies = (object)$cookies;
		return [ "Cookie: upik_.SID=" . $cookies->upik__SID ];
	}

	public function auth()
	{
		$headers = array_merge($this->config->headers->prelogin, $this->sid);
		$post = http_build_query([
			"username" => $this->config->upik_settings->user,
			"passwort" => $this->config->upik_settings->pass,
			"senden" => "%D0%92%D0%BE%D0%B9%D1%82%D0%B8"
		]);

		curl_setopt($this->curl, CURLOPT_URL, $this->config->upik_settings->host . $this->config->upik_settings->prelogin);
		curl_setopt($this->curl, CURLOPT_POST, 1);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->curl, CURLOPT_COOKIEJAR, $this->cookie_jar);
		curl_setopt($this->curl, CURLOPT_COOKIEFILE, $this->cookie_jar);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $post);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->curl, CURLOPT_ENCODING , "gzip");
		curl_exec($this->curl);
	}

	public function getUpikDuns($duns_id)
	{
		$dom = new \DOMDocument;
		$args = http_build_query([
			"quick" => 1,
			"DUNS" => $duns_id,
			"CTRY_CD" => "RU"
		]);

		$headers = array_merge($this->config->headers->search, $this->sid);
		curl_setopt($this->curl, CURLOPT_URL, $this->config->upik_settings->host . $this->config->upik_settings->search . '?' . $args);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->curl, CURLOPT_HEADER, 0);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($this->curl, CURLOPT_COOKIEFILE, $this->cookie_jar);
		curl_setopt($this->curl, CURLOPT_ENCODING , "gzip");
		$result = curl_exec($this->curl);
		@$dom->loadHTML($result);
		$xpath = new \DOMXPath($dom);
		$elements_name = $xpath->query("//table/tr/td[@id='upds_left']");
		$elements_text = $xpath->query("//table/tr/td[@id='upds_right']");
		$e_name_array = [];
		$e_text_array = [];

		foreach ($elements_name as $value) {
			$e_name_array[] = $value->nodeValue;
		}
		foreach ($elements_text as $value) {
			$e_text_array[] = $value->nodeValue;
		}

		$elements_array = array_combine($e_name_array, $e_text_array);
		return $elements_array;
	}

	public function clearSession()
	{
		unlink($this->cookie_jar);
	}

}
