const JWT_STRATEGIES = require('../config/constants').JWT_STRATEGIES;
const authController = require('../controllers').authController;
const usersController = require('../controllers').usersController;
const roleController = require('../controllers').roleController;
const profileController = require('../controllers').profileController;
const productsController =require('../controllers').productsController;
const categoriesController =require('../controllers').categoriesController;
const friendsController = require('../controllers').friendsController;
const languagesController = require('../controllers').languagesController;
const notificationController = require('../controllers').notificationController;
const settingsController = require('../controllers').settingsController;
module.exports = (app, passport) => {
    app.get('/api', (req, res) => res.status(200).send({
      message: 'Welcome to the Giver API!',
    }));

    // ROUTES FOR USERS CREATE, GET, PUT, DELETE
    app.get('/api/users', usersController.list);
    app.get('/api/users/:userId', usersController.retrieve);
    app.put('/api/users/:userId', usersController.update);
    app.delete('/api/users/:userId', usersController.destroy);
    app.get('/api/users/accountInfo/:userId', passport.authenticate(JWT_STRATEGIES.CHECK_ROLES, { session: false }), usersController.accountInfo);

    // AUTH WITH COMMON, GOOGLE AND FACEBOOK
    app.post('/api/auth/signUp', authController.signUp);
    app.post('/api/auth/signIn', authController.signIn);
    app.post('/api/auth/logout', authController.logOut);
    app.post('/api/user/forgotPassword', authController.forgotPassword);
    app.post('/api/user/forgotPasswordBoolean', authController.forgotPasswordBoolean);
    app.post('/api/user/changeUserPassword', authController.changeUserPassword);
    app.post('/api/auth/confirmEmail', authController.confirmEmail);
    app.post('/api/auth/resendConfirmationCode', authController.resendConfirmationCode);
    app.get('/api/auth/getUserIpLocation', authController.getUserIpLocation);

    app.get('/api/auth/facebook', passport.authenticate('facebook', {session: false, scope: ["email"]}));
    app.get('/api/auth/facebook/callback', passport.authenticate('facebook', { session: false, scope: ["email"],failureRedirect: process.env.FRONTEND_URL/*, successRedirect: '/', failureRedirect: '/login'*/ }), authController.authFacebookCallback);

    app.get('/api/auth/google',
      passport.authenticate('google',
        { scope: [
          'https://www.googleapis.com/auth/plus.login',
            'https://www.googleapis.com/auth/plus.me',
            'https://www.googleapis.com/auth/userinfo.email',
          ] }));
    app.get('/api/auth/google/callback',passport.authenticate('google',
      { session: false,
        scope: ["https://www.googleapis.com/auth/plus.me"],
        failureRedirect: process.env.FRONTEND_URL/*, successRedirect: '/', failureRedirect: '/login'*/ }),
      authController.authGoogleCallback);

    // Profile
    app.post("/api/profile/upload", passport.authenticate(JWT_STRATEGIES.CHECK_ROLES, { session: false }), profileController.uploadProfilePicture);
    app.get("/api/profile/userInfo", passport.authenticate(JWT_STRATEGIES.CHECK_ROLES, { session: false }), profileController.userInfo);

    //Friends
    app.get("/api/user/friends", passport.authenticate(JWT_STRATEGIES.CHECK_ROLES, { session: false }), friendsController.retrieveAllFriendsByUserId);
    app.post("/api/user/acceptInvitation", passport.authenticate(JWT_STRATEGIES.CHECK_ROLES, { session: false }), friendsController.acceptInvitation);
    app.post("/api/get_individual_users", passport.authenticate(JWT_STRATEGIES.CHECK_ROLES, { session: false }), friendsController.getSearchIndividualUsers);

  //Send email
    app.post('/api/email/sendInvitation',friendsController.sendFriendInvitationMail)
  // ROUTES FOR ROLES
    // route for geting all role groups
    app.get('/api/roles',roleController.list)
    app.get('/api/registrationRoles',roleController.registrationRoles)

    // ROUTES FOR PRODUCTS CREATE, GET, PUT, DELETE
    app.post('/api/product/save', passport.authenticate(JWT_STRATEGIES.CHECK_ROLES, { session: false }), productsController.save);
    app.get('/api/product/getAll', passport.authenticate(JWT_STRATEGIES.CHECK_ROLES, { session: false }), productsController.getAll);
    app.get('/api/product/getProduct/:productId', productsController.getProduct);
    app.get('/api/product/getProductWithRequests/:productId', passport.authenticate(JWT_STRATEGIES.CHECK_ROLES, { session: false }), productsController.getProductWithRequests);
    app.post('/api/product/acceptRequest', passport.authenticate(JWT_STRATEGIES.CHECK_ROLES, { session: false }), productsController.acceptRequest);
    app.delete('/api/product/delete/:productId', passport.authenticate(JWT_STRATEGIES.CHECK_ROLES, { session: false }), productsController.delete);
    app.post('/api/product/uploadImage/:productId', passport.authenticate(JWT_STRATEGIES.CHECK_ROLES, { session: false }), productsController.uploadProductImage);
    app.get('/api/product/getAllCategories', passport.authenticate(JWT_STRATEGIES.CHECK_ROLES, { session: false }), productsController.getAllCategories);
    app.post('/api/product/saveProductRequest', passport.authenticate(JWT_STRATEGIES.CHECK_ROLES, { session: false }), productsController.saveProductRequest);

    app.get('/api/products', productsController.getAllProducts);
    app.get('/api/products/listWithFriends', passport.authenticate(JWT_STRATEGIES.CHECK_ROLES, { session: false }), productsController.getAllProducts);

    // ROUTES FOR CATEGORIES
    app.get('/api/categories/listWithSubCategories', categoriesController.listWithSubCategories);
    app.post('/api/categories/getOtherCategories', categoriesController.getOtherCategories);
    app.post('/api/categories/add',  passport.authenticate(JWT_STRATEGIES.CHECK_ROLES, { session: false }), categoriesController.add);
    app.put('/api/categories/edit/:categoryId',  passport.authenticate(JWT_STRATEGIES.CHECK_ROLES, { session: false }), categoriesController.edit);
    app.delete('/api/categories/delete/:categoryId',  passport.authenticate(JWT_STRATEGIES.CHECK_ROLES, { session: false }), categoriesController.delete);
    app.post('/api/categories/changeStatus',  passport.authenticate(JWT_STRATEGIES.CHECK_ROLES, { session: false }), categoriesController.changeStatus);

    // ROUTES FOR LANGUAGES
    app.get('/api/languages/list', languagesController.list);

    // ROUTES FOR NOTIFICATIONS
    app.get('/api/notification/userNotifications', passport.authenticate(JWT_STRATEGIES.CHECK_ROLES, { session: false }), notificationController.userNotifications);
    app.get('/api/notification/hideUserNotificationsCount', passport.authenticate(JWT_STRATEGIES.CHECK_ROLES, { session: false }), notificationController.hideUserNotificationsCount);
    app.delete('/api/notification/delete/:notificationId', passport.authenticate(JWT_STRATEGIES.CHECK_ROLES, { session: false }), notificationController.delete);
    app.post('/api/notification/contactUs', notificationController.contactUs);

  // ROUTES FOR SETTINGS
    app.get('/api/settings/list', settingsController.list);
    app.post('/api/settings/save', passport.authenticate(JWT_STRATEGIES.CHECK_ROLES, { session: false }), settingsController.save);
};
