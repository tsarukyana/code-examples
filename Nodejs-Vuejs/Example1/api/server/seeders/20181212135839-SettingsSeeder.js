'use strict';

module.exports = {
  up: (queryInterface, Sequelize) => {
    const nowTime = Sequelize.literal('NOW()');
    const settings = [
      { name:'goal', createdAt: nowTime, updatedAt: nowTime},
      { name:'about_us', createdAt: nowTime, updatedAt: nowTime},
      { name:'general_description', createdAt: nowTime, updatedAt: nowTime},
      { name:'contact_us', createdAt: nowTime, updatedAt: nowTime},
      { name:'contact_us_description', createdAt: nowTime, updatedAt: nowTime},
      { name:'site_info', createdAt: nowTime, updatedAt: nowTime},
      { name:'site_info_description', createdAt: nowTime, updatedAt: nowTime},
    ];
    return queryInterface.bulkInsert('Settings', settings, {}).catch(err => console.log('err', err));
  },

  down: (queryInterface, Sequelize) => {

  }
};
