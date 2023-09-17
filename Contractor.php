<?php

namespace NW\WebService\References\Operations\Notification;

/**
 *
 * Contractor является моделью для работы с пользователями. Наследниками являются классы Employee и Seller (видимо для того чтоб по разному обрабатывать данные, потому что роли уу них разные).
 * Здесь бы я хранил только структуру данных для пользователей, а саму логику вынес бы в отдельный контроллер
 */
class Contractor {
	const TYPE_CUSTOMER = 0;
	public int $id;
	public int $type;
	public string $name;

	public function __construct ()
	{
		$this->id = 1;
		$this->type = 0;
		$this->name = 'Ivan';
	}

	public function getById (int $resellerId = 0): self|null
	{
		if ($resellerId == 0) {
			return null;
		}

		return new self($resellerId);
	}

	public function getFullName (): string
	{
		return $this->name.' '.$this->id;
	}
}