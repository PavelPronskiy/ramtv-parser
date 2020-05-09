<?php

namespace DunsChecker;

class ListorgController
{

	function __construct($config) {
		$this->curl = curl_init();
		$this->config = $config;
	}
	
	public function getINN($inn)
	{
		$link_inn = '';
		$dom = new \DOMDocument;
		$search_inn = $this->config->listorg_settings->host . '/search?' . http_build_query([
			"type" => "all",
			"val" => $inn
		]);
		curl_setopt($this->curl, CURLOPT_URL, $search_inn);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->config->headers->listorg);
		curl_setopt($this->curl, CURLOPT_HEADER, 1);
		curl_setopt($this->curl, CURLOPT_ENCODING , "gzip");
		$result = curl_exec($this->curl);
			
		@$dom->loadHTML($result);
		$xpath = new \DOMXPath($dom);
		$link_inn_xpath = $xpath->query('//div[@class="org_list"]//a/@href');

		foreach ($link_inn_xpath as $value) {
			$link_inn = $value->nodeValue;
		}

		sleep(3);

		curl_setopt($this->curl, CURLOPT_URL, $this->config->listorg_settings->host . $link_inn);
		$result = curl_exec($this->curl);
		@$dom->loadHTML($result);
		$xpath = new \DOMXPath($dom);
		$inn_data = $xpath->query("//a[@class='nwra lbs64']");
		$inn_phones = [];


		foreach ($inn_data as $value) {
			$inn_phones[] = $value->nodeValue;
		}

		return $inn_phones;
	}
}
