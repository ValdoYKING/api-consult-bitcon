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
                    // Devolver el nombre del usuario logueado
                    return res.status(200).json({ nombre: user.nombre, apellidos: user.apellidos, pro:true });
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

    // Validar que todos los campos obligatorios estén presentes
    if (!nombre || !apellidos || !fecha_nacimiento || !email || !password) {
        return res.status(400).json({ message: 'Por favor, completa todos los campos obligatorios' });
    }

    // Generar el hash de la contraseña
    const hashedPassword = bcrypt.hashSync(password, 10);

    // Verificar si el correo ya existe en la base de datos
    req.getConnection((err, conn) => {
        if (err) return res.send(err);

        conn.query('SELECT * FROM users WHERE email = ?', [email], (err, rows) => {
            if (err) return res.send(err);

            // Verificar si ya existe un usuario con el mismo correo
            if (rows.length > 0) {
                return res.status(409).json({ message: 'El correo electrónico ya está registrado, por favor use otro' });
            }

            // Crear un objeto con los datos de la cuenta
            const accountData = {
                nombre,
                apellidos,
                fecha_nacimiento,
                email,
                password: hashedPassword // Guardar la contraseña hasheada en la base de datos
            };

            // Guardar los datos de la cuenta en la base de datos
            conn.query('INSERT INTO users SET ?', [accountData], (err, rows) => {
                if (err) return res.send(err);

                res.json('Usuario registrado exitosamente');
            });
        });
    });
});

login.put('/updatePass/:id', (req, res) => {
    const { id } = req.params;
    const { contrasenaAntigua, contrasenaNueva } = req.body;

    // Validar que la antigua contraseña y la nueva contraseña estén presentes
    if (!contrasenaAntigua || !contrasenaNueva) {
        return res.status(400).json({ message: 'Por favor, ingresa la antigua contraseña y la nueva contraseña' });
    }

    // Obtener los datos del usuario desde la base de datos
    req.getConnection((err, conn) => {
        if (err) return res.send(err);

        conn.query('SELECT * FROM users WHERE id = ?', [id], (err, rows) => {
            if (err) return res.send(err);

            // Verificar si el usuario existe en la base de datos
            if (rows.length === 0) {
                return res.status(404).json({ message: 'Usuario no encontrado' });
            }

            const user = rows[0];

            // Verificar la antigua contraseña
            bcrypt.compare(contrasenaAntigua, user.password, (err, result) => {
                if (err) return res.send(err);

                if (!result) {
                    return res.status(401).json({ message: 'La antigua contraseña no coincide' });
                }

                // Generar el hash de la nueva contraseña
                const hashedcontrasenaNueva = bcrypt.hashSync(contrasenaNueva, 10);

                // Actualizar la contraseña en la base de datos
                conn.query('UPDATE users SET password = ? WHERE id = ?', [hashedcontrasenaNueva, id], (err, rows) => {
                    if (err) return res.send(err);

                    res.json('Contraseña actualizada exitosamente');
                });
            });
        });
    });
});


module.exports = login;
