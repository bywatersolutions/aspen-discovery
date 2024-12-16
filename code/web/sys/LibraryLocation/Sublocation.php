<?php /** @noinspection PhpMissingFieldTypeInspection */

class Sublocation extends DataObject {
    public $__table = 'sublocation';
    public $id;
    public $ilsId;
    public $name;
    public $weight;
    public $locationId;
    public $isValidHoldPickupAreaILS;
    public $isValidHoldPickupAreaAspen;

    public function getNumericColumnNames(): array {
        return [
            'ilsId',
            'locationId',
            'isValidHoldPickupAreaAspen',
            'isValidHoldPickupAreaILS'
        ];
    }

    static function getObjectStructure($context = ''): array {
        //Load locations for lookup values
        $allLocationsList = Location::getLocationList(false);
        $locationList = Location::getLocationList(!UserAccount::userHasPermission('Administer All Libraries'));

        $uneditableForILS = false;
        global $library;
        $accountProfile = $library->getAccountProfile();
        if ($accountProfile->ils == 'polaris') {
            $uneditableForILS = true;
        }

        return [
            'id' => [
                'property' => 'id',
                'type' => 'label',
                'label' => 'Id',
                'description' => 'The unique id of the sublocation within the database',
            ],
            'weight' => [
                'property' => 'weight',
                'type' => 'integer',
                'label' => 'Weight',
                'description' => 'The sort order',
                'default' => 0,
            ],
            'name' => [
                'property' => 'name',
                'type' => 'text',
                'label' => 'Display Name',
                'description' => 'The display name for the sublocation',
            ],
            'ilsId' => [
                'property' => 'ilsId',
                'type' => 'text',
                'label' => 'ILS Code',
                'description' => 'The ILS Code for the sublocation',
                'readOnly' => $uneditableForILS,
                'onchange' => "return AspenDiscovery.Admin.validateSublocationHoldPickupAreaAspen(this);",
            ],
            'locationId' => [
                'property' => 'locationId',
                'type' => 'enum',
                'values' => $locationList,
                'allValues' => $allLocationsList,
                'label' => 'Location',
                'description' => 'The location which the sublocation belongs to',
            ],
            'isValidHoldPickupAreaILS' => [
                'property' => 'isValidHoldPickupAreaILS',
                'type' => 'checkbox',
                'label' => 'Valid Hold Pickup Area (ILS)',
                'description' => 'Whether or not this sublocation is a valid hold pickup area for the ILS',
                'readOnly' => $uneditableForILS,
                'onchange' => "return AspenDiscovery.Admin.validateSublocationHoldPickupAreaAspen(this);",
                'note' => 'Requires an ILS Id value to be provided'
            ],
            'isValidHoldPickupAreaAspen' => [
                'property' => 'isValidHoldPickupAreaAspen',
                'type' => 'checkbox',
                'label' => 'Valid Hold Pickup Area (Aspen)',
                'description' => 'Whether or not this sublocation is a valid hold pickup area for Aspen',
                'note' => 'Requires an ILS Id and Valid Hold Pickup Area (ILS) to be checked',
            ],
        ];
    }
}