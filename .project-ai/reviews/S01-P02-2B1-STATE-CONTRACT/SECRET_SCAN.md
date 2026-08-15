# SECRET_SCAN — S01-P02 复审包

## 扫描对象

- `DIFF.txt`（完整 diff，25522 字符）
- `files_at_impl/*.txt`（3 个变更文件全文快照）

## 扫描规则

```text
private_key
PRIVATE KEY
BEGIN RSA
BEGIN EC
mnemonic
seed phrase
recovery phrase
AKIA[0-9A-Z]{16}
api[_-]?key\s*=\s*['"]?[A-Za-z0-9]{16,}
secret\s*=\s*['"]?[A-Za-z0-9]{16,}
password\s*=\s*['"][^'"]+['"]
0x[0-9a-fA-F]{64}
```

## 结果

```text
SECRET_SCAN_TOTAL_HITS = 0
VERDICT = PASS
```
