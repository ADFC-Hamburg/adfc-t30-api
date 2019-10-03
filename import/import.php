<?php
$url = "https://github.com/ADFC-Hamburg/adfc-t30-paten-frontend/files/3520913/Daten.T30.alle.soz.Einr.Mai.2018.2019-08-20.xlsx";
$localFileName="Daten_T30_Alle_sozEinr.xlsx";


require('vendor/autoload.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;

function download($url, $localName) {
    if (!file_exists($localName)) {
        $content = file_get_contents($url);
        file_put_contents($localName, $content);
    }
}

download($url, $localFileName);
$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($localFileName);
$workSheet=$spreadsheet->getSheet(0);


function getVal($workSheet,$buchstabe, $index) {
    return $workSheet->getCell($buchstabe.$index)->getValue();
}
$colToField= [
    "position" => function ($w,$i) {
        $val=getVal($w,'A',$i);
        if ($val == "") {
            // FIXME geocode
            return null;
        }
        return array_map("floatval",explode(",",preg_replace('/Point \((\S*) (\S*)\)/','$1,$2',$val)));
    },
    "type" => function ($w,$i) {
        return getVal($w,'B',$i);
    },
    "name" => function ($w,$i) {
        return getVal($w,'C',$i);
    },
    "street_house_no" => function ($w,$i) {
        return getVal($w,'D',$i)." ".getVal($w,'E',$i);;
    },
    "city" => function ($w, $i) {
        return "Hamburg";
    },
    "zip" => function ($w, $i) {
        return getVal($w,'F',$i);
    },
    "streetsection_complete" => function($w, $i) {
        return 0;
    },
    "address_supplement" => function($w, $i) {
        return "";
    },
    "by_the_records" => function($w, $i) {
        $out=[];
        if (getVal($w,'H',$i) != "") {
            $out[]="Einrichtung von der LEA gemeldet Aug 2019";
        }
        if (getVal($w,'I',$i) != "") {
            $out[]="ADFC sontige Kenntnis, T30 angeordnet am ".getVal($w,'I',$i);
        }
        if (getVal($w,'J',$i) != "") {
            $out[]="ADFC sontige Kenntnis, T30 umgesetzt am ".getVal($w,'J',$i);
        }
        if (getVal($w,'K',$i) != "") {
            $out[]="Laut Anfrage 03/19, T30 angeordet am ".getVal($w,'K',$i);
        }
        if (getVal($w,'L',$i) != "") {
            $out[]="Laut Anfrage 03/19, T30 eingeführt am ".getVal($w,'L',$i);
        }
        if (getVal($w,'M',$i) != "") {
            $out[]="Laut Anfrage 03/19: Antrag aus Bevölkerung";
        }
        if (getVal($w,'N',$i) != "") {
            $out[]="kein Tempo 30 am 30.11.16";
        }
        if (getVal($w,'O',$i) != "") {
            $out[]="Tempo 30 am 30.11.16";
        }
        if (getVal($w,'P',$i) != "") {
            $out[]="Tempo 30 seit 1.12.16";
        }
        if (getVal($w,'Q',$i) == "1") {
            $out[]="6-22 Uhr";
        }
        if (getVal($w,'Q',$i) == "2") {
            $out[]="0-24 Uhr";
        }
        if (getVal($w,'Q',$i) == "3") {
            $out[]="Mo.-Fr. 7:00-9:00 Uhr und 15:00-19:00 Uhr";
        }
        if (getVal($w,'R',$i) != "") {
            $out[]="Hier wurde eine Geschwindigkeitsmessung durchgeführt";
        }
        if (getVal($w,'S',$i) != "") {
            $out[]="Schriftliche Anträge auf verkehrsbeschränkende Maßnahmen vorliegend";
        }
        if (getVal($w,'T',$i) != "") {
            $out[]="Verkehrsunfallauswertung erstellt unter laufender Nummer: ".getVal($w,'T',$i);
        }
        if ((getVal($w,'U',$i) != "") && (getVal($w,'U',$i) != "-")) {
            $out[]="Buslininen: ".getVal($w,'U',$i);
        }
        if (getVal($w,'V',$i) == "1") {
            $out[]="Bustaktung mindestens 6 Fahrten/Std. in wenigstens einer Fahrtrichtung zur Hauptverkehrszeit";
        }
        if (getVal($w,'V',$i) == "2") {
            $out[]="Bustaktung weniger als 6 Fahrten/Std. in wenigstens einer Fahrtrichtung zur Hauptverkehrszeit";
        }
        if (getVal($w,'W',$i) == "1") {
                $out[]="Mehrspurig";
        }
        if (getVal($w,'W',$i) == "2") {
                $out[]="Einspurig";
        }

        return join("\n",$out);
    },
];
$moreData=true;
$i=2;
$data=[];
while ($workSheet->getCell("C".$i)->getValue() !='') {
    foreach($colToField as $fieldname => $func) {
        $data[$i-2][$fieldname]=$func($workSheet,$i);
    };
    $i++;
};

print json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
