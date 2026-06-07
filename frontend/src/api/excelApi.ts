import { http } from './http'

export type ImportResult = {
  success: boolean
  message: string
  job_id: string
  imported?: number
  total?: number
  errors?: string[]
}

export const excelApi = {
  upload(file: File, type: string) {
    const form = new FormData()
    form.append('file', file)
    form.append('type', type)
    return http.post<ImportResult>('/excel/import', form)
  },
}
