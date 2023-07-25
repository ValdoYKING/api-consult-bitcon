const express = require('express');
const routerPro = express.Router();

routerPro.get('/', (req, res) => {
    // Lógica para manejar la solicitud GET de /bituser
    res.send('Esta es la ruta BitPro');
});

// Ruta para actualizar los datos en la tabla user_pro_conf
routerPro.post('/updateUserPro/:id', (req, res) => {
    const { id } = req.params;

    // Obtener los campos a actualizar que llegan en la solicitud
    const { tipo_notificacion, tipo_alerta, tipo_moneda, cantidad_diferente } = req.body;

    // Verificar si el usuario existe en la base de datos
    req.getConnection((err, conn) => {
        if (err) return res.send(err);

        conn.query('SELECT * FROM users WHERE id = ?', [id], (err, rows) => {
            if (err) return res.send(err);

            // Verificar si el usuario existe en la base de datos
            if (rows.length === 0) {
                return res.status(404).json({ message: 'Usuario no encontrado' });
            }

            const user = rows[0];

            // Verificar si el usuario ya tiene un registro en user_pro_conf
            conn.query('SELECT * FROM user_pro_conf WHERE id_user = ?', [user.id], (err, rows) => {
                if (err) return res.send(err);

                // Si el usuario ya tiene un registro, se actualizan los datos
                if (rows.length > 0) {
                    const updateData = {};

                    // Actualizar solo los campos que llegan en la solicitud
                    if (tipo_notificacion) updateData.tipo_notificacion = tipo_notificacion;
                    if (tipo_alerta) updateData.tipo_alerta = tipo_alerta;
                    if (tipo_moneda) updateData.tipo_moneda = tipo_moneda;
                    if (cantidad_diferente) updateData.cantidad_diferente = cantidad_diferente;

                    conn.query('UPDATE user_pro_conf SET ? WHERE id_user = ?', [updateData, user.id], (err, result) => {
                        if (err) return res.status(500).json({ message: 'Error al actualizar la configuración' });

                        res.status(200).json({ message: 'Configuración actualizada exitosamente' });
                    });
                } else {
                    // Si el usuario no tiene un registro, se crea uno nuevo con los campos proporcionados
                    const proConfData = {
                        id_user: user.id,
                        tipo_notificacion,
                        tipo_alerta,
                        tipo_moneda,
                        cantidad_diferente
                    };

                    conn.query('INSERT INTO user_pro_conf SET ?', [proConfData], (err, result) => {
                        if (err) return res.status(500).json({ message: 'Error al registrar la configuración' });

                        res.status(200).json({ message: 'Configuración registrada exitosamente' });
                    });
                }
            });
        });
    });
});

// Ruta para actualizar los datos en la tabla user_pro_conf
routerPro.post('/updateUserProMail', (req, res) => {
    // const { id } = req.params;

    // Obtener los campos a actualizar que llegan en la solicitud
    //const { email,tipo_notificacion, tipo_alerta, tipo_moneda, cantidad_diferente,monedasFavoritas,alertas } = req.body;
    const { email,alertas,frecuencia,monedas,monedasFavoritas,name,tipoAlerta } = req.body;

    // Verificar si el usuario existe en la base de datos
    req.getConnection((err, conn) => {
        if (err) return res.send(err);

        conn.query('SELECT * FROM users WHERE email = ?', [email], (err, rows) => {
            if (err) return res.send(err);

            // Verificar si el usuario existe en la base de datos
            if (rows.length === 0) {
                return res.status(404).json({ message: 'Usuario no encontrado' });
            }

            const user = rows[0];

            // Verificar si el usuario ya tiene un registro en user_pro_conf
            conn.query('SELECT * FROM user_pro_conf WHERE id_user = ?', [user.id], (err, rows) => {
                if (err) return res.send(err);

                // Si el usuario ya tiene un registro, se actualizan los datos
                if (rows.length > 0) {
                    const updateData = {};
                    // Actualizar solo los campos que llegan en la solicitud
                    if (alertas) updateData.alertas = alertas;
                    if (tipoAlerta) updateData.tipoAlerta = tipoAlerta;
                    if (name) updateData.name = name;
                    if (frecuencia) updateData.frecuencia = frecuencia;
                    if (monedas) updateData.monedas = monedas;
                    if (monedasFavoritas) updateData.monedasFavoritas = monedasFavoritas;
                    // if (cantidad_diferente) updateData.cantidad_diferente = cantidad_diferente;

                    conn.query('UPDATE user_pro_conf SET ? WHERE id_user = ?', [updateData, user.id], (err, result) => {
                        if (err) return res.status(500).json({ message: 'Error al actualizar la configuración' });

                        res.status(200).json({ message: 'Configuración actualizada exitosamente' });
                    });
                } else {
                    // Si el usuario no tiene un registro, se crea uno nuevo con los campos proporcionados
                    const proConfData = {
                        id_user: user.id,
                        alertas,
                        tipoAlerta,
                        name,
                        frecuencia,
                        monedas,
                        monedasFavoritas
                    };

                    conn.query('INSERT INTO user_pro_conf SET ?', [proConfData], (err, result) => {
                        if (err) return res.status(500).json({ message: 'Error al registrar la configuración' });

                        res.status(200).json({ message: 'Configuración registrada exitosamente' });
                    });
                }
            });
        });
    });
});


module.exports = routerPro;