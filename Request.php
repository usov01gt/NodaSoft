<?php

namespace NW\WebService\References\Operations\Notification;

/**
 *
 * Вынес логику для обработки $_REQUEST в отдельный класс. В принципе во всех фрэймворках существуют свои классы и методы для безопасной обработки входных данных
 */
class Request{
	protected function secureRequest (array $request = []): array
	{
		$out = [];
		foreach ($request as $key => $value) {
			if (is_array($value)) {
				$out[htmlspecialchars(trim($key))] = $this->secureRequest($value);
			} else {
				$out[htmlspecialchars(trim($key))] = htmlspecialchars(trim($value));
			}
		}

		return $out;
	}

	public function getRequest ($pName): array
	{
		if (isset($_REQUEST[$pName]) && is_array($_REQUEST[$pName])) {
			return $this->secureRequest($_REQUEST[$pName]);
		} else {
			return [];
		}
	}
}