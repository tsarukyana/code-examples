'use strict';

module.exports = {
  up: (queryInterface, Sequelize) => {
    /*
      Add altering commands here.
      Return a promise to correctly handle asynchronicity.

      Example:
      return queryInterface.bulkInsert('Person', [{
        name: 'John Doe',
        isBetaMember: false
      }], {});
    */
    const nowTime = Sequelize.literal('NOW()');
    const languages = [
        {code: 'en', name: 'English', createdAt: nowTime, updatedAt: nowTime},
        {code: 'hy', name: 'Հայերեն', createdAt: nowTime, updatedAt: nowTime}
    ];
    return queryInterface.bulkInsert('Languages', languages, {}).catch(err => console.log('err', err));
  },

  down: (queryInterface, Sequelize) => {
    /*
      Add reverting commands here.
      Return a promise to correctly handle asynchronicity.

      Example:
      return queryInterface.bulkDelete('Person', null, {});
    */
    return queryInterface.bulkDelete('Languages', null, {});
  }
};
