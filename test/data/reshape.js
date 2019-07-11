const fs = require('fs');

const institutions = JSON.parse(fs.readFileSync('institutions.json'));

const bezirk2number = {
    'Altona': 1,
    'Bergedorf': 2,
    'Eimsb\u00fcttel': 3,
    'Harburg': 4,
    'Hamburg-Mitte': 5,
    'Hamburg-Nord': 6,
    'Wandsbek': 7
}

const einrichtungen = institutions.map(inst => {
    return {
        name: inst.name,
        art: inst.type,
        strasse: inst.street + ' ' + inst.number,
        adresszusatz: ' ',
        plz: inst.zip,
        ort: 'Hamburg',
        quelle: 3,
        bezirk: bezirk2number[inst.district],
        laengengrad: inst.lat.toFixed(6),
        breitengrad: inst.lon.toFixed(6)
    };
});

fs.writeFileSync('einrichtungen.json', JSON.stringify(einrichtungen));