export function isRequired(value: unknown) {
  return value !== null && value !== undefined && String(value).trim().length > 0
}

export function isEmail(value: string) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim())
}

export function isPhoneNumber(value: string) {
  return /^(\+62|62|0)8[1-9][0-9]{6,11}$/.test(value.replace(/[\s-]/g, ''))
}

export function passwordStrength(value: string) {
  let score = 0
  if (value.length >= 8) score += 1
  if (value.length >= 12) score += 1
  if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score += 1
  if (/\d/.test(value)) score += 1
  if (/[^A-Za-z0-9]/.test(value)) score += 1
  return score
}

export function isStrongPassword(value: string) {
  return passwordStrength(value) >= 4
}
