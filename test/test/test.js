
let fs = require('fs');
let chai = require('chai');
let chaiHttp = require('chai-http');
let should = chai.should();

let server = 'http://localhost/adfc/adfc-t30-api';
// let server = 'http://ben-steffen.de/t30';

let setupPayload = {
    resetSecret: "IBs1G38VUCiH6HEIlMrqXEGXkpaq9JKy",
    // resetSecret: '<secret-for-ben-steffen-de>',
	adminPassword: "pw"
};

chai.use(chaiHttp);

let userRegistration = JSON.parse(fs.readFileSync('./data/user-registration.json'));
let institutions = JSON.parse(fs.readFileSync('./data/institutions.json'));

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
    step('it should setup API', function(done) {
        chai.request(server)
            .post('/setup.php')
            .set('Content-Type', 'application/json')
            .send(setupPayload)
            .end((err, res) => {
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
            .send({ user: user1.username, street: 'Quatschstr.', number: 69 })
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
        chai.request(server)
            .put('/api/crud.php')
            .set('Access-Control-Allow-Credentials', token1)
            .send({ id: institutionIds0[1], street: 'change no. 1' })
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

});

describe('CRUD patenschaft', function() {
    let patenschaftId = null;
    step('it should be possible for registered users to create (post) patenschaft', function(done) {
        chai.request(server)
            .post('/api/crud.php')
            .set('Access-Control-Allow-Credentials', token1)
            .send({ institution: 2, relationship: 'Betreuer' })
            .query({ entity: 'patenschaft' })
            .end(function(err, res) {
                res.should.have.status(200);
                patenschaftId = res.body.id;
                done();
            });
    });

    step('it should be possible for registered users to create (post) patenschaft', function(done) {
        chai.request(server)
            .post('/api/crud.php')
            .set('Access-Control-Allow-Credentials', token2)
            .send({ institution: 3, relationship: 'Lehrer' })
            .query({ entity: 'patenschaft' })
            .end(function(err, res) {
                res.should.have.status(200);
                done();
            });
    });

    step('created Patenschaft should have correct user assigned', function(done) {
        chai.request(server)
            .get('/api/crud.php')
            .set('Access-Control-Allow-Credentials', token1)
            .query({ entity: 'patenschaft', filter: `[id,${patenschaftId}]`, refs: '(format,key)' })
            .end(function(err, res) {
                res.should.have.status(200);
                res.body[0].should.have.property('user');
                res.body[0].user.should.be.eql(user1.username)
                done();
            });
    });

    step('guests should not be able to read Patenschaft', function(done) {
        chai.request(server)
            .get('/api/crud.php')
            .query({ entity: 'patenschaft', filter: `[id,${patenschaftId}]` })
            .end(function(err, res) {
                res.should.have.status(403);
                done();
            });
    });

    step('registered should not be able to read other Patenschaft', function(done) {
        chai.request(server)
            .get('/api/crud.php')
            .set('Access-Control-Allow-Credentials', token2)
            .query({ entity: 'patenschaft', filter: `[id,${patenschaftId}]` })
            .end(function(err, res) {
                res.should.have.status(404);
                done();
            });
    });
});
