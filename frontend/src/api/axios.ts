import { http } from './http';

export const api = {
  get: async (path: string) => {
    const response = await http.get<any>(path);
    return response;
  },
  post: async (path: string, body?: any) => {
    const response = await http.post<any>(path, body);
    return response;
  },
  put: async (path: string, body?: any) => {
    const response = await http.put<any>(path, body);
    return response;
  },
  patch: async (path: string, body?: any) => {
    const response = await http.patch<any>(path, body);
    return response;
  },
  delete: async (path: string) => {
    const response = await http.delete<any>(path);
    return response;
  }
};

export default api;
