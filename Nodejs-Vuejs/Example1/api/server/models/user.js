'use strict';
const bcrypt = require("bcrypt");
const _lodash = require("lodash");
const {MainHelper} = require("../helpers");
const i18n = require("i18n");

module.exports = (sequelize, DataTypes) => {
    const User = sequelize.define('User', {
        firstName: {
            type: DataTypes.STRING,
            allowNull: true,
            defaultValue: null,
            validate: {max: 255}
        },
        lastName: {
            type: DataTypes.STRING,
            allowNull: true,
            defaultValue: null,
            validate: {max: 255}
        },
        email: {
            type: DataTypes.STRING,
            allowNull: false,
            unique: {
                args: true,
                msg: 'Oops. Looks like you already have an account with this email address.',
                fields: [sequelize.fn('lower', sequelize.col('email'))]
            },
            validate: {
                isEmail: {
                    args: true,
                    msg: 'The email you entered is invalid or is already in our system.'
                },
                max: {
                    args: 254,
                    msg: 'The email you entered is invalid or longer than 254 characters.'
                }
            }
        },
        image: {
            type: DataTypes.STRING,
            allowNull: true,
        },
        passwordDigest: {
            type: DataTypes.STRING,
            validate: {
                notEmpty: true
            }
        },
        password: {
            type: DataTypes.VIRTUAL
        },
        password_confirmation: {
            type: DataTypes.VIRTUAL
        },
        about: {
            type: DataTypes.STRING,
            allowNull: true,

        },
        phoneNumber: {
            type:DataTypes.STRING,
            allowNull: true,

        },
        dateBirthday: {
            type:DataTypes.STRING,
            allowNull: true,
        },
        facebookId:{
            type:DataTypes.STRING,
            allowNull: true,
        },
        googleId:{
          type:DataTypes.STRING,
          allowNull: true,
        },
        timeZone: {
            type: DataTypes.STRING,
            allowNull: false,
            defaultValue: 'UTC',
        },
        confirmCode:{
          type:DataTypes.STRING,
          allowNull: true,
        },
        status:{
          type:DataTypes.INTEGER,
          allowNull: false,
          defaultValue: 0,
        },
    }, {
        indexes: [{unique: true, fields: ['email']}],
    });
    User.associate = function (models) {
        // associations can be defined here
        User.belongsTo(models.Language, { foreignKey: 'languageId', onUpdate: 'CASCADE', onDelete: 'SET NULL' });
        User.belongsToMany(models.Role, {as: 'roles', through: 'UserRole', foreignKey: 'userId', otherKey: 'roleId'});
    };

    let hasSecurePassword = function (user, options, action) {
      return new Promise((resolve, reject) => {
          if (action === 'creating') {
              if (!user.password || !user.password_confirmation) {
                  reject(new Error(i18n.__('password_required')))
              }

              if (user.password !== user.password_confirmation) {
                  reject(new Error(i18n.__('password_doesnt_match')))
              }
              bcrypt.hash(user.get('password'), 10, function (err, hash) {
                  if (err) return err
                  user.passwordDigest = hash
                  resolve(user)
              })
          } else if (action === 'updating') {
              if (user.get('password')) {
                  if (!user.password || !user.password_confirmation) {
                      reject(new Error(i18n.__('password_required')))
                  }

                  if (user.password !== user.password_confirmation) {
                      reject(new Error(i18n.__('password_doesnt_match')))
                  }
                  bcrypt.hash(user.get('password'), 10, function (err, hash) {
                      if (err) return err
                      user.passwordDigest = hash
                      resolve(user)
                  })
              } else {
                  resolve(user)
              }
          } else {
              reject(new Error(i18n.__('something_wrong')))
          }
        });
    };

    User.beforeCreate((user, options) => {
        user.email = user.email.toLowerCase();
      return hasSecurePassword(user, options, 'creating').then(user => {
            console.log('created new user');
        }).catch((err) => {
         throw new Error(err.message);
      });
    });

    User.beforeUpdate(function (user, options) {
        user.email = user.email.toLowerCase();
        return hasSecurePassword(user, options, 'updating').then(user => {
            console.log('updated user');
        }).catch((err) => {
          throw new Error(err.message);
        });
    });

    User.addHook('beforeValidate', (user, options) => {
        let errors = [];
        if (_lodash.isEmpty(user.email)) {
            errors.push({message_code: 'email_cannot_be_empty', message: i18n.__("email_cannot_be_empty")});
        }else if (!MainHelper.validateEmail(user.email)) {
            errors.push({message_code: 'invalid_email', message: i18n.__("invalid_email")});
        }
        if (errors.length > 0) {
            const validationError = new Error();
            validationError.errors = errors;
            return sequelize.Promise.reject(validationError);
        }
    });

    return User;
};
