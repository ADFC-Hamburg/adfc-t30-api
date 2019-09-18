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

const einrichtungen = institutions.map(i => {
    return {
        name: i.name,
        type: i.type,
        street_house_no: i.street + ' ' + i.number,
        address_supplement: '',
        zip: i.zip,
        city: 'Hamburg',
        district: '',
        position: [parseFloat(i.lon.toFixed(6)), parseFloat(i.lat.toFixed(6))],
        streetsection_complete: 0,
        status: 0
    };
});

fs.writeFileSync('institutions_reshaped.json', JSON.stringify(einrichtungen));