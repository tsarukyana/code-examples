'use strict';
module.exports = {
    up: (queryInterface, Sequelize) =>{
        return queryInterface.createTable('Users', {
            id: {
                allowNull: false,
                autoIncrement: true,
                primaryKey: true,
                type: Sequelize.INTEGER
            },
            firstName: {
                type: Sequelize.STRING
            },
            lastName: {
                type: Sequelize.STRING
            },
            email: {
                allowNull: false,
                unique: true,
                type: Sequelize.STRING
            },
            image: {
                allowNull: true,
                type: Sequelize.STRING
            },
            passwordDigest: {
                allowNull: false,
                type: Sequelize.STRING
            },
            timeZone: {
                allowNull: false,
                type: Sequelize.STRING,
                defaultValue: 'UTC',
            },
            languageId: {
                allowNull: true,
                type: Sequelize.INTEGER
            },
            about: {
                type: Sequelize.STRING,
                allowNull: true,
            },
            phoneNumber: {
                type:Sequelize.STRING,
                allowNull: true,
            },
            dateBirthday: {
                type:Sequelize.STRING,
                allowNull: true,
            },
            facebookId:{
                type:Sequelize.STRING,
                allowNull: true,
            },
            googleId:{
                type:Sequelize.STRING,
                allowNull: true,
            },
            confirmCode:{
              type:Sequelize.STRING,
              allowNull: true,
            },
            status:{
              type:Sequelize.INTEGER,
              allowNull: false,
              defaultValue: 0,
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
            // console.log('res', res);
            queryInterface.sequelize.query('ALTER TABLE "Users" ADD CONSTRAINT "Users_languageId_fkey" FOREIGN KEY ("languageId") REFERENCES "Languages" ("id") MATCH SIMPLE ON UPDATE CASCADE ON DELETE SET NULL;')
                .then(() => {
                    // console.log('res2', res2);
                })
                .catch(err2 => {
                    console.log('err2', err2);
                });
        }).catch(err => {
            console.log('Users createTable Error: ', err);
        });
    },

    down: (queryInterface/* , Sequelize */) => {
        // const dropLanguageIdFKSQL = queryInterface.QueryGenerator.dropForeignKeyQuery("Users", "Users_languagId_fkey");
        // queryInterface.sequelize.query(dropLanguageIdFKSQL);
        return queryInterface.dropTable('Users');
    },
};
