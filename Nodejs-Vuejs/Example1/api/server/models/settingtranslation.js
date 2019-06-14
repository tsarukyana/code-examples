'use strict';
module.exports = (sequelize, DataTypes) => {
  const SettingTranslation = sequelize.define('SettingTranslation', {
    value: DataTypes.STRING
  }, {});
  SettingTranslation.associate = function(models) {
    // associations can be defined here
    SettingTranslation.belongsTo(models.Language, { foreignKey: 'languageId', onUpdate: 'CASCADE', onDelete: 'SET NULL' });
    SettingTranslation.belongsTo(models.Setting, { foreignKey: 'settingId', onUpdate: 'CASCADE', onDelete: 'CASCADE' });
  };
  return SettingTranslation;
};
