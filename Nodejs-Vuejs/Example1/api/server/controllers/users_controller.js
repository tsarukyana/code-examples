const User = require('../models').User;
const UserFriend = require('../models').UserFriend;
const Product = require('../models').Product;
const ProductRequest = require('../models').ProductRequest;
const bcrypt = require("bcrypt");
const {MESSAGE_TYPES, ROLE_TYPES} = require('../config/constants');

module.exports = {

  list(req, res) {
    return User
      .findAll({})
      .then(users => res.status(200).send(users))
      .catch(error => res.status(400).send(error));
  },
  retrieve(req, res) {
    return User
      .findByPk(req.params.userId)
      .then(user => {
        if (!user) {
          return res.status(404).send({
            message: 'User Not Found',
          });
        }
        let result ={
          email:user.email,
          firstName:user.firstName,
          lastName: user.lastName,
          image:user.image,
          languageId : user.languageId,
          id: user.id,
          phoneNumber:user.phoneNumber,
          dateBirthday:user.dateBirthday
        };

        return res.status(200).send(result);
      })
      .catch(error => res.status(400).send(error));
  },
  update(req, res) {
    return User
      .findByPk(req.params.userId)
      .then(user => {
        if (!user) {
          return res.status(404).send({
            message: 'User Not Found',
          });
        }
        let password = ''
        let passwordConfirmation = ''
        if (req.body.newPass && req.body.newPassConf && req.body.newPass === req.body.newPassConf) {
          if (req.body.oldPass) {
            if (bcrypt.compareSync(req.body.oldPass, user.passwordDigest)) {
              password = req.body.newPass
              passwordConfirmation = req.body.newPassConf
            } else {
              res.status(200).send({message: req.__('old_password_doesnt_match'), type: MESSAGE_TYPES.ERROR})
              return;
            }
          } else {
            res.status(200).send({message: req.__('old_password_required'), type: MESSAGE_TYPES.ERROR})
            return;
          }
        }

        return user
          .update({
            firstName: req.body.firstName || user.firstName,
            lastName: req.body.lastName || user.lastName,
            email: req.body.email || user.email,
            image: req.body.image || user.image,
            password: password || user.password,
            password_confirmation: passwordConfirmation || user.password_confirmation,
            phoneNumber:req.body.phoneNumber || user.phoneNumber,
            dateBirthday:req.body.dateBirthday || user.dateBirthday,
            about:req.body.about || user.about
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
              about:user.about
            };
            res.status(200).send({message: req.__('user_updated'), type: MESSAGE_TYPES.SUCCESS, user: result})
          })  // Send back the updated user.
          .catch((error) => res.status(400).send(error));
      })
      .catch((error) => res.status(400).send(error));
  },
  destroy(req, res) {
    return User
      .findByPk(req.params.userId)
      .then(user => {
        if (!user) {
          return res.status(400).send({
            message: 'User Not Found',
          });
        }
        return user
          .destroy()
          .then(() => res.status(200).send({message: 'User deleted successfully.'}))
          .catch(error => res.status(400).send(error));
      })
      .catch(error => res.status(400).send(error));
  },
  async accountInfo(req, res) {
    try{
      const loggedUser = req.user;

      let canGetEmailAndPhone = false;
      let _isFriend = false;
      let isRequestedToAnyProductProduct = false;

      let attr = ['id', 'firstName', 'lastName', 'image', 'about', 'status'];

      // checking loggedUser isAdmin
      const roles = req.user._permissions; // here we have all roles for logged user
      if (roles && roles.includes(ROLE_TYPES.ADMIN)) {
        canGetEmailAndPhone = true;
      }

      if (!canGetEmailAndPhone) {
        // checking the user is friend
        const isFriend = await UserFriend.isFriend(loggedUser.id, req.params.userId);
        if (isFriend) {
          canGetEmailAndPhone = true;
          _isFriend = true;
        }

        if (!canGetEmailAndPhone) {
          // checking user requested any products
          const productIds  = await Product.getProductIdsByUserId(loggedUser.id);
          isRequestedToAnyProductProduct  = await ProductRequest.isUserRequestedToAnyProduct(req.params.userId, productIds);
          if (isRequestedToAnyProductProduct) {
            canGetEmailAndPhone = true;
          }
        }
      }

      if (canGetEmailAndPhone || loggedUser.id === parseInt(req.params.userId)) {
        attr = [...attr, 'email', 'phoneNumber'];
      }

      const user = await User.findOne({
        where: {
          id: req.params.userId
        },
        attributes: attr
      });

      if (!user) {
        return res.status(200).send({message: req.__('user_not_found'), type: MESSAGE_TYPES.ERROR});
      }

      const owner = !!(loggedUser.id === req.params.userId);

      return res.status(200).send({user, isFriend: _isFriend, owner, message: req.__('account_info'), type: MESSAGE_TYPES.SUCCESS});

    }catch (e) {
      return res.status(200).send({message: req.__('something_wrong'), type: MESSAGE_TYPES.ERROR});
    }
  },
};
