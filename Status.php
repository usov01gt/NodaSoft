<?php

namespace NW\WebService\References\Operations\Notification;

/**
 *
 * не совсем понял логику работы со статусами, потому что данные для обработки получает из request ['differences']['from'] и ['differences']['to']
 */
class Status {
	public int $id;
	public string $name;

	public static function getName (int $id): string
	{
		$result = [
			0 => 'Completed',
			1 => 'Pending',
			2 => 'Rejected',
		];

		return $result[$id];
	}
}