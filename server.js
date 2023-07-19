const express = require('express');
const mysql = require('mysql');
const myconn = require('express-myconnection');
const cors = require('cors');

const routes = require('./routes');
const login = require('./login');
const bitUserRouter = require('./bitUser');


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
app.use(cors());

// Configuración CORS
app.use(cors({
    // origin: 'https://unrivaled-smakager-8d710f.netlify.app'
    origin: ['*','https://unrivaled-smakager-8d710f.netlify.app']
}));

// routes
app.get('/', (req, res) => {
    res.send('API successfully connected');
});
app.use('/api', routes);
app.use('/login', login);
app.use('/bituser', bitUserRouter);

//server running
app.listen(app.get('port'), () => {
    console.log('Express server listening on port ' + app.get('port'));
});