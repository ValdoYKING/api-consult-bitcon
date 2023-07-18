const express = require('express');
const bcrypt = require('bcrypt');
const bitUser = express.Router();

bitUser.get('/', (req, res) => {
    req.getConnection((err, conn) => {
        if (err) return res.send(err)

        conn.query('SELECT * FROM users', (err, rows) => {
            if (err) return res.send(err)

            res.json(rows);
        });
    });
});