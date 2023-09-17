<?php

namespace NW\WebService\References\Operations\Notification;

use NW\WebService\References\Operations\Notification\Contractor;
use NW\WebService\References\Operations\Notification\Employee;
use NW\WebService\References\Operations\Notification\Request;
use NW\WebService\References\Operations\Notification\ReferencesOperation;
use NW\WebService\References\Operations\Notification\Seller;
use NW\WebService\References\Operations\Notification\NotificationEvents;
use NW\WebService\References\Operations\Notification\Status;
use Exception;

/**
 *
 * Класс TsReturnOperation обрабатывает данные из request (лучше заменить на пост)
 * Проверяет наличие всех нужных входных параметров, отдает ошибку, либо отправляет уведомления на e-mail
 *
 * из непонятного:
 * 		функция __() (предположу что необходим для сбора Subject и Body письма)
 * 		класс MessagesClient (отправляет письма. Использованы статичные методы sendMessage и send,
 * 			причем у send 6-ым параметром массив $errors, используется как ссылка чтоб возвращать ошибки в случае неудачной отправки)
 */
class TsReturnOperation extends ReferencesOperation {

	public const TYPE_NEW = 1;
	public const TYPE_CHANGE = 2;
	protected array $result = [];
	protected array $request = [];
	protected array $templateData = [];

	protected Seller $reseller;
	protected Contractor $client;
	protected Employee $creator;
	protected Employee $expert;

	public function doOperation (): array
	{
		$this->request = (new Request())->getRequest('data');
		$this->prepareResult();
		try {
			$this->checkEmployees();
			$this->checkNotificationType();
			$this->checkTemplateData();
			$this->process();
		} catch (\Exception $exception) {
			$this->result['notificationClientBySms']['message'] = $exception->getMessage();
		}

		return $this->result;
	}

	protected function prepareResult (): void
	{
		$this->result = [
			'notificationEmployeeByEmail' => false,
			'notificationClientByEmail' => false,
			'notificationClientBySms' => [
				'isSent' => false,
				'message' => '',
			],
		];
	}

	protected function checkEmployees (): void
	{
		$this->reseller = (new Seller())->getById((int)$this->request['resellerId']);
		if ($this->reseller === null) {
			throw new Exception('Seller not found!', 400);
		}
		$this->creator = (new Employee())->getById((int)$this->request['creatorId']);
		if ($this->creator === null) {
			throw new Exception('Creator not found!', 400);
		}
		$this->expert = (new Employee())->getById((int)$this->request['expertId']);
		if ($this->expert === null) {
			throw new Exception('Expert not found!', 400);
		}
		$this->client = (new Contractor())->getById((int)$this->request['clientId']);
		if ($this->client === null || $this->client->type !== Contractor::TYPE_CUSTOMER || $this->client->id !== (int)$this->request['resellerId']) {
			throw new Exception('Client not found!', 400);
		}
	}

	protected function checkNotificationType (): void
	{
		$this->notificationType = (int)$this->request['notificationType'];
		if ($this->notificationType <= 0) {
			throw new Exception('Empty notificationType', 400);
		}
	}

	protected function checkTemplateData ()
	{
		$differences = '';
		if ($this->notificationType === self::TYPE_NEW) {
			$differences = __('NewPositionAdded', null, $this->reseller->id);
		} elseif ($this->notificationType === self::TYPE_CHANGE && !empty($this->request['differences'])) {
			$differences = __('PositionStatusHasChanged', [
				'FROM' => Status::getName((int)$this->request['differences']['from']),
				'TO' => Status::getName((int)$this->request['differences']['to']),
			], $this->reseller->id);
		}

		$this->templateData = [
			'COMPLAINT_ID' => (int)$this->request['complaintId'],
			'COMPLAINT_NUMBER' => (string)$this->request['complaintNumber'],
			'CREATOR_ID' => $this->creator->id,
			'CREATOR_NAME' => $this->creator->getFullName(),
			'EXPERT_ID' => $this->expert->id,
			'EXPERT_NAME' => $this->expert->getFullName(),
			'CLIENT_ID' => $this->client->id,
			'CLIENT_NAME' => $this->client->getFullName(),
			'CONSUMPTION_ID' => (int)$this->request['consumptionId'],
			'CONSUMPTION_NUMBER' => (string)$this->request['consumptionNumber'],
			'AGREEMENT_NUMBER' => (string)$this->request['agreementNumber'],
			'DATE' => (string)$this->request['date'],
			'DIFFERENCES' => $differences,
		];

		foreach ($this->templateData as $key => $tempData) {
			if (empty($tempData)) {
				throw new Exception("Template Data ({$key}) is empty!", 500);
			}
		}
	}

	public function process (): void
	{
		$emailFrom = $this->reseller->getResellerEmailFrom();
		$emails = $this->reseller->getEmailsByPermit('tsGoodsReturn');
		if ($emailFrom != '' && count($emails) > 0) {
			foreach ($emails as $email) {
				MessagesClient::sendMessage([
					0 => [
						'emailFrom' => $emailFrom,
						'emailTo' => $email,
						'subject' => __('complaintEmployeeEmailSubject', $this->templateData, $this->reseller->id),
						'message' => __('complaintEmployeeEmailBody', $this->templateData, $this->reseller->id),
					],
				], $this->reseller->id, NotificationEvents::CHANGE_RETURN_STATUS);
				$this->result['notificationEmployeeByEmail'] = true;
			}
		}

		// Шлём клиентское уведомление, только если произошла смена статуса
		if ($this->notificationType === self::TYPE_CHANGE && !empty($this->request['differences']['to'])) {
			if ($emailFrom != '' && !empty($client->email)) {
				MessagesClient::sendMessage([
					0 => [
						'emailFrom' => $emailFrom,
						'emailTo' => $client->email,
						'subject' => __('complaintClientEmailSubject', $this->templateData, $this->reseller->id),
						'message' => __('complaintClientEmailBody', $this->templateData, $this->reseller->id),
					],
				], $this->reseller->id, $client->id, NotificationEvents::CHANGE_RETURN_STATUS, (int)$this->request['differences']['to']);
				$this->result['notificationClientByEmail'] = true;
			}

			if (!empty($client->mobile)) {
				$res = NotificationManager::send($this->reseller->id, $client->id, NotificationEvents::CHANGE_RETURN_STATUS, $this->request['differences']['to'], $this->templateData, $error);
				if ($res) {
					$this->result['notificationClientBySms']['isSent'] = true;
				}
				if (!empty($error)) {
					$this->result['notificationClientBySms']['message'] = $error;
				}
			}
		}
	}

}
