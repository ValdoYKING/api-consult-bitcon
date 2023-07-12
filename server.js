const express = require('express');
const mysql = require('mysql');
const myconn = require('express-myconnection');

const routes = require('./routes');
const login = require('./login');

const app = express();
app.set('port', process.env.PORT || 9000);
const dboptions = {
    // host: 'localhost',
    host: 'fecha-enlinea.com',
    port: 3306,
    // user: 'root',
    user: 'fechaenl_sistemas',
    // password: '',
    password: 'st*YtvNT5nDg',
    // database: 'bitcoin'
    database: 'fechaenl_bit'
}

//middlewares
app.use(myconn(mysql, dboptions, 'single'));
app.use(express.json());

// routes
app.get('/', (req, res) => {
    res.send('API successfully connected');
});
app.use('/api', routes);
app.use('/login', login);

//server running
app.listen(app.get('port'), () => {
    console.log('Express server listening on port ' + app.get('port'));
});