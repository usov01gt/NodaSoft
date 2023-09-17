<?php

namespace NW\WebService\References\Operations\Notification;

/**
 *
 * ReferencesOperation является абстрактным классом, для обобщения работы (в нашем случае наследуемый класс TsReturnOperation)
 */
abstract class ReferencesOperation {
	abstract public function doOperation (): array;

}