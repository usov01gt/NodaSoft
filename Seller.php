<?php

namespace NW\WebService\References\Operations\Notification;

/**
 *
 * Класс наследник Contractor'а расширен для получения списка e-mail'ов для уведомлений (getEmailsByPermit)
 * функции getResellerEmailFrom и getEmailsByPermit перенес в данный метод, предполагаю, что они нужны как раз для того, чтоб получать e-mail'ы у данного типа пользователей
 */
class Seller extends Contractor {

	protected string $email = 'contractor@example.com';

	public function getResellerEmailFrom (): string
	{
		return $this->email;
	}

	public function getEmailsByPermit ($event): array
	{
		return ['someemeil@example.com', 'someemeil2@example.com'];
	}

}