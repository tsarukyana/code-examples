// Load dependencies
const _ = require('lodash');
const fs = require('fs');
const path = require('path');
const IMAGE_SIZES = require('../config/constants').IMAGE_SIZES;
const UPLOAD_PATH = path.resolve(__dirname, '../..', process.env.IMAGE_STORAGE);

const EmailTemplate = require('email-templates');
const nodeMailer = require('nodemailer');
const crypto = require('crypto');

module.exports = {
  validateEmail(email) {
    const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(email);
  },
  imageFilter(req, file, cb) {
// supported image file mimetypes
    const allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];

    if (_.includes(allowedMimes, file.mimetype)) {
// allow supported image files
      cb(null, true);
    } else {
// throw error for invalid files
      cb(new Error('Invalid file type. Only jpg, png and gif image files are allowed.'));
    }
  },
  removeFileByPath(filePath) {
    let matches, pathsplit;

    let paths = [];

    // create paths for responsive images
    pathsplit = filePath.split('/');
    matches = pathsplit.pop().match(/^(.+?)_.+?\.(.+)$/i);


    if (matches) {
      paths = _.map(IMAGE_SIZES, function(size) {
        return UPLOAD_PATH + pathsplit.join('/') + '/' + (matches[1] + '_' + size + '.' + matches[2]);
      });
    }

    // delete the files from the filesystem
    _.each(paths, function(_path) {
      if (fs.existsSync(_path)) {
        fs.unlink(_path);
      }
    });
  },
  sendEmailConfirmation(req, user) {
    let poolConfig = {
      pool: true,
      host: process.env.SMTP_HOST,
      port: process.env.SMTP_PORT,
      secure: true, // use TLS
      auth: {
        user: process.env.SMTP_AUTH_USER,
        pass: process.env.SMTP_AUTH_PASSWORD
      }
    };

    const email = new EmailTemplate({
      views: {
        options: {
          extension: 'ejs' // <---- HERE
        }
      }
    });
    const confirmationLink = process.env.FRONTEND_URL + '/confirmEmail?code=' + user.confirmCode;
    let name = '';
    if (user.firstName !== null) {
      name += user.firstName;
    }
    if (user.lastName !== null) {
      name += ' '+ user.lastName;
    }
    const locales = {
      message: req.__('email_confirmation_message'),
      title: req.__('email_confirmation_title'),
      confirmation_link: confirmationLink,
      click_to_confirm: req.__('click_to_confirm'),
      hello: req.__('email_confirmation_hello', {name: name})
    };

    return Promise
      .all([
        email.render('../templates/emailConfirmation/html', locales),
        email.render('../templates/emailConfirmation/text', locales)
      ])
      .then(([ html, text ]) => {
        const mailOptions = {
          from: process.env.SENDER_EMAIL, // sender address
          to: user.email, // list of receivers
          subject: req.__('email_confirmation'), // Subject line
          text: text, // plain text body
          html: html // html body
        };

        let transporter = nodeMailer.createTransport(poolConfig);

        return transporter.verify(function(error, success) {
          if (error) {
            return {error: error, success: false, info: null};
          } else {
            console.log('nodeMailer Server is ready to take our messages');

            // send mail with defined transport object
            return transporter.sendMail(mailOptions, (error, info) => {
              if (error) {
                return {error: error, success: false, info: info};
              }
              return {error: false, success: success, info: info};
            });
          }
        });
      })
      .catch(console.error);
  },
  encrypt(text) {
    let cipher = crypto.createCipher(process.env.CRYPTO_ALGORITHM, process.env.CRYPTO_PASSWORD);
    let crypted = cipher.update(text,'utf8','hex');
    crypted += cipher.final('hex');
    return crypted;
  },
  decrypt(text) {
    let decipher = crypto.createDecipher(process.env.CRYPTO_ALGORITHM, process.env.CRYPTO_PASSWORD);
    let dec = decipher.update(text,'hex','utf8');
    dec += decipher.final('utf8');
    return dec;
  },
};
