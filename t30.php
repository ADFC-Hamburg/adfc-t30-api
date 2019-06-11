<?php

include_once __DIR__ . '/vendor/bensteffen/flexapi/datamodel/DataModelFactory.php';
include_once __DIR__ . '/vendor/bensteffen/flexapi/datamodel/DataModel.php';
include_once __DIR__ . '/vendor/bensteffen/flexapi/datamodel/DataEntity.php';
include_once __DIR__ . '/vendor/bensteffen/flexapi/datamodel/IdEntity.php';

class T30Factory extends DataModelFactory {
    public function buildDataModel() {
        $dataModel = new DataModel();

        $dataModel->addEntities([
            new UserData(),
            new Institution(),
            new Patenschaft()
        ]);

        $dataModel->addReference('patenschaft.user -> userdata');
        $dataModel->addReference('patenschaft.institution -> institution');

        $dataModel->addObservation([
            'observerName' => 'userdata',
            'subjectName' => 'userdata',
            'context' => ['beforeInsert', 'onInsert']
        ]);

        $dataModel->addObservation([
            'observerName' => 'institution',
            'subjectName' => 'institution',
            'context' => ['onInsert', 'onDelete']
        ]);

        $dataModel->addObservation([
            'observerName' => 'patenschaft',
            'subjectName' => 'patenschaft',
            'context' => ['beforeInsert', 'onInsert']
        ]);

        return $dataModel;
    }
}

class UserData extends DataEntity {
    public function __construct() {
        parent::__construct('userdata');
        $this->addFields([
            ['name' => 'user', 'type' => 'varchar', 'length' => FlexAPI::get('maxUserNameLength'), 'primary' => true] ,
            ['name' => 'firstName', 'type' => 'varchar', 'length' => 64],
            ['name' => 'lastName', 'type' => 'varchar', 'length' => 64],
            ['name' => 'street', 'type' => 'varchar', 'length' => 128],
            ['name' => 'number', 'type' => 'varchar', 'length' => 8],
            ['name' => 'city', 'type' => 'varchar', 'length' => 64],
            ['name' => 'zip', 'type' => 'int'],
            ['name' => 'phone', 'type' => 'varchar', 'length' => 32]
        ]);
    }

    public function observationUpdate($event) {

    }
}

class Institution extends IdEntity {
    public function __construct() {
        parent::__construct('institution');
        $this->addFields([
            ['name' => 'name', 'type' => 'varchar', 'length' => 256],
            ['name' => 'type', 'type' => 'int'],
            ['name' => 'tempo30', 'type' => 'int'],
            ['name' => 'street', 'type' => 'varchar', 'length' => 128],
            ['name' => 'number', 'type' => 'varchar', 'length' => 8],
            ['name' => 'zip', 'type' => 'int'],
            ['name' => 'district', 'type' => 'varchar', 'length' => 64, 'notNull' => false],
            ['name' => 'lat', 'type' => 'decimal', 'length' => '8,6'],
            ['name' => 'lon', 'type' => 'decimal', 'length' => '8,6'],
        ]);
    }

    public function observationUpdate($event) {

    }

}

class Patenschaft extends IdEntity {
    public function __construct() {
        parent::__construct('patenschaft');
        $this->addFields([
            ['name' => 'user', 'type' => 'int', 'default' => 0],
            ['name' => 'institution', 'type' => 'int'],
            ['name' => 'relationship', 'type' => 'varchar', 'length' => 128],
        ]);
    }

    public function observationUpdate($event) {
        if ($event['context'] === 'beforeInsert' || $event['context'] === 'beforeUpdate') {
            if (array_key_exists('user', $event['data'])) {
                throw(new Exception('Field "user" cannot be set manually.', 400));
            }
        } elseif ($event['context'] === 'onInsert') {
            $userDataId = $this->dataModel->idOf('userdata', [ 'username' => $event['user'] ]);
            if (!$userDataId) {
                throw(new Exception('No user data found.', 500));
            }
            FlexAPI::dataModel()->getConnection()->updateDatabase($event['subjectEntity'], [
                'id' => $event['insertId'],
                'user' => $userDataId
            ]);
        }
    }
}

