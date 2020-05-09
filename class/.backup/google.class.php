<?php

namespace DunsChecker;

class GoogleClientController {

	function __construct($config) {
		$this->config = $config;
		$this->google_token = '/tmp/' . $config->google_client->token;
		$this->google_client = $this->getGoogleApiClient();
	}

	public function getGoogleApiClient()
	{
		$client = new \Google_Client();
		$client->setApplicationName("duns bot");
		$client->setScopes(\Google_Service_Sheets::SPREADSHEETS);
		$client->setAuthConfig(dirname(__DIR__) . '/' . $this->config->google_client->credentials);
		$client->useApplicationDefaultCredentials();

		if ($client->isAccessTokenExpired()) {
			$client->refreshTokenWithAssertion();
		}

		return $client;
	}

	public function getSpreadsheets($range, $column = '', $index = 2): array {
		$array = [];
		$service = new \Google_Service_Sheets($this->google_client);
		$rows = $service->spreadsheets_values->get(
			$this->config->google_client->spreadsheet_id,
			$range,
			[
				'majorDimension' => 'ROWS'
			]
		);

		foreach ($rows->getValues() as $items) {
			if (count($items) > 0 ) {
				$array[$index] = $items[0];
			}
			
			$index++;

		}

		return $array;
	}

	public function getSpreadsValues($column, $sheet_list, $start_index) {
		return $this->getSpreadsheets(
			$sheet_list . "!" . $column . $start_index . ":" . $column . $this->config->google_client->max_rows,
			$column,
			$start_index
		);
	}

	public function updateSpreadsValue($sheet_list, $column, $index, $values) {
		$service = new \Google_Service_Sheets($this->google_client);
		$range = $sheet_list . '!' . $column . $index;
		$request_values = new \Google_Service_Sheets_ValueRange([
			'majorDimension' => 'ROWS',
			'range' => $range,
			'values' => [[ $values ]]
		]);

		$params = [ 'valueInputOption' => 'RAW' ];

		// var_dump($request_values);

		return $service->spreadsheets_values->update(
			$this->config->google_client->spreadsheet_id,
			$range,
			$request_values,
			$params
		);

	}

	public function getSpreadsheetsValuesByColumns($columns): object {
		$columns_object = (object)[];
		foreach ($columns as $column) {
			$columns_object->{$column} = $this->getSpreadsValues($this->config->google_client->columns->{$column}, $this->config->google_client->sheet_list, 2);
		}

		return $columns_object;
	}

}