const multer = require('multer');
const { FILE_LIMITS } = require('../config/constants');
const { ImageStorage, MainHelper } = require('../helpers');

module.exports = {
  uploadProfilePicture (req, res) {
    let user = req.user; // this comes from jwt strategy
    const userOldImage = req.user.image;

    // setup a new instance of the ImageStorage engine
    const storage = ImageStorage({
      path_identifier: user.id.toString()
    });
    // setup multer
    const upload = multer({
      storage: storage,
      limits: FILE_LIMITS,
      fileFilter: MainHelper.imageFilter
    }).single(process.env.IMAGE_FIELD)

    upload(req, res, function (err) {
      if (err) {
        return res.send(err);
      }
      const img = '/' + req.user.id.toString() + '/' +req.file.generatedRandomFilename + '_original.' + req.file.fileExtention;

      return user
        .update({
          image: img
        }).then(us => {
          if (userOldImage) {
            MainHelper.removeFileByPath(userOldImage); // removing old images
          }
          res.send(us)
        })
        .catch(e => {
          res.send(e)
        })
    });
  },
  userInfo(req, res) {
    req.user.passwordDigest = null;
    res.status(200).send({user: req.user}); // req.user comes from jwt strategy
  },
}
