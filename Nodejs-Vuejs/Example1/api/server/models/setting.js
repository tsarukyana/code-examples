'use strict';
module.exports = (sequelize, DataTypes) => {
  const Setting = sequelize.define('Setting', {
    name: DataTypes.STRING
  }, {});
  Setting.associate = function(models) {
    // associations can be defined here
    Setting.hasMany(models.SettingTranslation, {foreignKey: 'settingId'});
  };
  return Setting;
};
