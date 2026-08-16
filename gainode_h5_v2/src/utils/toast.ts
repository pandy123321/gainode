import { ref } from 'vue'

const toasts = ref<{ id: number; message: string }[]>([])
let nextId = 0

export function showToast(message: string, duration = 2000) {
  const id = nextId++
  toasts.value.push({ id, message })
  setTimeout(() => {
    toasts.value = toasts.value.filter((t) => t.id !== id)
  }, duration)
}

export function useToast() {
  return { toasts }
}
