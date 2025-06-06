<?php

require_once ROOT_DIR . '/sys/DB/DataObject.php';

class PermissionGroupPermission extends DataObject {
	public $__table = 'permission_group_permissions';
	public $__primaryKey = 'id';

	public $id;
	public $groupId;
	public $permissionId;

	static function getObjectStructure($context = ''): array {
		return [
			'id' => [
				'property' => 'id',
				'type' => 'label',
				'label' => 'ID',
			],
			'groupId' => [
				'property' => 'groupId',
				'type' => 'integer',
				'label' => 'Group ID',
				'description' => 'The permission group ID.',
			],
			'permissionId' => [
				'property' => 'permissionId',
				'type' => 'integer',
				'label' => 'Permission ID',
				'description' => 'The ID of the permission in this group.',
			],
		];
	}
}