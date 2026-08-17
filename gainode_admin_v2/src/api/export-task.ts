/**
 * 导出任务 + 异步任务轮询（05 §6：POST /export-tasks → GET /async-jobs/{id}）。
 * 用于 Admin 报表导出等异步场景；终态判定以服务端 status 为准，不本地臆造进度。
 */
import { get, post } from './http-v2'
import type { AsyncJob, Envelope, ExportTaskResult } from './types'

/** 发起导出任务，返回 task_id 供轮询 */
export function createExportTask(
  params: Record<string, unknown>,
): Promise<Envelope<ExportTaskResult>> {
  return post<ExportTaskResult>('/api/v1/export-tasks', params)
}

/** 查询异步任务状态 */
export function getAsyncJob<T = unknown>(jobId: string): Promise<Envelope<AsyncJob<T>>> {
  return get<AsyncJob<T>>(`/api/v1/async-jobs/${jobId}`)
}

export interface PollOptions {
  /** 轮询间隔毫秒，默认 1500 */
  intervalMs?: number
  /** 最大轮询次数，默认 60（约 90s）；超时抛错，不无限挂起 */
  maxAttempts?: number
  /** 每次轮询后回调（用于更新 UI 进度） */
  onProgress?: (job: AsyncJob) => void
  /** 是否已取消（外部传入可取消信号） */
  isCancelled?: () => boolean
}

const sleep = (ms: number) => new Promise<void>((resolve) => setTimeout(resolve, ms))

/**
 * 轮询异步任务直到 completed/failed。
 * 终止条件：终态 / 超次数 / 外部取消。超次数抛错，调用方据此进入错误态。
 */
export async function pollAsyncJob<T = unknown>(
  jobId: string,
  options: PollOptions = {},
): Promise<AsyncJob<T>> {
  const intervalMs = options.intervalMs ?? 1500
  const maxAttempts = options.maxAttempts ?? 60

  for (let attempt = 0; attempt < maxAttempts; attempt++) {
    if (options.isCancelled?.()) {
      throw new Error('轮询已取消')
    }
    const { data: job } = await getAsyncJob<T>(jobId)
    options.onProgress?.(job)
    if (job.status === 'completed' || job.status === 'failed') {
      return job
    }
    await sleep(intervalMs)
  }
  throw new Error(`异步任务 ${jobId} 轮询超时`)
}
