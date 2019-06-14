'use strict';
const Language = require('../models').Language;
const Setting = require('../models').Setting;

module.exports = {
  up: (queryInterface, Sequelize) => {
    const nowTime = Sequelize.literal('NOW()');

    const languagePromise =  new Promise((resolve, reject) => {
      Language.findAll({})
        .then(items => resolve(items))
        .catch(error => reject(error));
    });
    const settingPromise = new Promise((resolve, reject) => {
      Setting.findAll({}).then(items => resolve(items)).catch(error => reject(error));
    });

    let settingTranslations = [];
    const settingValues = [
      ['Goal', 'Կարգախոս'],
      ['About Giver text here', 'Կայքի մասին տեքստ'],
      ['General description here', 'Ընդհանուր տեղեկություն կայքի մասին'],
      ['Contact us', 'Կապնվել մեզ հետ'],
      ['Contact us description', 'Կապնվել մեզ հետ նկարագրություն'],
      ['Site info', 'Կայքի մասին'],
      ['Site info description', 'Կայքի մասին նկարագրություն'],
    ];
    return Promise.all([languagePromise, settingPromise]).then(values => {
      const languages = values[0];
      const settings = values[1];
      settings.forEach((settingItem, settingIndex) => {
        languages.forEach((lang, langIndex) => {
          settingTranslations.push({settingId: settingItem.dataValues.id, languageId: lang.dataValues.id, value: settingValues[settingIndex][langIndex], createdAt: nowTime, updatedAt: nowTime});
        });
      });
      if (settingTranslations.length > 0) {
        return queryInterface.bulkInsert('SettingTranslations', settingTranslations, {}).catch(error => console.log(error));
      }
    }, reason => {
      console.log('reason', reason);
    });
  },

  down: (queryInterface, Sequelize) => {
    return queryInterface.bulkDelete('SettingTranslations', null, {});
  }
};
