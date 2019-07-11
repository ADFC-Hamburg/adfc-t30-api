<?php

include_once __DIR__ . '/vendor/bensteffen/flexapi/datamodel/DataModelFactory.php';
include_once __DIR__ . '/vendor/bensteffen/flexapi/datamodel/DataModel.php';
include_once __DIR__ . '/vendor/bensteffen/flexapi/datamodel/DataEntity.php';
include_once __DIR__ . '/vendor/bensteffen/flexapi/datamodel/IdEntity.php';

class T30Factory extends DataModelFactory {
    public function buildDataModel() {
        $dataModel = new DataModel();

        $dataModel->addEntities([
            new Adresse(),
            new Person(),
            new Einrichtung(),
            new EinrichtungsArt(),
            new EinrichtungsQuelle(),
            new BezirkHamburg(),
            new BeziehungZurEinrichtung(),
            new ForderungsStrassenAbschnitt(),
            new PolizeiKommissariat(),
            new Tempo30(),
            new ZeitlicheBeschraenkung(),
            new Email(),
            new TextVorlage()
        ]);

        $dataModel->addReference('beziehungzureinrichtung.person -> person');
        $dataModel->addReference('beziehungzureinrichtung.einrichtung -> einrichtung');

        $dataModel->addReference('einrichtung.bezirk -> bezirkhamburg');
        $dataModel->addReference('einrichtung.art -> einrichtungsart');
        $dataModel->addReference('einrichtung.quelle -> einrichtungsquelle');

        $dataModel->addReference('forderungsstrassenabschnitt.bezirk -> bezirkhamburg');
        $dataModel->addReference('forderungsstrassenabschnitt.person -> person');
        $dataModel->addReference('forderungsstrassenabschnitt.einrichtung -> einrichtung');
        $dataModel->addReference('forderungsstrassenabschnitt.polizeikommissariat -> polizeikommissariat');
        $dataModel->addReference('forderungsstrassenabschnitt.tempo30 -> tempo30');

        $dataModel->addReference('tempo30.zeitliche_beschraenkung -> zeitlichebeschraenkung');

        $dataModel->addReference('email.mailvorlage -> textvorlage');
        $dataModel->addReference('email.person -> person');
        $dataModel->addReference('email.polizeikommissariat -> polizeikommissariat');
        $dataModel->addReference('email.forderungsstrassenabschnitt -> forderungsstrassenabschnitt');
    
        // $dataModel->addObservation([
        //     'observerName' => 'userdata',
        //     'subjectName' => 'userdata',
        //     'context' => ['beforeInsert', 'onInsert']
        // ]);

        return $dataModel;
    }
}

class Adresse extends IdEntity {
    public function __construct() {
        parent::__construct('adresse');
        $this->addFields([
            ['name' => 'strasse', 'type' => 'varchar', 'length' => 255],
            ['name' => 'zusatz', 'type' => 'varchar', 'length' => 255],
            ['name' => 'plz', 'type' => 'varchar', 'length' => 5],
            ['name' => 'ort', 'type' => 'varchar', 'length' => 255],
        ]);
    }

    public function observationUpdate($event) { }
}

class Person extends IdEntity {
    public function __construct() {
        parent::__construct('person');
        $this->addFields([
            ['name' => 'user', 'type' => 'varchar', 'length' => FlexAPI::get('maxUserNameLength'), 'primary' => true],
            ['name' => 'strasse', 'type' => 'varchar', 'length' => 255],
            ['name' => 'plz', 'type' => 'varchar', 'length' => 5],
            ['name' => 'ort', 'type' => 'varchar', 'length' => 255],
            ['name' => 'telefon', 'type' => 'varchar', 'length' => 20, 'notNull' => false],
            ['name' => 'mobil', 'type' => 'varchar', 'length' => 20, 'notNull' => false]
        ]);
    }

    public function observationUpdate($event) {

    }
}

class Einrichtung extends IdEntity {
    public function __construct() {
        parent::__construct('einrichtung');
        $this->addFields([
            ['name' => 'name', 'type' => 'varchar', 'length' => 255],
            ['name' => 'art', 'type' => 'smallint'],
            ['name' => 'strasse', 'type' => 'varchar', 'length' => 255],
            ['name' => 'adresszusatz', 'type' => 'varchar', 'length' => 255],
            ['name' => 'plz', 'type' => 'varchar', 'length' => 5],
            ['name' => 'ort', 'type' => 'varchar', 'length' => 255],
            ['name' => 'quelle', 'type' => 'smallint'],
            ['name' => 'bezirk', 'type' => 'smallint'],
            ['name' => 'breitengrad', 'type' => 'decimal', 'length' => '8,6'],
            ['name' => 'laengengrad', 'type' => 'decimal', 'length' => '8,6'],
        ]);
    }

    public function observationUpdate($event) { }
}

class PolizeiKommissariat extends DataEntity {
    public function __construct() {
        parent::__construct('polizeikommissariat');
        $this->addFields([
            ['name' => 'pk-nummer', 'type' => 'int', 'primary' => true],
            ['name' => 'region', 'type' => 'varchar', 'length' => 255],
            ['name' => 'strasse', 'type' => 'varchar', 'length' => 255],
            ['name' => 'plz', 'type' => 'varchar', 'length' => 5],
            ['name' => 'ort', 'type' => 'varchar', 'length' => 255],
            ['name' => 'telefon', 'type' => 'varchar', 'length' => 20],
            ['name' => 'email', 'type' => 'varchar', 'length' => 255],
            // ['name' => 'polygon', 'type' => 'polygon']
        ]);
    }

