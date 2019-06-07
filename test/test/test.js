
let fs = require('fs');
let chai = require('chai');
let chaiHttp = require('chai-http');
let should = chai.should();

let server = 'http://localhost/adfc/adfc-t30-api';

let setupPayload = {
	resetSecret: "IBs1G38VUCiH6HEIlMrqXEGXkpaq9JKy",
	adminPassword: "pw"
};

chai.use(chaiHttp);

let userRegistration = JSON.parse(fs.readFileSync('./data/user-registration.json'));
let institutions = JSON.parse(fs.readFileSync('./data/institutions.json'));

let user1 = userRegistration.ok[0];
let token1 = null;
let user2 = userRegistration.ok[1];

let adminToken = null;

let institutionsSlice0 = institutions.slice(0,5);
let institutionIds0 = null;
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
        step('it should regsiter a user', function(done) {
            chai.request(server)
                .post('/api/portal.php')
                .set('Content-Type', 'application/json')
                .send(user)
                .end((err, res) => {
                    res.should.have.status(200);
                    done();
            });
        });
        step('it should verify an account using a token', function(done) {
            let tokenFile = '../api/' + user.username + '.json';
            content = JSON.parse(fs.readFileSync(tokenFile));
            chai.request(server)
                .get('/api/portal.php')
                .set('Content-Type', 'application/json')
                .query({ verify: content.token })
                .end((err, res) => {
                    res.should.have.status(200);
                    fs.unlinkSync(tokenFile);
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
});

describe('CRUD', function() {
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

    step('for guests it should be possible to create (post) instiutions', function(done) {
        chai.request(server)
            .post('/api/crud.php')
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

    step('it should not be possible for no-admins to delete institutions', function(done) {
        chai.request(server)
            .delete('/api/crud.php')
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
});
