const express = require('express');
const router = express.Router();

router.get('/', (req, res) => {
    // Lógica para manejar la solicitud GET de /bituser
    res.send('Esta es la ruta BitUser');
});

// Ruta para obtener los datos de un usuario específico excluyendo la contraseña
router.get('/getUserData/:id', (req, res) => {
    const userId = req.params.id;

    req.getConnection((err, conn) => {
        if (err) return res.send(err);

        // Realizar una consulta a la base de datos para obtener los datos del usuario por su ID
        conn.query('SELECT id, nombre, apellidos, fecha_nacimiento, email, pro FROM users WHERE id = ?', [userId], (err, rows) => {
            if (err) return res.status(200).send(err);

            // Verificar si se encontró un usuario con el ID proporcionado
            if (rows.length === 0) {
                return res.status(404).json({ message: 'Usuario no encontrado' });
            }

            // Devolver los datos del usuario excluyendo la contraseña
            res.status(200).json(rows[0]);
        });
    });
});

/* Obetener datos del usuario mediante post: email */
router.post('/getUserbyEmail', (req, res) => {
    const { email } = req.body;

    // Validar que se proporcionó el correo electrónico
    if (!email) {
        return res.status(400).json({ message: 'Por favor, ingresa el correo electrónico del usuario' });
    }

    req.getConnection((err, conn) => {
        if (err) return res.send(err);

        // Realizar una consulta a la base de datos para obtener los datos del usuario por su correo electrónico
        conn.query('SELECT id, nombre, apellidos, fecha_nacimiento, email, pro FROM users WHERE email = ?', [email], (err, rows) => {
            if (err) return res.status(500).send(err);

            // Verificar si se encontró un usuario con el correo electrónico proporcionado
            if (rows.length === 0) {
                return res.status(404).json({ message: 'Usuario no encontrado' });
            }

            // Devolver los datos del usuario excluyendo la contraseña
            res.status(200).json(rows[0]);
        });
    });
});

// Ruta para actualizar los datos de un usuario por su ID
router.put('/updateUserData/:id', (req, res) => {
    const userId = req.params.id;
    const { nombre, apellidos, fecha_nacimiento, email, pro } = req.body;

    // Verificar que se proporcionen al menos algunos datos para actualizar
    if (!nombre && !apellidos && !fecha_nacimiento && !email && !pro) {
        return res.status(400).json({ message: 'No se proporcionaron datos para actualizar' });
    }

    req.getConnection((err, conn) => {
        if (err) return res.send(err);

        // Realizar una consulta para verificar que el correo sea único en la base de datos
        conn.query('SELECT * FROM users WHERE email = ? AND id != ?', [email, userId], (err, rows) => {
            if (err) return res.send(err);

            // Verificar si ya existe un usuario con el mismo correo
            if (rows.length > 0) {
                return res.status(409).json({ message: 'El correo electrónico ya está registrado, por favor use otro' });
            }

            // Construir un objeto con los campos a actualizar (excluyendo el ID)
            const updateData = {};
            if (nombre) updateData.nombre = nombre;
            if (apellidos) updateData.apellidos = apellidos;
            if (fecha_nacimiento) updateData.fecha_nacimiento = fecha_nacimiento;
            if (email) updateData.email = email;
            if (pro !== undefined) updateData.pro = pro;

            // Realizar la actualización en la base de datos
            conn.query('UPDATE users SET ? WHERE id = ?', [updateData, userId], (err, result) => {
                if (err) return res.send(err);

                res.status(200).json({ message: 'Datos del usuario actualizados exitosamente' });
            });
        });
    });
});


module.exports = router;