// Load dependencies
const _ = require('lodash');
const fs = require('fs');
const path = require('path');
const Jimp = require('jimp');
const crypto = require('crypto');
const mkdirp = require('mkdirp');
const concat = require('concat-stream');
const streamifier = require('streamifier');
const IMAGE_SIZES = require('../config/constants').IMAGE_SIZES;

// Configure UPLOAD_PATH
// process.env.IMAGE_STORAGE contains uploads/avatars
const UPLOAD_PATH = path.resolve(__dirname, '../..', process.env.IMAGE_STORAGE);

// create a multer storage engine
const ImageStorage = function(options) {

  // this serves as a constructor
  function ImageStorage(opts) {

    const baseUrl = process.env.IMAGE_BASE_URL;

    const allowedStorageSystems = ['local'];
    const allowedOutputFormats = ['jpg', 'png', 'gif'];

    // fallback for the options
    const defaultOptions = {
      storage: 'local',
      output: 'png',
      greyscale: false,
      quality: 100,
      square: false,
      threshold: 0,
      responsive: true,
      path_identifier: ''
    };

    // extend default options with passed options
    let options = (opts && _.isObject(opts)) ? _.pick(opts, _.keys(defaultOptions)) : {};
    options = _.extend(defaultOptions, options);

    // check the options for correct values and use fallback value where necessary
    this.options = _.forIn(options, function(value, key, object) {

      switch (key) {

        case 'square':
        case 'greyscale':
        case 'responsive':
          object[key] = _.isBoolean(value) ? value : defaultOptions[key];
          break;

        case 'storage':
          value = String(value).toLowerCase();
          object[key] = _.includes(allowedStorageSystems, value) ? value : defaultOptions[key];
          break;

        case 'output':
          value = String(value).toLowerCase();
          object[key] = _.includes(allowedOutputFormats, value) ? value : defaultOptions[key];
          break;

        case 'quality':
          value = _.isFinite(value) ? value : Number(value);
          object[key] = (value && value >= 0 && value <= 100) ? value : defaultOptions[key];
          break;

        case 'threshold':
          value = _.isFinite(value) ? value : Number(value);
          object[key] = (value && value >= 0) ? value : defaultOptions[key];
          break;

      }

    });

    // set the upload path
    this.uploadPath = this.options.responsive ? path.join(UPLOAD_PATH, this.options.path_identifier) : UPLOAD_PATH;

    // set the upload base url
    this.uploadBaseUrl = this.options.responsive ? path.join(baseUrl, this.options.path_identifier) : baseUrl;

    if (this.options.storage === 'local') {
      // if upload path does not exist, create the upload path structure
      !fs.existsSync(this.uploadPath) && mkdirp.sync(this.uploadPath);
    }

  }

  // this generates a random cryptographic filename
  ImageStorage.prototype._generateRandomFilename = function() {
    // create pseudo random bytes
    var bytes = crypto.pseudoRandomBytes(32);

    // create the md5 hash of the random bytes
    var checksum = crypto.createHash('MD5').update(bytes).digest('hex');

    // return as filename the hash with the output extension
    return checksum + '.' + this.options.output;
  };

  // this creates a Writable stream for a filepath
  ImageStorage.prototype._createOutputStream = function(filepath, cb) {

    // create a reference for this to use in local functions
    var that = this;

    // create a writable stream from the filepath
    var output = fs.createWriteStream(filepath);

    // set callback fn as handler for the error event
    output.on('error', cb);

    // set handler for the finish event
    output.on('finish', function() {
      cb(null, {
        destination: that.uploadPath,
        baseUrl: that.uploadBaseUrl,
        filename: path.basename(filepath),
        storage: that.options.storage,
        generatedRandomFilename: that.generatedRandomFilename,
        fileExtention: that.options.output,
      });
    });

    // return the output stream
    return output;
  };

  // this processes the Jimp image buffer
  ImageStorage.prototype._processImage = function(image, cb) {

    // create a reference for this to use in local functions
    const that = this;

    let batch = [];

    // the responsive sizes
    const sizes = IMAGE_SIZES;

    const filename = this._generateRandomFilename();
    this.generatedRandomFilename = filename.split('.')[0];

    let mime = Jimp.MIME_PNG;

    // create a clone of the Jimp image
    let clone = image.clone();
    const cloneOriginal = image.clone();

    // fetch the Jimp image dimensions
    const width = clone.bitmap.width;
    const height = clone.bitmap.height;
    let square = Math.min(width, height);
    const threshold = this.options.threshold;

    // resolve the Jimp output mime type
    switch (this.options.output) {
      case 'jpg':
        mime = Jimp.MIME_JPEG;
        break;
      case 'gif':
        mime = Jimp.MIME_GIF;
        break;
      case 'png':
      default:
        mime = Jimp.MIME_PNG;
        break;
    }

    // auto scale the image dimensions to fit the threshold requirement
    if (threshold && square > threshold) {
      clone = (square === width) ? clone.resize(threshold, Jimp.AUTO) : clone.resize(Jimp.AUTO, threshold);
    }

    // crop the image to a square if enabled
    if (this.options.square) {

      if (threshold) {
        square = Math.min(square, threshold);
      }

      // fetch the new image dimensions and crop
      clone = clone.crop((clone.bitmap.width - square) / 2, (clone.bitmap.height - square) / 2, square, square);
    }

    // convert the image to greyscale if enabled
    if (this.options.greyscale) {
      clone = clone.greyscale();
    }

    // set the image output quality
    clone = clone.quality(this.options.quality);

    if (this.options.responsive) {

      // map through  the responsive sizes and push them to the batch
      batch = _.map(sizes, function(size) {

        let outputStream;

        let image = null;
        let filepath = filename.split('.');

        // create the complete filepath and create a writable stream for it
        filepath = filepath[0] + '_' + size + '.' + filepath[1];
        filepath = path.join(that.uploadPath, filepath);
        outputStream = that._createOutputStream(filepath, cb);

        // scale the image based on the size
        switch (size) {
          case 'sm':
            image = clone.clone().scale(0.3);
            break;
          case 'md':
            image = clone.clone().scale(0.7);
            break;
          case 'lg':
            image = clone.clone();
            break;
          case 'original':
            image = cloneOriginal;
            break;
        }

        // return an object of the stream and the Jimp image
        return {
          stream: outputStream,
          image: image
        };
      });

    } else {

      // push an object of the writable stream and Jimp image to the batch
      batch.push({
        stream: that._createOutputStream(path.join(that.uploadPath, filename), cb),
        image: clone
      });

    }

    // process the batch sequence
    _.each(batch, function(current) {
      // get the buffer of the Jimp image using the output mime type
      current.image.getBuffer(mime, function(err, buffer) {
        if (that.options.storage === 'local') {
          // create a read stream from the buffer and pipe it to the output stream
          streamifier.createReadStream(buffer).pipe(current.stream);
        }
      });
    });

  };

  // multer requires this for handling the uploaded file
  ImageStorage.prototype._handleFile = function(req, file, cb) {

    // create a reference for this to use in local functions
    const that = this;

    // create a writable stream using concat-stream that will
    // concatenate all the buffers written to it and pass the
    // complete buffer to a callback fn
    const fileManipulate = concat(function(imageData) {

      // read the image buffer with Jimp
      // it returns a promise
      Jimp.read(imageData)
        .then(function(image) {
          // process the Jimp image buffer
          that._processImage(image, cb);
        })
        .catch(cb);
    });

    // write the uploaded file buffer to the fileManipulate stream
    file.stream.pipe(fileManipulate);

  };

  // multer requires this for destroying file
  ImageStorage.prototype._removeFile = function(req, file, cb) {

    let matches, pathsplit;
    const filename = file.filename;
    const _path = path.join(this.uploadPath, filename);
    let paths = [];

    // delete the file properties
    delete file.filename;
    delete file.destination;
    delete file.baseUrl;
    delete file.storage;

    // create paths for responsive images
    if (this.options.responsive) {
      pathsplit = _path.split('/');
      matches = pathsplit.pop().match(/^(.+?)_.+?\.(.+)$/i);

      if (matches) {
        paths = _.map(IMAGE_SIZES, function(size) {
          return pathsplit.join('/') + '/' + (matches[1] + '_' + size + '.' + matches[2]);
        });
      }
    } else {
      paths = [_path];
    }

    // delete the files from the filesystem
    _.each(paths, function(_path) {
      fs.unlink(_path, cb);
    });

  };

  // create a new instance with the passed options and return it
  return new ImageStorage(options);

};

// export the storage engine
module.exports = ImageStorage;
