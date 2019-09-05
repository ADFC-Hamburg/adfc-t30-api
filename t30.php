<?php

include_once __DIR__ . '/vendor/ADFC-Hamburg/flexapi/datamodel/DataModelFactory.php';
include_once __DIR__ . '/vendor/ADFC-Hamburg/flexapi/datamodel/DataModel.php';
include_once __DIR__ . '/vendor/ADFC-Hamburg/flexapi/datamodel/DataEntity.php';
include_once __DIR__ . '/vendor/ADFC-Hamburg/flexapi/datamodel/IdEntity.php';

class T30Factory extends DataModelFactory {
    public function buildDataModel() {
        $dataModel = new DataModel();

        $dataModel->addEntities([
            new Street(),
            new UserData(),
            new Institution(),
            new PoliceDepartment(),
            new Email(),
            new DemandedStreetSection(),
            new DistrictHamburg(),
            new RelationToInstitution(),
        ]);

        $dataModel->addReference('relationtoinstitution.person -> userdata');
        $dataModel->addReference('relationtoinstitution.institution -> institution');

        $dataModel->addReference('demandedstreetsection.person -> userdata');
        $dataModel->addReference('demandedstreetsection.institution -> institution');

        $dataModel->addReference('email.person -> userdata');
        $dataModel->addReference('email.police_department -> policedepartment');
        $dataModel->addReference('email.demanded_street_section -> demandedstreetsection');

        $dataModel->addObservation([
            'observerName' => 'userdata',
            'subjectName' => 'userdata',
            'context' => ['beforeInsert', 'onInsert']
        ]);

        return $dataModel;
    }
}

class Street extends DataEntity {
    public function __construct() {
        parent::__construct('street');
        $this->addFields([
            ['name' => 'street_name', 'type' => 'varchar', 'length' => 255]
        ]);
    }
}

class UserData extends IdEntity {
    public function __construct() {
        parent::__construct('userdata');
        $this->addFields([
            ['name' => 'user', 'type' => 'varchar', 'length' => FlexAPI::get('maxUserNameLength')],
            ['name' => 'street_house_no', 'type' => 'varchar', 'length' => 255],
            ['name' => 'zip', 'type' => 'varchar', 'length' => 5],
            ['name' => 'city', 'type' => 'varchar', 'length' => 255],
            ['name' => 'phone', 'type' => 'varchar', 'length' => 20, 'notNull' => false],
            ['name' => 'mobile', 'type' => 'varchar', 'length' => 20, 'notNull' => false]
        ]);
    }

    public function observationUpdate($event) {}
}

class Institution extends IdEntity {
    public function __construct() {
        parent::__construct('institution');
        $this->addFields([
            ['name' => 'name', 'type' => 'varchar', 'length' => 255],
            ['name' => 'type', 'type' => 'smallint'],
            ['name' => 'street_house_no', 'type' => 'varchar', 'length' => 255],
            ['name' => 'address_supplement', 'type' => 'varchar', 'length' => 255],
            ['name' => 'zip', 'type' => 'varchar', 'length' => 5],
            ['name' => 'city', 'type' => 'varchar', 'length' => 255],
            ['name' => 'lon', 'type' => 'decimal', 'length' => '10,8'],
            ['name' => 'lat', 'type' => 'decimal', 'length' => '10,8'],
            ['name' => 'streetsection_complete', 'type' => 'boolean'],
            ['name' => 'status', 'type' => 'smallint']
            // FIXME ['name' => 'position', 'type' => 'point'],
        ]);
    }
}

class PoliceDepartment extends IdEntity {
    public function __construct() {
        parent::__construct('policedepartment');
        $this->addFields([
            // ['name' => 'department_number', 'type' => 'int', 'primary' => true],
            ['name' => 'region', 'type' => 'varchar', 'length' => 255],
            ['name' => 'street_house_no', 'type' => 'varchar', 'length' => 255],
            ['name' => 'zip', 'type' => 'varchar', 'length' => 5],
            ['name' => 'city', 'type' => 'varchar', 'length' => 255],
            ['name' => 'phone', 'type' => 'varchar', 'length' => 20],
            ['name' => 'email', 'type' => 'varchar', 'length' => 255],
            // ['name' => 'area', 'type' => 'polygon']
        ]);
    }
}

