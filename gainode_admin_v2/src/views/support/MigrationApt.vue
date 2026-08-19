<template>
  <div class="page">
    <el-result icon="info" title="APT Migration（Future / CLOSED）" sub-title="该功能为 Future 阶段，当前 CLOSED，仅保留对象契约与预留信息，不提供执行控件。">
      <template #extra>
        <el-descriptions :column="1" border>
          <el-descriptions-item label="状态">CLOSED by default</el-descriptions-item>
          <el-descriptions-item label="对象契约">MigrationRequest</el-descriptions-item>
          <el-descriptions-item label="接口预留">POST /migration-requests；GET finality</el-descriptions-item>
        </el-descriptions>
      </template>
    </el-result>
  </div>
</template>

<script lang="ts">
export default { name: 'MigrationApt' }
</script>

<script lang="ts" setup>
// A-MIGRATION-001 APT Migration（Future/CLOSED）。P0 不显示可执行 create 按钮（07 §8）。
</script>

<style scoped>
.page { padding: 16px 24px; max-width: 960px; margin: 0 auto; }
</style>
