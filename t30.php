<?php

include_once __DIR__ . '/vendor/adfc-hamburg/flexapi/datamodel/DataModelFactory.php';
include_once __DIR__ . '/vendor/adfc-hamburg/flexapi/datamodel/DataModel.php';
include_once __DIR__ . '/vendor/adfc-hamburg/flexapi/datamodel/DataEntity.php';
include_once __DIR__ . '/vendor/adfc-hamburg/flexapi/datamodel/IdEntity.php';

include_once __DIR__ . '/vendor/adfc-hamburg/flexapi/database/queryfactories/AbstractReadQueryFactory.php';
include_once __DIR__ . '/vendor/adfc-hamburg/flexapi/services/pipes/IfEntityDataPipe.php';

class T30Factory extends DataModelFactory {
    public function buildDataModel() {
        $institution = new Institution();
        $institution->registerQueryFactory('sql', 'read', new InstitutionSqlReadQueryFactory());

        $dataModel = new DataModel();

        $dataModel->addEntities([
            new Street(),
            new UserData(),
            $institution,
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
        $dataModel->addReference('email.demanded_street_section -> demandedstreetsection');

        $dataModel->addObservation([
            'observerName' => 'userdata',
            'subjectName' => 'userdata',
            'context' => ['beforeInsert', 'onInsert']
        ]);
        $dataModel->addObservation([
            'observerName' => 'institution',
            'subjectName' => 'demandedstreetsection',
            'context' => ['onUpdate', 'onInsert', 'onDelete']
        ]);
        $dataModel->addObservation([
            'observerName' => 'demandedstreetsection',
            'subjectName' => 'demandedstreetsection',
            'context' => ['onInsert', 'beforeUpdate', 'onUpdate']
        ]);
        $dataModel->addObservation([
            'observerName' => 'email',
            'subjectName' => 'email',
            'context' => ['beforeInsert', 'onInsert', 'onUpdate']
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
            ['name' => 'firstName', 'type' => 'varchar', 'length' => 128],
            ['name' => 'lastName', 'type' => 'varchar', 'length' => 128],
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
            ['name' => 'position', 'type' => 'point'],
            ['name' => 'streetsection_complete', 'type' => 'boolean'],
            ['name' => 'status', 'type' => 'smallint'],
            ['name' => 'by_the_records', 'type' => 'text']
        ]);
    }

    public function observationUpdate($event) {
        if ($event['subjectName'] == 'demandedstreetsection') {
          $instId = null;
          if ($event['context'] === 'onInsert') {
            if (!array_key_exists('institution', $event['data'])) {
              throw(new Exception("Cannot create demanded street section without institution.", 400));
            }
            $instId= $event['data']['institution'];
          } elseif ($event['context'] === 'onUpdate') {
            if (array_key_exists('status', $event['data'])) {
              if (array_key_exists('institution', $event['data'])) {
                $instId = $event['data']['institution'];
              } else {
                $section = $this->dataModel->read('demandedstreetsection', [
                  'filter' => ['institution' => $event['id']],
                  'flatten' => 'singleResult',
                  'selection' => ['institution']
                ]);
                $instId = $section['institution'];
              }
            }
          }
          if ($instId) {
            $this->calcAndSetStatus($instId);
          }
        }
    }
    public function calcAndSetStatus($id) {
        $d_result = $this->dataModel->read('demandedstreetsection',
          [
            'filter' => ['institution' => $id],
            'selection' => ['status']
          ]
        );
        $i_result = $this->dataModel->read('institution',
          [
            'filter' => ['id' => $id],
            'selection' => ['status', 'streetsection_complete']
          ]
        );

        if ($i_result[0]['streetsection_complete'] == 1) {
          $newstatus=DemandedStreetSection::STATUS_T30_OK;
        } else {
          $newstatus=DemandedStreetSection::STATUS_T30_UNKLAR;
        };
        foreach($d_result as $value) {
            switch($value['status']) {
              case DemandedStreetSection::STATUS_T30_FEHLT:
                $newstatus = DemandedStreetSection::STATUS_T30_FEHLT;
                break;
              case DemandedStreetSection::STATUS_T30_UNKLAR:
                if ($newstatus != DemandedStreetSection::STATUS_T30_FEHLT) {
                  $newstatus = DemandedStreetSection::STATUS_T30_UNKLAR;
                }
                break;
              case DemandedStreetSection::STATUS_T30_FORDERUNG:
                if (!(in_array($newstatus, [
                  DemandedStreetSection::STATUS_T30_FEHLT,
                  DemandedStreetSection::STATUS_T30_UNKLAR,
                ]))) {
                  $newstatus = DemandedStreetSection::STATUS_T30_FORDERUNG;
                }
                break;
              case DemandedStreetSection::STATUS_T30_ANGEORDNET:
                if (in_array($newstatus, [
                  DemandedStreetSection::STATUS_T30_ABGELEHNT,
                  DemandedStreetSection::STATUS_T30_OK,
                  ])) {
                    $newstatus = DemandedStreetSection::STATUS_T30_ANGEORDNET;
                }
                break;
              case DemandedStreetSection::STATUS_T30_ABGELEHNT:
                if (in_array($newstatus, [
                  DemandedStreetSection::STATUS_T30_OK,
                  ])) {
                    $newstatus = DemandedStreetSection::STATUS_T30_ABGELEHNT;
                }
                break;
              case DemandedStreetSection::STATUS_T30_OK:
                // do nothing
                break;
            }
        }
        if ($i_result[0]['status'] !=$newstatus) {
            $this->dataModel->update('institution',
            [
              'id' =>$id,
              'status' => $newstatus
            ]);
        }
    }
}

class InstitutionSqlReadQueryFactory extends AbstractReadQueryFactory {
  public function makeQuery($filter = [], $fieldSelection = [], $distinct = false, $order = [], $pagination = []) {
    if (count($fieldSelection) === 0) {
      $fieldSelection = $this->entity->fieldNames();
      array_push($fieldSelection, 'district');
      //array_push($fieldSelection, 'policedepartment');
    }
    $addDistrict = false;
    $addPK = false;
    if (in_array('district', $fieldSelection)) {
      $addDistrict = true;
      $index = array_search('district', $fieldSelection);
      if ($index !== false) {
        unset($fieldSelection[$index]);
      }
    }
    if (in_array('policedepartment', $fieldSelection)) {
      $addPK = true;
      $index = array_search('policedepartment', $fieldSelection);
      if ($index !== false) {
        unset($fieldSelection[$index]);
      }
    }
    $fieldSequence = Sql::Sequence($fieldSelection, function($f) {
      return Sql::Column($f, 'institution', null, $this->entity->getField($f)['type']);
    });
    if ($addDistrict) {
      $fieldSequence->addItem(Sql::Column('district', 'township', null, 'string', 'district'));
      //$fieldSequence->addItem(Sql::Column('name', 'township', null, 'string', 'township));
    }
    if ($addPK) {
        $fieldSequence->addItem(Sql::Column('name', 'polzeikommiseriate', null, 'string', 'policedepartment'));
        //$fieldSequence->addItem(Sql::Column('policedepartment_email', 'email', null, 'string', 'polzeikommiseriate'));

    }
    $select = 'SELECT';
    if ($distinct) {
        $select .= ' DISTINCT';
    }
    $orderQuery = '';
    if (count($order) > 0) {
        $orderQuery = ' '.Sql::Order($order)->toQuery();
    }
    $paginationQuery = '';
    if (count($pagination) > 0) {
        $paginationQuery = ' '.Sql::Pagination($pagination)->toQuery();
    }
    return sprintf("%s %s FROM `institution` JOIN `township` ON ST_CONTAINS(`township`.`geom`,`institution`.`position`) ".
                                            //"JOIN `polzeikommiseriate` ON ST_CONTAINS(`polzeikommiseriate`.`geom`,`institution`.`position`) ".
                                            "WHERE %s%s%s",
        $select,
        $fieldSequence->toQuery(),
        Sql::attachCreator($filter['tree'])->toQuery(),
        $orderQuery,
        $paginationQuery
    );
  }
}

class Email extends IdEntity {
    public function __construct() {
        parent::__construct('email');
        $this->addFields([
            ['name' => 'mail_subject', 'type' => 'varchar', 'length' => 255],
            ['name' => 'mail_start', 'type' => 'text'],
            ['name' => 'mail_body', 'type' => 'text'],
            ['name' => 'mail_end', 'type' => 'text'],
            ['name' => 'mail_send', 'type' => 'boolean'],
            ['name' => 'sent_on', 'type' => 'timestamp'],
            ['name' => 'person', 'type' => 'int'],
            ['name' => 'police_department', 'type' => 'int'],
            ['name' => 'demanded_street_section', 'type' => 'int'],
        ]);
    }
  
    public function observationUpdate($event) {
      if ($event['context'] === 'beforeInsert') {
        /**
         * Checke, ob der Forderungsstraßenabschnitt angegeben ist und exisitiert.
         */
        if (!array_key_exists('demanded_street_section', $event['data'])) {
          throw(new Exception('Missing demanded street section ID.', 400));
        }
        $sectionId = $event['data']['demanded_street_section'];
        $section = FlexAPI::superAccess()->read('demandedstreetsection', [
          'filter' => [ 'id' => $sectionId ],
          'flatten' => 'singleResult'
        ]);
        if (!$section) {
          throw(new Exception("No street section with ID = $sectionId found.", 400));
        }
        if ($section['mail_sent']) {
          throw(new Excpetion("Demand mail already sent for given street section.", 400));
        }
      }
      if ($event['context'] === 'onInsert') {
        /**
         * Ordne Email dem aktuellen Benutzer zu:
         * email.person <- userData.id
         */
        $userData = FlexAPI::superAccess()->read('userdata', [
          'filter' => [ 'user' => FlexAPI::guard()->getUsername() ],
          'flatten' => 'singleResult'
        ]);
        FlexAPI::superAccess()->update('email', [
          'id' => $event['insertId'],
          'person' => $userData['id']
        ]);
      }
    }
}

abstract class EmailPipe {
  private $userId = null;
  private $isAdmin = null;

  protected function getUserId() {
    if (!$this->userId) {
      $userData = FlexAPI::superAccess()->read('userdata', [
        'filter' => [ 'user' => FlexAPI::guard()->getUsername() ],
        'flatten' => 'singleResult'
      ]);
      if ($userData) {
        $this->userId = $userData['id'];
      }
    }
    return $this->userId;
  }

  protected function isAdmin() {
    if ($this->isAdmin === null) {
      $this->isAdmin = in_array('admin', FlexAPI::guard()->getUserRoles());
    }
    return $this->isAdmin;
  }
}

class FilterPrivateEmailFields extends EmailPipe implements IfEntityDataPipe {
  public function transform($entity, $data) {
      if ($entity->getName() === 'email') {
        if (array_key_exists('person', $data)) {
          $isPermitted = $this->isAdmin() || $this->getUserId() === $data['person'];
          if (!$isPermitted) {
            unset($data['mail_start']);
            unset($data['mail_end']);
          }
        } else {
          throw(new Exception("Field 'person' must be selected, when reading entity 'email'.", 400));
        }
      }
      return $data;
  }
}

class ProtectPrivateEmailFields extends EmailPipe implements IfEntityDataPipe {
  public function transform($entity, $data) {
      if ($entity->getName() === 'email') {
        $hasId = array_key_exists('id', $data);
        $hasMailStart = array_key_exists('mail_start', $data);
        $hasMailEnd = array_key_exists('mail_end', $data);
        if ($hasId && ($hasMailStart || $hasMailEnd)) {
          $email = FlexAPI::superAccess()->read('email', [
            'filter' => $entity->uniqueFilter($data['id']),
            'flatten' => 'singleResult',
            'selection' => ['id', 'person', 'mail_send']
          ]);
          $isPermitted = $this->isAdmin() || (!$email['mail_send'] && $this->getUserId() === $email['person']);
          if (!$isPermitted) {
            throw(new Exception("Not permitted to update field 'mail_start' or 'mail_end'", 403));
          }
        }
      }
      return $data;
  }
}

class DemandedStreetSection extends IdEntity {
  const STATUS_T30_UNKLAR=0;
  const STATUS_T30_FEHLT=3;
  const STATUS_T30_FORDERUNG=1;
  const STATUS_T30_OK=2;
  const STATUS_T30_ABGELEHNT=4;
  const STATUS_T30_ANGEORDNET=5;

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
          ['name' => 'status', 'type' => 'int'],
          ['name' => 'progress_report', 'type' => 'text'],
          ['name' => 'mail_sent', 'type' => 'boolean']
      ]);
  }

  public function observationUpdate($event) {
    if ($event['context'] === 'onInsert') {
      /**
       * Ordne Straßenabschnitt dem aktuellen Benutzer zu:
       * demandedstreetsection.person <- userData.id
       */
      $userData = FlexAPI::superAccess()->read('userdata', [
        'filter' => [ 'user' => FlexAPI::guard()->getUsername() ],
        'flatten' => 'singleResult'
      ]);
      FlexAPI::superAccess()->update('demandedstreetsection', [
        'id' => $event['insertId'],
        'person' => $userData['id']
      ]);
    }
    if ($event['context'] === 'beforeUpdate') {
      $section = FlexAPI::superAccess()->read('demandedstreetsection', [
        'filter' => [ 'id' => $event['data']['id'] ],
        'selection' => ['mail_sent'],
        'flatten' => 'singleResult'
      ]);
      if ($section['mail_sent']) {
        $allowedData = extractArray(
          ['id', 'status', 'street', 'house_no_from', 'house_no_to'],
          $event['data']
        );
        if (count($event['data']) !== count($allowedData)) {
          throw(new Exception("Only fields 'status', 'street', 'house_no_from' and 'house_no_to' my be updated after demand mail was sent.", 400));
        }

        $contains = array_key_exists('street', $event['data']);
        $contains = $contains || array_key_exists('house_no_from', $event['data']);
        $contains = $contains || array_key_exists('house_no_to', $event['data']);
        if ($contains && !in_array('admin', FlexAPI::guard()->getUserRoles())) {
          throw(new Exception("Fields 'street', 'house_no_from' and 'house_no_to' may not be changed by admin after demand mail was sent.", 400));
        }
      }
    }
  }
}

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