// class Tempo30 extends IdEntity {
//     public function __construct() {
//         parent::__construct('tempo30');
//         $this->addFields([
//             ['name' => 'established on', 'type' => 'boolean'],
//             ['name' => 'angeordnet_in', 'type' => 'boolean'],
//             ['name' => 'eingerichtet_am', 'type' => 'date'],
//             ['name' => 'angeordnet_am', 'type' => 'date'],
//             ['name' => 'grund_tempo30', 'type' => 'varchar', 'length' => 1000],
//             ['name' => 'ablehnungsgrund_tempo30', 'type' => 'varchar', 'length' => 1000],
//             ['name' => 'zeitliche_beschraenkung', 'type' => 'smallint'],
//             ['name' => 'abgelehnt_in', 'type' => 'boolean'],
//         ]);
//     }
// }

class Email extends IdEntity {
    public function __construct() {
        parent::__construct('email');
        $this->addFields([
            ['name' => 'draft', 'type' => 'text'],
            ['name' => 'text', 'type' => 'text'],
            ['name' => 'anonymised_text', 'type' => 'text'],
            ['name' => 'sent_on', 'type' => 'timestamp'],
            ['name' => 'person', 'type' => 'int'],
            ['name' => 'police_department', 'type' => 'int'],
            ['name' => 'demanded_street_section', 'type' => 'int'],
        ]);
    }

    public function observationUpdate($event) { }
}

class DemandedStreetSection extends IdEntity {
    public function __construct() {
        parent::__construct('demandedstreetsection');
        $this->addFields([
            ['name' => 'street', 'type' => 'varchar', 'length' => 255],
            ['name' => 'house_no_from', 'type' => 'varchar', 'length' => 8],
            ['name' => 'house_no_to', 'type' => 'varchar', 'length' => 8],
            ['name' => 'entrance', 'type' => 'smallint'],
            ['name' => 'user_note', 'type' => 'text'],
            ['name' => 'multilane', 'type' => 'smallint'],
            ['name' => 'bus_lines', 'type' => 'varchar', 'length' => 255],
            ['name' => 'much_bus_traffic', 'type' => 'smallint'],
            ['name' => 'reason_slower_buses', 'type' => 'text'],
            ['name' => 'time_restriction', 'type' => 'varchar', 'length' => 1000],
            ['name' => 'other_streets_checked', 'type' => 'varchar', 'length' => 1000],
            ['name' => 'person', 'type' => 'int'],
            ['name' => 'institution', 'type' => 'int'],
            ['name' => 'status', 'type' => 'int']
        ]);
    }
}

// class Status extends DataEntity {
//     public function __construct() {
//         parent::__construct('status');
//         $this->addFields([
//             ['name' => 'status_id', 'type' => 'smallint', 'primary' => true],
//             ['name' => 'denotation', 'type' => 'varchar', 'length' => 1000],
//         ]);
//     }
// }

// class InstitutionType extends DataEntity {
//     public function __construct() {
//         parent::__construct('institutiontype');
//         $this->addFields([
//             ['name' => 'type_id', 'type' => 'smallint', 'primary' => true],
//             ['name' => 'type', 'type' => 'varchar', 'length' => 255],
//         ]);
//     }
// }

class DistrictHamburg extends DataEntity {
    public function __construct() {
        parent::__construct('districthamburg');
        $this->addFields([
            ['name' => 'district_id', 'type' => 'smallint', 'primary' => true],
            ['name' => 'district', 'type' => 'varchar', 'length' => 255],
            // ['name' => 'polygon', 'type' => 'polygon']
        ]);
    }
}

class RelationToInstitution extends IdEntity {
    public function __construct() {
        parent::__construct('relationtoinstitution');
        $this->addFields([
            ['name' => 'relation_type', 'type' => 'varchar', 'length' => 1000],
            ['name' => 'person', 'type' => 'int'],
            ['name' => 'institution', 'type' => 'int'],
        ]);
    }

    public function observationUpdate($event) {
        if ($event['context'] === 'beforeInsert' || $event['context'] === 'beforeUpdate') {
            if (array_key_exists('user', $event['data'])) {
                throw(new Exception('Field "user" cannot be set manually.', 400));
            }
        } elseif ($event['context'] === 'onInsert') {
            $userDataId = $this->dataModel->idOf('userdata', [ 'user' => $event['user'] ]);
            if (!$userDataId) {
                throw(new Exception('No user data found.', 500));
            }
            FlexAPI::superAccess()->update('patenschaft', [
                'id' => $event['insertId'],
                'user' => $userDataId
            ]);
        }
    }
}