    public function observationUpdate($event) { }
}

class Tempo30 extends IdEntity {
    public function __construct() {
        parent::__construct('tempo30');
        $this->addFields([
            ['name' => 'eingerichtet_in', 'type' => 'boolean'],
            ['name' => 'angeordnet_in', 'type' => 'boolean'],
            ['name' => 'eingerichtet_am', 'type' => 'date'],
            ['name' => 'angeordnet_am', 'type' => 'date'],
            ['name' => 'grund_tempo30', 'type' => 'varchar', 'length' => 1000],
            ['name' => 'ablehnungsgrund_tempo30', 'type' => 'varchar', 'length' => 1000],
            ['name' => 'zeitliche_beschraenkung', 'type' => 'smallint'],
            ['name' => 'abgelehnt_in', 'type' => 'boolean'],
        ]);
    }

    public function observationUpdate($event) { }
}

class Email extends IdEntity {
    public function __construct() {
        parent::__construct('email');
        $this->addFields([
            ['name' => 'mailentwurf_nutzer', 'type' => 'text'],
            ['name' => 'abgeschickter_text', 'type' => 'text'],
            ['name' => 'abgeschickt_am', 'type' => 'timestamp'],
            ['name' => 'mailvorlage', 'type' => 'smallint'],
            ['name' => 'person', 'type' => 'int'],
            ['name' => 'polizeikommissariat', 'type' => 'int'],
            ['name' => 'forderungsstrassenabschnitt', 'type' => 'int'],
        ]);
    }

    public function observationUpdate($event) { }
}

class ForderungsStrassenAbschnitt extends IdEntity {
    public function __construct() {
        parent::__construct('forderungsstrassenabschnitt');
        $this->addFields([
            ['name' => 'strasse_von', 'type' => 'varchar', 'length' => 255],
            ['name' => 'strasse_bis', 'type' => 'varchar', 'length' => 255],
            ['name' => 'plz', 'type' => 'varchar', 'length' => 5],
            ['name' => 'ort', 'type' => 'varchar', 'length' => 255],
            ['name' => 'bezirk', 'type' => 'smallint'],
            ['name' => 'mehrspurig_in', 'type' => 'boolean'],
            ['name' => 'nutzeranmerkung', 'type' => 'varchar', 'length' => 4000],
            ['name' => 'buslinien', 'type' => 'varchar', 'length' => 255],
            ['name' => 'viel_busverkehr', 'type' => 'boolean'],
            ['name' => 'grund_verlangsamung_bus', 'type' => 'varchar', 'length' => 4000],
            ['name' => 'person', 'type' => 'int'],
            ['name' => 'einrichtung', 'type' => 'int'],
            ['name' => 'polizeikommissariat', 'type' => 'int'],
            ['name' => 'tempo30', 'type' => 'int']
        ]);
    }

    public function observationUpdate($event) { }
}

class EinrichtungsArt extends DataEntity {
    public function __construct() {
        parent::__construct('einrichtungsart');
        $this->addFields([
            ['name' => 'artnummer', 'type' => 'smallint', 'primary' => true],
            ['name' => 'art', 'type' => 'varchar', 'length' => 255],
        ]);
    }

    public function observationUpdate($event) { }
}

class EinrichtungsQuelle extends DataEntity {
    public function __construct() {
        parent::__construct('einrichtungsquelle');
        $this->addFields([
            ['name' => 'quellennummer', 'type' => 'smallint', 'primary' => true],
            ['name' => 'quelle', 'type' => 'varchar', 'length' => 255],
        ]);
    }

    public function observationUpdate($event) { }
}

class BezirkHamburg extends DataEntity {
    public function __construct() {
        parent::__construct('bezirkhamburg');
        $this->addFields([
            ['name' => 'bezirknummer', 'type' => 'smallint', 'primary' => true],
            ['name' => 'bezirk', 'type' => 'varchar', 'length' => 255],
        ]);
    }

    public function observationUpdate($event) { }
}

class ZeitlicheBeschraenkung extends DataEntity {
    public function __construct() {
        parent::__construct('zeitlichebeschraenkung');
        $this->addFields([
            ['name' => 'nummer', 'type' => 'smallint', 'primary' => true],
            ['name' => 'beschraenkung_inhalt', 'type' => 'varchar', 'length' => 255],
        ]);
    }

    public function observationUpdate($event) { }
}

class TextVorlage extends DataEntity {
    public function __construct() {
        parent::__construct('textvorlage');
        $this->addFields([
            ['name' => 'vorlagennummer', 'type' => 'smallint', 'primary' => true],
            ['name' => 'text', 'type' => 'text'],
        ]);
    }

    public function observationUpdate($event) { }
}

class BeziehungZurEinrichtung extends IdEntity {
    public function __construct() {
        parent::__construct('beziehungzureinrichtung');
        $this->addFields([
            ['name' => 'beziehungsart', 'type' => 'varchar', 'length' => 1000],
            ['name' => 'person', 'type' => 'int'],
            ['name' => 'einrichtung', 'type' => 'int'],
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

