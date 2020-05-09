<?php

namespace DunsChecker;

class CheckerController {

	public $config;
	public static $phonesArray;
	public static $domainsArray;
	public static $clinicsArray;
	public static $dunsArray;
	public static $innArray;

	function __construct($config, $GoogleClientController, $UpikController, $ListorgController) {
		$this->config = $config;
		$this->GoogleClientController = $GoogleClientController;
		$this->UpikController = $UpikController;
		$this->ListorgController = $ListorgController;
		$this->check();
	}

	public function shrinkPhonesArray($array): array {
		$res_array = [];
		foreach ($array as $item) {
			$item = (int)preg_replace('/\D+/m', '', $item);
			$item = (int)preg_replace('/^([0-9]{1})/m', '', $item);
			$res_array[] = $item;
		}
		return $res_array;
	}

	public function cliOrgStatusViewer($object): string {
		return 'Домен: ' . $object->domain . PHP_EOL .
		'Клиника: ' . $object->clinic . PHP_EOL .
		'ИНН: ' . $object->inn . PHP_EOL .
		'D-U-N-S Идентификатор: ' . $object->duns . PHP_EOL .
		'Статус в UPIK: ' . $object->upik_status . PHP_EOL .
		'Статус в list-org.com: ' . $object->listorg_status . PHP_EOL .
		// 'ORG в UPIK: ' . $object->upik_name . PHP_EOL .
		// 'Номер телефона: ' . $object->phone . PHP_EOL .
		PHP_EOL;
	}

	public function listorgCheckPhones($listorg_phones_array)
	{
		$status = false;
		$phones_array = $this->shrinkPhonesArray($listorg_phones_array);
		foreach ($phones_array as $phone) {
			// var_dump('shrinked: ' . $phone);
			// var_dump(self::$phonesArray);
			if (in_array($phone, self::$phonesArray)) {
				$status = true;
				break;
			}
		}

		return $status;

	}

	public function checkDunsStatus($index)
	{
		$elements = $this->UpikController->getUpikDuns(self::$dunsArray[$index]);
		$phone = isset($elements['Telefon Nummer']) ? $elements['Telefon Nummer'] : '';
		return in_array($phone, self::$phonesArray);
	}

	public function checkListOrgStatus($index)
	{
		$phones = $this->ListorgController->getINN(self::$innArray[$index]);
		return $this->listorgCheckPhones($phones);
	}

	public function check()
	{

		$gc = $this->config->google_client;

		// авторизация на сайте upik.de
		$this->UpikController->auth();

		// получение списков из таблицы гугл докс
		$ss = $this->GoogleClientController->getSpreadsheetsValuesByColumns([
			'phones',
			'duns',
			'inn',
			'clinics',
			'domains'
		]);
		
		self::$domainsArray	= $ss->domains;
		self::$clinicsArray	= $ss->clinics;
		self::$phonesArray 	= $this->shrinkPhonesArray($ss->phones);
		self::$dunsArray  	= $ss->duns;
		self::$innArray 	= $ss->inn;

		foreach (self::$domainsArray as $index => $item) {

			// проверка номера телефона в UPIK
			if (isset(self::$dunsArray[$index])) {
				$upik_status = $this->checkDunsStatus($index) ? 'подтверждён' : 'не подтверждён';
				$this->GoogleClientController->updateSpreadsValue($gc->sheet_list, $gc->columns->upik_updates, $index, $upik_status);
			}

			// проверка номера телефона в list-org.com
			if (isset(self::$innArray[$index])) {
				$listorg_status = $this->checkListOrgStatus($index) ? 'подтверждён' : 'не подтверждён';
				$this->GoogleClientController->updateSpreadsValue($gc->sheet_list, $gc->columns->listorg_updates, $index, $listorg_status);
			}

			$this->GoogleClientController->updateSpreadsValue($gc->sheet_list, $gc->columns->date_updates, $index, date("Y-m-d H:i:s"));

			echo $this->cliOrgStatusViewer((object)[
				"duns" => isset(self::$dunsArray[$index]) ? self::$dunsArray[$index] : '',
				"domain" => isset(self::$domainsArray[$index]) ? self::$domainsArray[$index] : '',
				"clinic" => isset(self::$clinicsArray[$index]) ? self::$clinicsArray[$index] : '',
				"phone" => isset(self::$phonesArray[$index]) ? self::$phonesArray[$index] : '',
				"inn" => isset(self::$innArray[$index]) ? self::$innArray[$index] : '',
				"upik_status" => isset(self::$dunsArray[$index]) ? $upik_status : '',
				"listorg_status" => isset(self::$innArray[$index]) ? $listorg_status : ''
			]);

			sleep(rand(50,120));
		}


/*		foreach ($ss->duns as $index => $duns) {
			$elements = $this->UpikController->getUpikDuns($duns);
			$upik_phone = isset($elements['Telefon Nummer']) ? $elements['Telefon Nummer'] : '';
			$upik_status = in_array($upik_phone, $this->shrinkPhonesArray($ss->phones));

			// $listorg_status = in_array($ss->phones[$index], $this->ListorgController->getINN($ss->inn[$index])) ? '' : '';
			// var_dump($this->shrinkPhonesArray($listorg));
			
			$listorg_phones_array = $this->shrinkPhonesArray($this->ListorgController->getINN($ss->inn[$index]));
			$listorg_status = $this->listorgCheckPhones($listorg_phones_array, $ss->phones);

			$upik_status_msg = $upik_status ? 'подтверждён' : 'не подтверждён';
			$listorg_status_msg = $listorg_status ? 'подтверждён' : 'не подтверждён';

			$this->GoogleClientController->updateSpreadsValue($this->config->google_client->sheet_list, $this->config->google_client->columns->upik_updates, $index, $upik_status_msg);
			$this->GoogleClientController->updateSpreadsValue($this->config->google_client->sheet_list, $this->config->google_client->columns->listorg_updates, $index, $listorg_status_msg);

			$this->GoogleClientController->updateSpreadsValue($this->config->google_client->sheet_list, $this->config->google_client->columns->date_updates, $index, date("Y-m-d H:i:s"));


			// break;
		}
*/
		$this->UpikController->clearSession();

	}
	
}