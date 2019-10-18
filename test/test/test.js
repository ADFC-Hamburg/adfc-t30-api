
let fs = require('fs');
let chai = require('chai');
let chaiHttp = require('chai-http');
let should = chai.should();
let mocha_steps = require('mocha-steps');
let step = mocha_steps.step;
let config = require('./testConfig.json');
console.log(config);
let server =  config.url;

let setupPayload = {
    resetSecret: config.resetSecret,
    adminPassword: "pw"
};

chai.use(chaiHttp);

let userRegistration;
let institutions;

if (fs.existsSync('./test/data')) {
    userRegistration = JSON.parse(fs.readFileSync('./test/data/user-registration.json'));
    institutions = JSON.parse(fs.readFileSync('./test/data/institutions_reshaped.json'));
} else {
    userRegistration = JSON.parse(fs.readFileSync('./data/user-registration.json'));
    institutions = JSON.parse(fs.readFileSync('./data/institutions_reshaped.json'));
}



let user1 = userRegistration.ok[0];
let token1 = null;
let user2 = userRegistration.ok[1];
let token2 = null;

let adminToken = null;

let institutionsSlice0 = institutions.slice(0,5);
var institutionIds0 = null;
let institutionsSlice1 = institutions.slice(5,12);
let institutionsSlice2 = institutions.slice(12,20);

describe('API SETUP', function() {
    this.timeout(30000);
    step('it should setup API', function(done) {
        this.timeout(30000);
        setTimeout(done, 30000);
        chai.request(server)
            .post('/setup.php')
            .set('Content-Type', 'application/json')
            .send(setupPayload)
            .end((err, res) => {
                console.log(err);
                console.log(res);
                res.should.have.status(200);
                done();
        });
    });
});

describe('USER SYSTEM', function() {
    userRegistration.ok.forEach(user => {
        let verifyToken = null;
        step('it should regsiter a user', function(done) {
            chai.request(server)
                .post('/api/portal.php')
                .set('Content-Type', 'application/json')
                .send(user)
                .end((err, res) => {
                    res.should.have.status(200);
                    verifyToken = res.body.token;
                    done();
            });
        });
        step('it should verify an account using a token', function(done) {
            // let tokenFile = '../api/' + user.username + '.json';
            // content = JSON.parse(fs.readFileSync(tokenFile));
            chai.request(server)
                .get('/api/portal.php')
                .set('Content-Type', 'application/json')
                .query({ verify: verifyToken })
                .end((err, res) => {
                    res.should.have.status(200);
                    // fs.unlinkSync(tokenFile);
                    done();
            });
        });
    });
    step('it should fail on bad email', function(done) {
        chai.request(server)
            .post('/api/portal.php')
            .set('Content-Type', 'application/json')
            .send(userRegistration.badEmail)
            .end((err, res) => {
                res.should.have.status(400);
                done();
        });
    });

    step('it should return a token, when a user logs in', function(done) {
        chai.request(server)
            .post('/api/portal.php')
            .send({ concern: 'login', username: user1.username, password: user1.password })
            .end((err, res) => {
                res.should.have.status(200);
                res.body.should.be.a('object');
                res.body.should.have.property('token');
                token1 = res.body.token;
                done();
            });
    });

    step('it should return a token, when a user logs in', function(done) {
        chai.request(server)
            .post('/api/portal.php')
            .send({ concern: 'login', username: user2.username, password: user2.password })
            .end((err, res) => {
                res.should.have.status(200);
                res.body.should.be.a('object');
                res.body.should.have.property('token');
                token2 = res.body.token;
                done();
            });
    });

    step('it should return a token, when a admin logs in', function(done) {
        chai.request(server)
            .post('/api/portal.php')
            .send({ concern: 'login', username: 'admin', password: setupPayload.adminPassword })
            .end((err, res) => {
                res.should.have.status(200);
                res.body.should.be.a('object');
                res.body.should.have.property('token');
                adminToken = res.body.token;
                done();
            });
    });

    step('it should fail on bad password', function(done) {
        chai.request(server)
            .post('/api/portal.php')
            .send({ concern: 'login', username: 'admin', password: 'try' })
            .end((err, res) => {
                res.should.have.status(401);
                done();
            });
    });
});

