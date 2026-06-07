export type FitnezUser = {
  id: number
  email: string
  full_name: string
  phone?: string | null
  birth_date?: string | null
  profile_picture_url?: string | null
  role: 'admin' | 'trainer' | 'member' | string
  role_id?: number
  is_active: boolean
  email_verified_at?: string | null
  membership_package_id?: number | null
  membership_started_at?: string | null
  membership_expires_at?: string | null
  free_class_access?: boolean | null
  membership_package?: {
    id: number
    code: string
    name: string
    duration_months: number
    price: number
    free_class_access: boolean
    benefits?: string[] | null
    is_active?: boolean
  } | null
  trainer_status?: 'not_submitted' | 'pending' | 'approved' | 'rejected' | string
  can_access_trainer_workspace?: boolean
  trainer_application_id?: number | null
}

export type LoginResult = {
  token_type: 'Bearer'
  access_token: string
  user: FitnezUser
}

export type RegisterProspectiveMemberPayload = {
  full_name: string
  email: string
  phone?: string
  password: string
  password_confirmation: string
}
