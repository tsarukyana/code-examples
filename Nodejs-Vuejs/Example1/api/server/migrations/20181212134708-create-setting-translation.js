'use strict';
module.exports = {
  up: (queryInterface, Sequelize) => {
    return queryInterface.createTable('SettingTranslations', {
      id: {
        allowNull: false,
        autoIncrement: true,
        primaryKey: true,
        type: Sequelize.INTEGER
      },
      settingId: {
        type: Sequelize.INTEGER
      },
      languageId: {
        type: Sequelize.INTEGER
      },
      value: {
        type: Sequelize.STRING
      },
      createdAt: {
        allowNull: false,
        type: Sequelize.DATE
      },
      updatedAt: {
        allowNull: false,
        type: Sequelize.DATE
      }
    }).then(() => {

      queryInterface.sequelize.query('ALTER TABLE "SettingTranslations" ADD CONSTRAINT "SettingTranslations_settingId_fkey" FOREIGN KEY ("settingId") REFERENCES "Settings" ("id") MATCH SIMPLE ON UPDATE CASCADE ON DELETE CASCADE;')
        .then(() => {

        })
        .catch(err => {
          console.log('SettingTranslations_settingId_fkey Error: ', err);
        });
      queryInterface.sequelize.query('ALTER TABLE "SettingTranslations" ADD CONSTRAINT "SettingTranslations_languageId_fkey" FOREIGN KEY ("languageId") REFERENCES "Languages" ("id") MATCH SIMPLE ON UPDATE CASCADE ON DELETE SET NULL;')
        .then(() => {

        })
        .catch(err => {
          console.log('SettingTranslations_languageId_fkey Error: ', err);
        });
    }).catch(err => {
      console.log('SettingTranslations createTable Error: ', err);
    });
  },
  down: (queryInterface/*, Sequelize*/) => {
    return queryInterface.dropTable('SettingTranslations');
  }
};
