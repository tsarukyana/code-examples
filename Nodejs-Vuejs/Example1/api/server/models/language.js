'use strict';
module.exports = (sequelize, DataTypes) => {
  const Language = sequelize.define('Language', {
    code: DataTypes.STRING,
    name: DataTypes.STRING
  }, {});
  Language.associate = function(models) {
    // associations can be defined here
  };
  return Language;
};
