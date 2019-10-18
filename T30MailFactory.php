<?php

include_once __DIR__ . '/vendor/bensteffen/bs-php-utils/Array2Table.php';

class T30MailFactory {
    public static function makeVerificationMailGenerator($format) {
        if ($format === 'html') {
            return function($token, $url) {
                return ""
                . "Hallo,"
                . "<br>bitte klicke <a href=\"".$url."\">hier</a>, um deinen Account bei der Kampagne des ADFC Hamburg \"Tempo 30 an sozialen Einrichtungen\" zu aktivieren."
                . "<br>Oder gebe das Token <b>".$token."</b> in das Forumlar ein."
                . "<br>Viel Spaß!"
                . nl2br(T30MailFactory::footer());
            };
        } elseif ($format === 'plain') {
            return function($token, $url) {
                return ""
                . "Hallo,"
                . "\nbitte besuche:"
                . "\n\n".$url
                . "\n\num deinen Account bei der Kampagne des ADFC Hamburg \"Tempo 30 an sozialen Einrichtungen\" zu aktivieren, oder gibt das Token:"
                . "\n".$token
                . "\n\nin das Forumlar ein."
                . "\nViel Spaß!"
                . T30MailFactory::footer();
            };
        }
    }

    public static function makeChangeNotificationMailGenerator($format) {
        return function($entityName, $state, $metaData, $fieldChanges) use($format) {
            $table = [];
            foreach ($state as $fieldName => $value) {
                array_push($table, ['Attribut' => $fieldName, 'Wert' => $value, 'Änderung' => '']);
            }
            $names = array_column($table, 'Attribut');
            foreach ($fieldChanges as $change) {
                $i = array_search($change['fieldName'], $names);
                $table[$i]['Wert'] = $change['oldValue'];
                $table[$i]['Änderung'] = $change['newValue'];
            }
            $changeTable = new Array2Table($table);
            $at = $metaData['timeStamp'];
            $stub = ""
            . "\nHallo,"
            . "\n\nam ".date('d.m.Y', $at)." um ".date('H:i:s', $at)
            . "\n\nwurde(n) durch '".$metaData['user']."'"
            . "\n\nin einem Datensatz der Tabelle '$entityName' folgendende Änderung(en) durchgeführt:"
            . "\n\n\n";
            if ($format === 'html') {
                return utf8_decode(
                    nl2br($stub)
                    . "<tt>".nl2br(str_replace(' ', '&nbsp;', $changeTable->toAscii()))."</tt>"
                    . "<br><br>"
                );
            } elseif ($format === 'plain') {
                return utf8_decode(
                    $stub
                    . $changeTable->toAscii()
                    . "\n\n"
                );
            }
        };
    }

    public static function makeDemandSentNotificationMail($demandMail, $institution, $format) {
        $section = $demandMail['demanded_street_section'];
        $message = ""
        . "Hallo,"
        . "\n\nfür den Straßenabschnitt"
        . ' "'.$section['street']." ".$section['house_no_from']." - ".$section['house_no_to'].'"'." an der Einrichtung"
        . "\n\n".$institution['name']
        . "\n".$institution['street_house_no']
        . "\n\nist nun bereits eine Forderungs-Email an das zuständige Polizeikommissariat versendet worden."
        . "\n\nEs ist nun über das ADFC-Portal nicht mehr möglich eine weitere Forderung für diesen Abschnitt abzuschicken."
        . "\nDu kannst aber gerne noch weitere Forderungsstraßenabschnitte für die oben genannte Institution einstellen."
        . T30MailFactory::footer();

        if ($format === 'html') {
            return nl2br($message);
        } else if ($format === 'plain') {
            return $message;
        }
    }

    protected static function footer() {
        return ""
        . "\n\n--"
        . "\nTempo 30 an sozialen Einrichtungen"
        . "\nEine Kampagne des ADFC Hamburg"
        . "\n\nwww.hamburg.adfc.de/tempo30sozial"
        . "\ntempo30sozial@hamburg.adfc.de"
        . "\n\nAllgemeiner Deutscher Fahrrad-Club"
        . "\nLandesverband Hamburg e. V."
        . "\nKoppel 34 - 36"
        . "\n20099 Hamburg"
        . "\n\nAnsprechpartnerin"
        . "\nWiebke Hansen"
        . "\nTel: (040) 32 90 41 15";
    }
}