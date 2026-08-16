/**
 * 文件上传（V2 契约）—— 后端签发 presigned URL，客户端不再持有任何云密钥。
 *
 * 参考：03_MOBILE §接口 `POST /api/v1/uploads`；07 §S03 上传改 presigned URL。
 * 旧实现使用硬编码 AWS AK/SK（已在 S03-P01 步骤 8 移除）。
 */
import { post } from '../api/http'

export interface PresignedUpload {
  upload_url: string
  object_url: string
}

export async function s3Upload(file: File, fileName: string, folder = 'avatars'): Promise<string | null> {
  const env = await post<PresignedUpload>('/api/v1/uploads', {
    key: `${folder}/${fileName}`,
    content_type: file.type || 'application/octet-stream',
  })

  const uploadUrl = env.data?.upload_url
  if (!uploadUrl) return null

  const put = await fetch(uploadUrl, {
    method: 'PUT',
    headers: { 'Content-Type': file.type || 'application/octet-stream' },
    body: file,
  })
  if (!put.ok) return null
  return env.data.object_url ?? uploadUrl.split('?')[0]
}

/** 生成唯一文件名 */
export function generateFileName(file: File): string {
  const ext = file.name.split('.').pop() || 'jpg'
  const ts = Date.now()
  const rand = Math.random().toString(36).slice(2, 8)
  return `${ts}_${rand}.${ext}`
}
