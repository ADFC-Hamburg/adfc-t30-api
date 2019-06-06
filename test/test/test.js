
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

userRegistration = JSON.parse(fs.readFileSync('./data/user-registration.json'));

describe('T30 API', function() { 
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
        step('it should verify a account using a token', function(done) {
            content = JSON.parse(fs.readFileSync('../api/' + user.username + '.json'));
            chai.request(server)
                .get('/api/portal.php')
                .set('Content-Type', 'application/json')
                .query({ verify: content.token })
                .end((err, res) => {
                    res.should.have.status(200);
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
    userRegistration.missing.forEach(user => {
        step('it should fail on missing data', function(done) {
            chai.request(server)
                .post('/api/portal.php')
                .set('Content-Type', 'application/json')
                .send(user)
                .end((err, res) => {
                    res.should.have.status(400);
                    done();
            });
        });
    });
});
