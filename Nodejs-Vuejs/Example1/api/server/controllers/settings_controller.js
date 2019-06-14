const Setting = require('../models').Setting;
const SettingTranslation = require('../models').SettingTranslation;
const MESSAGE_TYPES = require('../config/constants').MESSAGE_TYPES;

module.exports = {
  list: async function (req, res) {
    const settings = await Setting.findAll({
      include: [{
        model: SettingTranslation,
        attributes: ['value', 'languageId']
      }]
    });
    if (settings) {
      res.status(200).send({message: req.__('get_settings'), settings: settings})
    } else {
      res.status(200).send({message: req.__('get_settings_issue'), type: MESSAGE_TYPES.ERROR})
    }
  },
  async save (req, res) {
    if (req.body.data && req.body.data.length > 0) {

      for (const item of req.body.data) { // here we used for correct working with async/await
        await SettingTranslation.update(
          { value: item.value }, /* set attributes' value */
          { where: { settingId: item.id, languageId: item.languageId }} /* where criteria */
        );
      }

      const settings = await Setting.findAll({
        include: [{
          model: SettingTranslation,
          attributes: ['value', 'languageId']
        }]
      });
      if (settings) {
        res.status(200).send({message: req.__('settings_saved'), settings: settings, type: MESSAGE_TYPES.SUCCESS})
      } else {
        res.status(200).send({message: req.__('get_settings_issue'), type: MESSAGE_TYPES.ERROR})
      }
    }
    res.status(200).send({message: req.__('something_wrong'), type: MESSAGE_TYPES.ERROR})
  },
};
