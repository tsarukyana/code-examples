const express = require('express');
const i18n = require("i18n");
const logger = require('morgan');
const bodyParser = require('body-parser');
require('dotenv').config();
const passport = require('./server/config/auth/passport');
// Set up the express app
const LANGUAGES = require('./server/config/constants').LANGUAGES;

const app = express();

app.use(express.static("uploads/images"));

app.use(function (req, res, next) {

    // Website you wish to allow to connect
    res.setHeader('Access-Control-Allow-Origin', '*');

    // Request methods you wish to allow
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, PATCH, DELETE');

    // Request headers you wish to allow
    res.setHeader('Access-Control-Allow-Headers', 'Origin,X-Requested-With,Content-Type,Authorization,X-Lang');

    // Set to true if you need the website to include cookies in the requests sent
    // to the API (e.g. in case you use sessions)
    res.setHeader('Access-Control-Allow-Credentials', true);

    // Pass to next layer of middleware
    next();
});

// multi language
i18n.configure({
  locales:['en', 'hy'],
  updateFiles: false, // no need rewrite langages json files
  directory: __dirname + '/locales'
});
app.use(i18n.init);

app.use(function(req, res, next) {
  if (!req.headers['x-lang'] || !LANGUAGES.CODES.includes(req.headers['x-lang'])) {
    req.headers['x-lang'] = LANGUAGES.DEFAULT;
  }

  req.locale = req.headers['x-lang'];
  res.locale = req.headers['x-lang'];
  i18n.setLocale(req.headers['x-lang']);
  i18n.setLocale(req, req.headers['x-lang']);
  i18n.setLocale(res, req.headers['x-lang']);

  next();
});

// Log requests to the console.
app.use(logger('dev'));

// parse application/json
app.use(bodyParser.json());
// parse application/x-www-form-urlencoded
app.use(bodyParser.urlencoded({extended: false}));

app.use(passport.initialize());

app.set('superSecret', process.env.SECRET); // secret variable

// Require our routes into the application.
require('./server/routes')(app, passport);

app.get('*', (req, res) => {
    res.status(200).send({
        message: 'Welcome to the beginning of Giver.',
    });
});

module.exports = app;
