import axios from 'axios'

export const getLanguage = () => {
  let _lang = localStorage.getItem('lang');
  if (!_lang) {
    _lang = process.env.DEFAULT_LANG
  }
  return _lang
};

const axiosApi = axios.create({
  baseURL: process.env.ROOT_API + '/api'
});

axiosApi.interceptors.request.use(
  function (config) {
    config.headers['X-Lang'] = getLanguage()
    return config
  },
  function (error) {
    return Promise.reject(error)
  }
);

export const setAuthHeader = (token) => {
  axiosApi.defaults.headers.common['X-Lang'] = getLanguage();
  if (!token) return;
  axiosApi.defaults.headers.common['Authorization'] = `Bearer ${token}`;
};

// Set the initial header from storage or something (should surround with try catch in actual app)
setAuthHeader(localStorage.getItem('token'));

export default axiosApi;
