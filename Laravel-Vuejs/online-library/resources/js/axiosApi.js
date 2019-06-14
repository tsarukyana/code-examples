import axios from 'axios';

const axiosApi = axios.create({
  baseURL: '/api'
});

export const setAuthHeader = (token_type, token) => {
  if (!token_type || !token) return;
  axiosApi.defaults.headers.common['Authorization'] = `${token_type} ${token}`;

};

// Set the initial header from storage or something (should surround with try catch in actual app)
setAuthHeader(localStorage.getItem('token_type'), localStorage.getItem('access_token'));

export default axiosApi;
