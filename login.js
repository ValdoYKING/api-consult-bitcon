const express = require('express');
const bcrypt = require('bcrypt');
const login = express.Router();

// Ruta para el inicio de sesión
login.post('/', (req, res) => {
    const { email, password } = req.body;

    // Validar que los campos obligatorios estén presentes
    if (!email || !password) {
        return res.status(400).json({ message: 'Por favor, ingresa el email y la contraseña' });
    }

    // Verificar si el usuario existe en la base de datos
    req.getConnection((err, conn) => {
        if (err) return res.send(err);

        conn.query('SELECT * FROM users WHERE email = ?', [email], (err, rows) => {
            if (err) return res.send(err);

            // Verificar si se encontró un usuario con el email proporcionado
            if (rows.length === 0) {
                return res.status(401).json({ message: 'Email o contraseña incorrectos' });
            }

            const user = rows[0];

            // Verificar la contraseña
            bcrypt.compare(password, user.password, (err, result) => {
                if (err) return res.send(err);

                if (result) {
                    return res.json({ message: 'Inicio de sesión exitoso' });
                } else {
                    return res.status(401).json({ message: 'Email o contraseña incorrectos' });
                }
            });
        });
    });
});


// Ruta para registrar una nueva cuenta
login.post('/register', (req, res) => {
    const { nombre, apellidos, fecha_nacimiento, email, password } = req.body;

    // Validar que los campos obligatorios estén presentes
    if (!nombre || !apellidos || !fecha_nacimiento || !email || !password) {
        return res.status(400).json({ message: 'Por favor, completa todos los campos obligatorios' });
    }

    // Generar el hash de la contraseña
    const hashedPassword = bcrypt.hashSync(password, 10);

    // Crear un objeto con los datos de la cuenta
    const accountData = {
        nombre,
        apellidos,
        fecha_nacimiento,
        email,
        password: hashedPassword // Guardar la contraseña hasheada en la base de datos
    };

    // Guardar los datos de la cuenta en la base de datos
    req.getConnection((err, conn) => {
        if (err) return res.send(err)

        conn.query('INSERT INTO users SET ?', [accountData], (err, rows) => {
            if (err) return res.send(err)

            res.json('rows inserted');
        });
    });
});
//Preuba produccion

module.exports = login;
