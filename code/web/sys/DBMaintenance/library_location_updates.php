<?php /** @noinspection SqlResolve */

function getLibraryLocationUpdates() {
	return [

		'alternate_library_card_form_message' => [
			'title' => 'Add field for Alternate Library Card Form Message',
			'description' => 'Add field for Alternate Library Card Form Message',
			'sql' => [
				"ALTER TABLE library ADD COLUMN alternateLibraryCardFormMessage MEDIUMTEXT",
			],
		],

		'symphony_user_category_notice_settings' => [
			'title' => 'Symphony user category notice settings',
			'description' => 'Add settings for which Symphony user categories have what options for notices and billing notices',
			'sql' => [
				"ALTER TABLE library ADD COLUMN symphonyNoticeCategoryNumber VARCHAR(2)",
				"ALTER TABLE library ADD COLUMN symphonyNoticeCategoryOptions VARCHAR(128)",
				"ALTER TABLE library ADD COLUMN symphonyBillingNoticeCategoryNumber VARCHAR(2)",
				"ALTER TABLE library ADD COLUMN symphonyBillingNoticeCategoryOptions VARCHAR(128)",
			],
		],

		'symphony_default_phone_field' => [
			'title' => 'Symphony default phone field',
			'description' => 'Add field to set name of default phone field (usually PHONE)',
			'sql' => [
				"ALTER TABLE library ADD COLUMN symphonyDefaultPhoneField VARCHAR(16) DEFAULT 'PHONE'",
			],
		],

		'show_cellphone_in_profile' => [
			'title' => 'Show cellphone in profile',
			'description' => 'Toggle to show cellphone in profile (for Symphony)',
			'sql' => [
				"ALTER TABLE library ADD COLUMN showCellphoneInProfile TINYINT(1) DEFAULT '0'",
			],
		],
	];
}