const express = require('express');
const routes = express.Router();

routes.get('/', (req, res) => {
    req.getConnection((err, conn) => {
        if (err) return res.send(err)

        conn.query('SELECT * FROM users', (err, rows) => {
            if (err) return res.send(err)

            res.json(rows);
        });
    });
});

routes.post('/', (req, res) => {
    req.getConnection((err, conn) => {
        if (err) return res.send(err)
        // console.log(req.body);
        conn.query('INSERT INTO users SET ?', [req.body], (err, rows) => {
            if (err) return res.send(err)

            // res.json(rows);
            res.json('rows inserted');
        });
    });
});

routes.delete('/:id', (req, res) => {
    req.getConnection((err, conn) => {
        if (err) return res.send(err)

        conn.query('DELETE FROM users WHERE id = ?', [req.params.id], (err, rows) => {
            if (err) return res.send(err)

            res.json('rows deleted');
        });
    });
});

routes.put('/:id', (req, res) => {
    req.getConnection((err, conn) => {
        if (err) return res.send(err)

        conn.query('UPDATE users set ? WHERE id = ?', [req.body, req.params.id], (err, rows) => {
            if (err) return res.send(err)

            res.json('rows updated');
        });
    });
});


module.exports = routes;