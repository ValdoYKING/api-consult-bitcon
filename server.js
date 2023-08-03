const express = require('express');
const http = require('http');
const mysql = require('mysql');
const myconn = require('express-myconnection');
const cors = require('cors');
const jwt = require('jsonwebtoken');
const socketIO = require('socket.io');

const routes = require('./routes');
const login = require('./login');
const bitUserRouter = require('./bitUser');
const bitProRouter = require('./bitPro');

const app = express();
const server = http.createServer(app);
const io = socketIO(server);


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

// Clave secreta para la generación del token
const secretKey = 'ABDCE';

// Middleware de autenticación para verificar el token
function verificarToken(req, res, next) {
    const token = req.header('Authorization');
    if (!token) return res.status(401).json({ message: 'Acceso denegado. Token no proporcionado.' });

    jwt.verify(token, secretKey, (err, decoded) => {
        if (err) return res.status(403).json({ message: 'Token inválido.' });

        // Agregar los datos del usuario decodificados al objeto request para ser usados en otras rutas protegidas
        req.user = decoded;
        next();
    });
}

// Configuración CORS
app.use(cors({
    // origin: 'https://unrivaled-smakager-8d710f.netlify.app'
    origin: ['*', 'https://unrivaled-smakager-8d710f.netlify.app']
}));

// routes
app.get('/', (req, res) => {
    res.send('API successfully connected');
});
app.use('/api', routes);
app.use('/login', login);
// app.use('/bituser', verificarToken,  bitUserRouter);
app.use('/bituser', bitUserRouter);
app.use('/bitpro', bitProRouter);

// Escuchar conexiones de clientes
io.on('connection', (socket) => {
    console.log('Nuevo cliente conectado');

    // Aquí puedes definir eventos y lógica para manejar la comunicación con los clientes
    // Por ejemplo:
    socket.on('mensaje', (data) => {
        console.log('Mensaje recibido:', data);
        // Aquí puedes emitir el mensaje a todos los clientes conectados
        io.emit('mensaje', data);
    });

    // Otros eventos y lógica aquí...
});

//server running
/* app.listen(app.get('port'), () => {
    console.log('Express server listening on port ' + app.get('port'));
}); */

// Iniciar el servidor
const port = process.env.PORT || 9000;
server.listen(port, () => {
    console.log(`Servidor en funcionamiento en el puerto ${port}`);
});