describe('CRUD userdata', function() {
    step('it should fail on bad token', function(done) {
        chai.request(server)
            .get('/api/crud.php')
            .set('Access-Control-Allow-Credentials', 'a390rjvkjsner2j4nb')
            .query({ entity: 'userdata' })
            .end((err, res) => {
                res.should.have.status(401);
                done();
            });
    });

    step('user should be able to read own user-data', function(done) {
        chai.request(server)
            .get('/api/crud.php')
            .set('Access-Control-Allow-Credentials', token1)
            .query({ entity: 'userdata' })
            .end((err, res) => {
                res.should.have.status(200);
                res.body.should.be.a('array');
                res.body.length.should.be.eql(1);
                res.body[0].should.include(user1.userData);
                user1.userData.id = res.body[0].id;
                done();
            });
    });

    step('user should be able to read own user-data', function(done) {
        chai.request(server)
            .get('/api/crud.php')
            .set('Access-Control-Allow-Credentials', token2)
            .query({ entity: 'userdata' })
            .end((err, res) => {
                res.should.have.status(200);
                res.body.should.be.a('array');
                res.body.length.should.be.eql(1);
                res.body[0].should.include(user2.userData);
                done();
            });
    });

    step('registered users should be able to update userdata', function(done) {
        chai.request(server)
            .put('/api/crud.php')
            .set('Access-Control-Allow-Credentials', token1)
            .send({ id: user1.userData.id , street_house_no: 'Quatschstr. 69' })
            .query({ entity: 'userdata' })
            .end(function(err, res) {
                res.should.have.status(200);
                done();
            });
    });

    step('registered user should not be able to delete user-data', function(done) {
        chai.request(server)
            .delete('/api/crud.php')
            .set('Access-Control-Allow-Credentials', token1)
            .query({ entity: 'userdata' })
            .end((err, res) => {
                res.should.have.status(403);
                done();
            });
    });

    step('guest should not be able to access user data', function(done) {
        chai.request(server)
            .get('/api/crud.php')
            .query({ entity: 'userdata', filter: `[user,'${user1.username}']` })
            .end((err, res) => {
                res.should.have.status(403);
                done();
            });
    });

    step('admin should be able to read all user data', function(done) {
        chai.request(server)
            .get('/api/crud.php')
            .set('Access-Control-Allow-Credentials', adminToken)
            .query({ entity: 'userdata' })
            .end((err, res) => {
                res.should.have.status(200);
                res.body.should.be.a('array');
                res.body.length.should.be.eql(2);
                done();
            });
    });
});

describe('CRUD institution', function() {
    step('it should be possible for registered users to create (post) institutions', function(done) {
        chai.request(server)
            .post('/api/crud.php')
            .set('Access-Control-Allow-Credentials', token1)
            .send(institutionsSlice0)
            .query({ entity: 'institution' })
            .end(function(err, res) {
                res.should.have.status(200);
                res.body.should.have.property('id');
                res.body.id.should.be.a('array');
                res.body.id.length.should.be.eql(institutionsSlice0.length);
                institutionIds0 = res.body.id;
                done();
            });
    });

    step('it should not be possible for guests to create (post) institutions', function(done) {
        chai.request(server)
            .post('/api/crud.php')
            .send(institutionsSlice0)
            .query({ entity: 'institution' })
            .end(function(err, res) {
                res.should.have.status(403);
                done();
            });
    });

    step('it should not be possible for reg. users to update (put) institution', function(done) {
        let instUpdate = { id: institutionIds0[1], street_house_no: 'change no. 1' };
        chai.request(server)
            .put('/api/crud.php')
            .set('Access-Control-Allow-Credentials', token1)
            .send(instUpdate)
            .query({ entity: 'institution' })
            .end(function(err, res) {
                res.should.have.status(200);
                done();
            });
    });

    step('it should not be possible for no-admins to delete institutions', function(done) {
        chai.request(server)
            .delete('/api/crud.php')
            .set('Access-Control-Allow-Credentials', token1)
            .query({ entity: 'institution', filter: `[id,${institutionIds0[0]}]` })
            .end(function(err, res) {
                res.should.have.status(403);
                done();
            });
    })

    step('it should be possible for admins to delete institutions', function(done) {
        chai.request(server)
            .delete('/api/crud.php')
            .set('Access-Control-Allow-Credentials', adminToken)
            .query({ entity: 'institution', filter: `[id,${institutionIds0[0]}]` })
            .end(function(err, res) {
                res.should.have.status(200);
                done();
            });
    })

    step('non-admins should not get history', function(done) {
        chai.request(server)
            .get('/api/monitor.php')
            .query({ entity: 'institution', id: institutionIds0[2] })
            // .set('Access-Control-Allow-Credentials', token1)
            .end(function(err, res) {
                res.should.have.status(403);
                done();
            });
    });
    step('history of unchanged resource should be have length 1', function(done) {
        chai.request(server)
            .get('/api/monitor.php')
            .query({ entity: 'institution', id: institutionIds0[2] })
            .set('Access-Control-Allow-Credentials', adminToken)
            .end(function(err, res) {
                res.should.have.status(200);
                res.body.should.have.property('history');
                res.body.history.should.be.a('array');
                res.body.history.length.should.be.eql(1);
                done();
            });
    });

    step('history of resource changed 1 time should be have length 2', function(done) {
        chai.request(server)
            .get('/api/monitor.php')
            .query({ entity: 'institution', id: institutionIds0[1] })
            .set('Access-Control-Allow-Credentials', adminToken)
            .end(function(err, res) {
                res.should.have.status(200);
                res.body.should.have.property('history');
                res.body.history.should.be.a('array');
                res.body.history.length.should.be.eql(2);
                done();
            });
    });

    step('it should not be possible for reg. users to update (put) institution with full data', function(done) {
        let instUpdate={"id":institutionIds0[1],
                        "position":[10.184035567714226,53.496844354475336],
                        "name":"KiTa Sportini, Sport- und Bewegungskindertagesstätte",
                        "street_house_no":"Billwerder Billdeich 609",
                        "zip":"21033",
                        "city":"Hamburg",
                        "type":"1",
                        "streetsection_complete":true};


        chai.request(server)
            .put('/api/crud.php')
            .set('Access-Control-Allow-Credentials', token1)
            .send(instUpdate)
            .query({ entity: 'institution' })
            .end(function(err, res) {
                res.should.have.status(200);
                done();
            });
    });

});
