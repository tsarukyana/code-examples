const { User } = require('../models');
const { Role } = require('../models');
const { Language } = require("../models");
const { UserRole } = require("../models");
const { ResetPassword } = require("../models");
const UserNotifications = require("../models").UserNotifications;
const Notification = require("../models").Notification;
const Invitations = require("../models").Invitations;
const IpLocation = require("../models").IpLocation;
const bcrypt = require("bcrypt");
const jwt = require('jsonwebtoken');
const fs = require('fs');
const request = require('request');
const MESSAGE_TYPES = require('../config/constants').MESSAGE_TYPES;
const ROLE_TYPES = require('../config/constants').ROLE_TYPES;
const { MainHelper } = require('../helpers');
const path = require('path');
const validator = require('validator');
const EmailTemplate = require('email-templates');
const nodeMailer = require('nodemailer');
let uploadDirectory = path.join('uploads','images','/');
const ip_location = require("iplocation").default;

const download =  function(uri, filename, callback){
  request.head(uri, function(err, res, body){
    request(uri).pipe(fs.createWriteStream(filename)).on('close', callback);
  });
};
module.exports = {

  async signIn(req, res) {
    const data = JSON.parse(req.body.data);

    const email = data.email;
    const pass = data.password;
    const user = await User.findOne({where: {email: email}});
    if (!user) {
      res.status(200).send({
        message: req.__('user_not_found'),
        type: MESSAGE_TYPES.ERROR,
      });
      return;
    }
    if (user.status === 0) {
      res.status(200).send({
        message: req.__('inactive_user_check_email'),
        type: MESSAGE_TYPES.ERROR,
        inactiveUser: true,
      });
      return;
    }
    const roles = await user.getRoles();

    if (roles && roles.length > 0) {
      let permissions = [];
      roles.forEach((role, i) => {
        permissions.push(role.name);
      });

      if (bcrypt.compareSync(pass, user.passwordDigest)) {
        let token = jwt.sign({userId: user.id, permissions: permissions}, process.env.SECRET, {
          expiresIn: 86400 // expires in 24 hours
        });
        res.status(200).send({message: req.__('you_are_logged'), type: MESSAGE_TYPES.SUCCESS, user: user, token: token})
      } else {
        res.status(200).send({message: req.__('password_doesnt_match'), type: MESSAGE_TYPES.ERROR})
      }
    } else {
      res.status(200).send({message: req.__('something_wrong'), type: MESSAGE_TYPES.ERROR})
    }
  },
  async signUp(req, res) {
      let data = JSON.parse(req.body.data);
      if (!data.email) {
        res.status(200).send({message: req.__('email_cannot_be_empty'), type: MESSAGE_TYPES.ERROR});
        return;
      }
      const _lang = req.headers['x-lang']
      data.image = null;
      try {
        const language = await  Language.findOne({where: {code: _lang}});
        if (language) {
          const user = await User.findOne({where: {email: data.email}});
          if(user !== null) {
            res.status(200).send({message: req.__('user_already_exists'), type: MESSAGE_TYPES.ERROR});
            return;
          }
          let sendConfirmationCode = true;

          if (data.inviterMail) {
            if (data.realInviteEmail === data.email) {
              const invitor = await Invitations.findOne({where: {userId: data.inviterId, active: false}}); // for secure reason need to check in db
              if (data.realInviteEmail === invitor.inviteEmail) {
                sendConfirmationCode = false;
              }
            }
          }
          const forEncrypt = data.email + '/' + Math.floor(Date.now() / 1000);
          const encryptedCode = MainHelper.encrypt(forEncrypt);
          try{
            const newUser = await User
              .create({
                firstName: data.firstName,
                lastName: data.lastName,
                email: data.email,
                image: data.image,
                password: data.password,
                password_confirmation: data.password_confirmation,
                password_digest: null,
                languageId: language.id,
                confirmCode: sendConfirmationCode ? encryptedCode : null,
                status:  sendConfirmationCode ? 0 : 1, // inactive / active
              });
            if (newUser) {
              let role = undefined;
              if (data.role && data.role.roleId) {
                role = await Role.findByPk(data.role.roleId);
              } else {
                role = await Role.findOne({where: {name: ROLE_TYPES.USER}}); // default user role
              }
              if (role) {
                const userRole = await UserRole.create({
                  userId: newUser.id,
                  roleId: role.id
                });

                const roles = await newUser.getRoles();
                if (roles && roles.length > 0) {

                  if (!sendConfirmationCode) { // invite user situation
                    let permissions = [];
                    roles.forEach((role, i) => {
                      permissions.push(role.name);
                    });
                    let token = jwt.sign({userId: newUser.id, permissions: permissions}, process.env.SECRET, {
                      expiresIn: 86400 // expires in 24 hours
                    });

                    const invitations = await Invitations.findAll({where: {inviteEmail: data.email}});
                    const notification = await Notification.findOne({where: {name: 'Friend_invitation'}})
                    let userNotifications = []
                    invitations.forEach((invitation, i) => {
                      invitation.update({
                        inviteId: newUser.id,
                        active: true,
                      })
                      userNotifications.push({
                        userId: newUser.id,
                        fromUserId: invitation.userId,
                        name: notification.name,
                        notificationType: notification.id
                      })
                    });
                    UserNotifications.bulkCreate(userNotifications);
                    let result ={
                      email: newUser.email,
                      firstName: newUser.firstName,
                      lastName: newUser.lastName,
                      image: newUser.image,
                      languageId : newUser.languageId
                    };
                    res.status(200).send({message: req.__('user_created'), type: MESSAGE_TYPES.SUCCESS, user: result, token: token});

                  } else {
                    MainHelper.sendEmailConfirmation(req, newUser); // sending email confirmation
                    res.status(200).send({message: req.__('user_created_email_confirmation'), type: MESSAGE_TYPES.SUCCESS, isConfirmEmail: true});
                  }
                } else {
                  res.status(200).send({message: req.__('user_role_issue'), type: MESSAGE_TYPES.ERROR});
                }
              } else {
                res.status(200).send({message: req.__('user_role_issue'), type: MESSAGE_TYPES.ERROR});
              }
            } else {
              res.status(200).send({message: req.__('user_register_issue'), type: MESSAGE_TYPES.ERROR});
            }
          }catch (e) {
            res.status(200).send({message: e.message, type: MESSAGE_TYPES.ERROR});
          }
        } else {
          res.status(200).send({message: req.__('site_language_issue'), type: MESSAGE_TYPES.ERROR});
        }
      } catch (error) {
        res.status(400).send(error);
      }
  },
  userInfo(req, res) {
      if (req.headers['authorization']) {
          const token = req.headers['authorization'].replace('Bearer ', '')
          const decoded = jwt.verify(token, process.env.SECRET);
          User.findByPk(decoded.userId).then(user => {
              user.passwordDigest = null;
              res.status(200).send({user: user});
          }).catch(e => {
              res.status(400).send({error: e});
          })
      } else {
          res.status(200).send({error: req.__('unauthorized_request'), type: MESSAGE_TYPES.ERROR});
      }
  },
  logOut(req, res) {
    res.clearCookie('user_sid');
    res.redirect('/');
  },

  async authFacebookCallback(req, res) {
    let fbUser = req.user._json; // this comes from facebook strategy
    let imagePath = '';
    const language = await  Language.findOne({where: {code: 'en'}});
    if (language) {
      const user = await User.findOne({where: {email: fbUser.email}});
      if (user !== null){
        const roles = await user.getRoles();
        let permissions = [];
        if (roles && roles.length > 0) {
          roles.forEach((role, i) => {
            permissions.push(role.name);
          });
        }
        if(user.image === null){
          const dir = "" + user.id;
          if(!fs.existsSync(uploadDirectory + dir)){
            fs.mkdirSync((uploadDirectory + dir))
          }
          let downloadDirectory = uploadDirectory + dir + "/"
          await download(fbUser.picture.data.url, downloadDirectory + dir + '.png', function(){
            console.log('done');
          });
          imagePath ='/' + dir + '/' +dir + '.png'
        }
        return user
          .update({
            facebookId: fbUser.id,
            image:user.image || imagePath || null,
            confirmCode: null,
            status: 1,
          })
          .then(() =>{
            let result ={
              email:user.email,
              firstName:user.firstName,
              lastName: user.lastName,
              image:user.image,
              languageId : user.languageId,
              id: user.id,
              phoneNumber:user.phoneNumber,
              dateBirthday:user.dateBirthday,
              facebookId:user.facebookId,
              about:user.about
            };
            let token = jwt.sign({userId: result.id, permissions: permissions}, process.env.SECRET, {
              expiresIn: 86400 // expires in 24 hours
            });
            res.redirect(process.env.FRONTEND_URL+ '/?signed_token_fb_or_google=' +token);
          })  // Send back the updated user.
          .catch((error) => res.status(400).send(error));
      }else {
        let randomPassword = Math.random().toString(36).slice(-12);
        const newUser = await User
          .create({
            firstName: fbUser.first_name,
            lastName: fbUser.last_name,
            email: fbUser.email,
            image: null,
            password: randomPassword,
            password_confirmation:randomPassword,
            password_digest: null,
            languageId: language.id,
            facebookId: fbUser.id,
            status: 1, // automatically activated
          });
        if (newUser) {
          const dir = "" + newUser.id;
          if(!fs.existsSync(uploadDirectory + dir)){
            fs.mkdirSync((uploadDirectory + dir))
          }
          let downloadDirectory = uploadDirectory + dir + "/"
          await download(fbUser.picture.data.url, downloadDirectory + dir + '.png', function(){
            console.log('done');
          });
          imagePath ='/' + dir + '/' +dir + '.png'
          const role = await Role.findOne({where: {name: ROLE_TYPES.USER}});
          if (role) {
            const userRole = await UserRole.create({
              userId: newUser.id,
              roleId: role.id
            });
            const roles = await newUser.getRoles();
            if (roles && roles.length > 0) {
              let permissions = [];
              roles.forEach((role, i) => {
                permissions.push(role.name);
              });
              newUser
                .update({
                  image:imagePath || null,
                });
              let token = jwt.sign({userId: newUser.id, permissions: permissions}, process.env.SECRET, {
                expiresIn: 86400 // expires in 24 hours
              });
              res.redirect(process.env.FRONTEND_URL+ '/?signed_token_fb_or_google=' +token);
            }
          } else {
            res.status(200).send({message: req.__('user_role_issue'), type: MESSAGE_TYPES.ERROR});
          }
        }
      }
    }
  },

  async authGoogleCallback(req,res) {
    let googleUser = req.user; // this comes from facebook strategy
    let userEmail = req.user.emails.find((a)=>{
      if(a.type === 'account'){
        return true
      }
    });
    let profilePhoto = googleUser.photos[0].value.replace('sz=50','sz=200');
    let imagePath = '';
    let downloadDirectory = '';
    const language = await  Language.findOne({where: {code: 'en'}});
    if (language) {
      const user = await User.findOne({where: {email: userEmail.value}});
      if (user !== null){
        const roles = await user.getRoles();
        let permissions = [];
        if (roles && roles.length > 0) {
          roles.forEach((role, i) => {
            permissions.push(role.name);
          });
        }
        if(user.image === null){
          const dir = "" + user.id;
          if(!fs.existsSync(uploadDirectory + dir)){
            fs.mkdirSync((uploadDirectory + dir))
          }
          downloadDirectory  = uploadDirectory + dir + "/"
          await download(profilePhoto, downloadDirectory + dir + '.png', function(){
            console.log('done');
          });
          imagePath ='/' + dir + '/' +dir + '.png'
        }
        return user
          .update({
            googleId: googleUser.id,
            image:user.image || imagePath || null,
            confirmCode: null,
            status: 1,
          })
          .then(() =>{
            let result ={
              email:user.email,
              firstName:user.firstName,
              lastName: user.lastName,
              image:user.image,
              languageId : user.languageId,
              id: user.id,
              phoneNumber:user.phoneNumber,
              dateBirthday:user.dateBirthday,
              googleId:user.googleId,
              about:user.about
            };
            let token = jwt.sign({userId: result.id, permissions: permissions}, process.env.SECRET, {
              expiresIn: 86400 // expires in 24 hours
            });
            res.redirect(process.env.FRONTEND_URL+ '/?signed_token_fb_or_google=' +token);
          })  // Send back the updated user.
          .catch((error) => res.status(400).send(error));
      }else {
        let randomPassword = Math.random().toString(36).slice(-12);
        const newUser = await User
          .create({
            firstName: googleUser.name.givenName,
            lastName: googleUser.name.familyName,
            email: userEmail.value,
            image: null,
            password: randomPassword,
            password_confirmation:randomPassword,
            password_digest: null,
            languageId: language.id,
            googleId: googleUser.id,
            status: 1, // automatically activated
          });
        if (newUser) {
          const dir = "" + newUser.id;
          if(!fs.existsSync(uploadDirectory + dir)){
            fs.mkdirSync((uploadDirectory + dir))
          }
          downloadDirectory = uploadDirectory + dir + "/"
          await download(profilePhoto, downloadDirectory + dir + '.png', function(){
            console.log('done');
          });
          imagePath ='/' + dir + '/' +dir + '.png'
          const role = await Role.findOne({where: {name: ROLE_TYPES.USER}});
          if (role) {
            const userRole = await UserRole.create({
              userId: newUser.id,
              roleId: role.id
            });
            const roles = await newUser.getRoles();
            if (roles && roles.length > 0) {
              let permissions = [];
              roles.forEach((role, i) => {
                permissions.push(role.name);
              });
              newUser
                .update({
                  image:imagePath || null,
                });
              let token = jwt.sign({userId: newUser.id, permissions: permissions}, process.env.SECRET, {
                expiresIn: 86400 // expires in 24 hours
              });
              res.redirect(process.env.FRONTEND_URL+ '/?signed_token_fb_or_google=' +token);
            }
          } else {
            res.status(200).send({message: req.__('user_role_issue'), type: MESSAGE_TYPES.ERROR});
          }
        }
      }
    }
    // res.redirect(process.env.FRONTEND_URL+ '/?google_token=');
  },
  async confirmEmail(req, res) {
    if (req.body.confirmCode) {
      let confirmCode = MainHelper.decrypt(req.body.confirmCode);
      const confirmCodeArr = confirmCode.split("/");
      if (confirmCodeArr[0] && MainHelper.validateEmail(confirmCodeArr[0])) {
        const encryptedTime = confirmCodeArr[1] ? confirmCodeArr[1] : null;
        if (encryptedTime) {
          const durationTime = parseInt(encryptedTime) + 300; // after 5 minutes
          const nowTime = parseInt(Math.floor(Date.now() / 1000));
           if (durationTime > nowTime) { // all are ok and checking interval is 5 minutes
            const user = await User.findOne({ where: { email: confirmCodeArr[0], confirmCode: req.body.confirmCode } });
            if (user) {
              await user.update({
                confirmCode: null,
                status: 1
              });
              res.status(200).send({message: req.__('email_confirmed'), type: MESSAGE_TYPES.SUCCESS});
            } else {
              res.status(200).send({message: req.__('email_confirm_user_not_found'), type: MESSAGE_TYPES.ERROR});
            }
          } else {
            res.status(200).send({message: req.__('email_confirm_invalid_confirm_code'), type: MESSAGE_TYPES.ERROR});
          }
        } else {
          res.status(200).send({message: req.__('email_confirm_invalid_confirm_code'), type: MESSAGE_TYPES.ERROR});
        }
      } else {
        res.status(200).send({message: req.__('email_confirm_invalid_confirm_code'), type: MESSAGE_TYPES.ERROR});
      }
    } else {
      res.status(200).send({message: req.__('email_confirm_invalid_confirm_code'), type: MESSAGE_TYPES.ERROR});
    }
  },
  async forgotPasswordBoolean(req, res) {
    const ThereIsResetPassword = await ResetPassword.findOne({where: {code: req.body.forgotPassword}});
    if (ThereIsResetPassword && ThereIsResetPassword.status) {
      res.status(200).send({
        userId: ThereIsResetPassword.userId,
        type: MESSAGE_TYPES.SUCCESS,
      });
    } else {
      res.status(200).send({
        type: MESSAGE_TYPES.ERROR,
      });
    }

  },
  async changeUserPassword(req, res) {
    const newPassword = req.body.newPassword;
    const confirmPassword = req.body.confirmNewPassword;
    const userId = req.body.userId;
    const code = req.body.code;

    if (!newPassword || !confirmPassword) {
      res.status(200).send({message: req.__('password_and_password_confirmation'), type: MESSAGE_TYPES.ERROR});
    } else if (newPassword !== confirmPassword) {
      res.status(200).send({message: req.__('password_and_password_confirmation_same'), type: MESSAGE_TYPES.ERROR});
    } else {
      const user = await User.findOne({ where: { id: userId } });
      const resetPassword = await ResetPassword.findOne({ where: { code: code } });

      if (user && resetPassword) {
        user.password = newPassword;
        user.password_confirmation = confirmPassword;
        user.save();

        resetPassword.status = false;
        resetPassword.save();
        res.status(200).send({message: req.__('password_changed'), type: MESSAGE_TYPES.SUCCESS, email: user.email});
        return;
      }
      res.status(200).send({message: req.__('password_changed_issue'), type: MESSAGE_TYPES.ERROR});
    }
  },
  async resendConfirmationCode(req, res) {
    if (req.body.email && MainHelper.validateEmail(req.body.email)) {
      const user = await User.findOne({where: {email: req.body.email}});
      if(user === null) res.status(200).send({message: req.__('user_doesnt_exist'), type: MESSAGE_TYPES.ERROR});

      const forEncrypt = req.body.email + '/' + Math.floor(Date.now() / 1000);
      const encryptedCode = MainHelper.encrypt(forEncrypt);
      await user.update({
        confirmCode: encryptedCode,
        status: 0
      });
      MainHelper.sendEmailConfirmation(req, user); // sending email confirmation
      res.status(200).send({message: req.__('email_confirmation_sent'), type: MESSAGE_TYPES.SUCCESS, resendConfirmEmail: true});

    } else {
      res.status(200).send({message: req.__('invalid_email'), type: MESSAGE_TYPES.ERROR});
    }
  },
  async forgotPassword(req, res) {
    const email = req.body.data;

    if (!validator.isEmail(email)) {
      res.status(200).send({
        message: req.__('email_does_not_exist'),
        type: MESSAGE_TYPES.ERROR,
      })
    }

    const user = await User.findOne({where: {email: email}});
    if (!user) {
      res.status(200).send({
        message: req.__('user_not_found'),
        type: MESSAGE_TYPES.ERROR,
      });
    }

    let resetPassword = "";
    let possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

    while (true) {
      if (resetPassword.length === 50) {
        break
      }
      resetPassword += possible.charAt(Math.floor(Math.random() * possible.length));
    }

    const ThereIsResetPassword = await ResetPassword.findOne({where: {userId: user.id}});
    if (ThereIsResetPassword && ThereIsResetPassword.status) {
      return res.status(200).send({
        message: req.__('we_have_already'),
        type: MESSAGE_TYPES.INFO,
      });
    }

    await ResetPassword
      .create({
        userId: user.id,
        code: resetPassword,
        status: true,
        expireTime: Date.now() + 300
      });

    const emailData = req.body;
    const poolConfig = {
      pool: true,
      host: process.env.SMTP_HOST,
      port: process.env.SMTP_PORT,
      secure: true, // use TLS
      auth: {
        user: process.env.SMTP_AUTH_USER,
        pass: process.env.SMTP_AUTH_PASSWORD
      }
    };

    const emailTemplate = new EmailTemplate({
      views: {
        options: {
          extension: 'ejs' // <---- HERE
        }
      }
    });
    const locales = {
      message: emailData.message,
      title: req.__('forgot_password_title'),
      hello: req.__('forgot_password_hello') + ' ' + user.firstName + ' ' + user.lastName,
      forgot_password_link: process.env.FRONTEND_URL + '/forgotPassword?code=' + resetPassword,
      forgot_password_link_text: req.__('forgot_password_link_text'),
    };

    Promise
      .all([
        emailTemplate.render('../templates/forgotPassword/html', locales),
        emailTemplate.render('../templates/forgotPassword/text', locales)
      ])
      .then(([html, text]) => {
        const mailOptions = {
          from: process.env.SENDER_EMAIL, // sender address
          to: email, // list of receivers
          subject: req.__('forgot_password_title'), // Subject line
          text: text, // plain text body
          html: html // html body
        };

        let transporter = nodeMailer.createTransport(poolConfig);
        transporter.verify(function (error, success) {
          if (error) {
            res.send({type: MESSAGE_TYPES.ERROR, success: false, info: null});
          } else {
            console.log('nodeMailer Server is ready to take our messages');
            transporter.sendMail(mailOptions, (error, info) => {
              if (error) {
                res.send({type: MESSAGE_TYPES.ERROR, success: false, info: info});
              }
              res.send({type: MESSAGE_TYPES.SUCCESS, message: req.__('check_your_email'), success: success, info: info});
            });
          }
        });
      })
      .catch(console.error);
  },
  async getUserIpLocation(req, res) {
    try {
      const _ipAddress = req.query && req.query.ipAddress
      if (_ipAddress) {
        const ipLocation = await IpLocation.findOne({
          where: {
            'ip': _ipAddress
          }
        });
        let ipLocationData = {};
        if (ipLocation) {
          ipLocationData = ipLocation;
        } else {
          ipLocationData = await ip_location(_ipAddress);
          if (ipLocationData) {
            await IpLocation
              .create({
                ip: _ipAddress,
                langCode: ipLocationData.countryCode === 'AM' ? 'hy' : 'en',
                country: ipLocationData.country,
                countryCode: ipLocationData.countryCode,
                region: ipLocationData.region,
                regionCode: ipLocationData.regionCode,
                city: ipLocationData.city,
                postal: ipLocationData.postal,
                latitude: ipLocationData.latitude,
                longitude: ipLocationData.longitude,
                timezone: ipLocationData.timezone
              })
          }
        }
        res.status(200).send({message: req.__('user_location'), type: MESSAGE_TYPES.SUCCESS, ipLocation: ipLocationData});
      } else {
        res.status(200).send({message: req.__('something_wrong'), type: MESSAGE_TYPES.ERROR});
      }
    }catch (e) {
      res.status(200).send({message: req.__('something_wrong'), type: MESSAGE_TYPES.ERROR});
    }
  },
};