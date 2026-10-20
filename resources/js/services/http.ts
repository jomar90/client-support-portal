import axios from 'axios';

const http = axios.create({
    headers: {
        Accept: 'application/json',
    },
    withCredentials: true,
    withXSRFToken: true,
});

export default http;
