# HAProxy + 雙 AP + MySQL + Log 備份

內容包含：

- HAProxy 前端 LB
- AP × 多台
- DB
- 健康檢查
- Stats 監控
- Log 查詢
- Log 備份（手動＋自動備份腳本

## Docker Compose 負載平衡與健康檢查完整練習

本篇紀錄我在本機使用 Docker Compose 建立：

- **Public Subnet：HAProxy Load Balancer**
- **Private Subnet：AP × 多台、MySQL DB**
- **HAProxy 健康檢查 (HTTP health check)**
- **HAProxy Stats 頁面**
- **Log 查詢 / Log 備份策略（含自動化腳本）**

透過這次實作，我熟悉了：

- 如何實作「前端 HAProxy → 後端 AP Pool」
- 如何讓多台 AP 並加入 LB
- 如何使用健康檢查自動踢掉壞掉的 AP
- 如何管理 Docker logs（查詢 / 備份 / 輪替）

---

# ## 目錄

1. 系統架構圖
2. 專案目錄結構
3. docker-compose.yml
4. HAProxy 設定（含健康檢查 + stats）
5. AP（PHP）程式：health.php & hostname 顯示
6. 啟動與測試
7. 檢查流量是否輪詢
8. 模擬 AP 掛掉與自動復原
9. Log 查詢方式
10. Log 備份策略
11. 自動備份腳本（Linux / Windows）

---

# ## 1. 系統架構圖

**Public Subnet**

```
+--------------+
|   HAProxy    |  <-- 提供 Public Port 80
|  LoadBalance |
+--------------+
        |
        |
Private Subnet
  |----------+-----------+
  |                      |
+------+            +---------+
| AP1  |            |  AP2    |   <-- 可多台架構
+------+            +---------+
        \          /
         \        /
        +-------------+
        |   MySQL     |
        +-------------+
```

---

# ## 2. 專案目錄結構

```
public_private_net/
├── docker-compose.yml
├── haproxy/
│   └── haproxy.cfg
├── ap/
│   ├── Dockerfile
│   ├── index.php
│   ├── login.php
│   ├── welcome.php
│   ├── logout.php
│   └── health.php
└── db/
    └── init.sql
```

---

# ## 3. docker-compose.yml

```yaml
version: "3.8"

services:
  haproxy:
    image: haproxy:2.9
    container_name: haproxy
    ports:
      - "80:80"
      - "8404:8404" # 把 8404 映射出來 → HAProxy stats 頁面（可視化看 UP/DOWN）
    volumes:
      - ./haproxy/haproxy.cfg:/usr/local/etc/haproxy/haproxy.cfg:ro
    depends_on:
      - ap1
      - ap2
    networks:
      - public_net
      - private_net

  ap1:
    build: ./ap
    container_name: ap1
    networks:
      - private_net
    depends_on:
      - db

  ap2:
    build: ./ap
    container_name: ap2
    networks:
      - private_net
    depends_on:
      - db

  db:
    image: mysql:8
    container_name: db
    environment:
      MYSQL_ROOT_PASSWORD: Dev12876266
      MYSQL_DATABASE: test
      MYSQL_ROOT_HOST: "%"
    networks:
      - private_net
    volumes:
      - db_data:/var/lib/mysql
      - ./db/init.sql:/docker-entrypoint-initdb.d/init.sql:ro

volumes:
  db_data:

networks:
  public_net:
    driver: bridge
  private_net:
    driver: bridge
```

---

# ## 4. HAProxy 設定（含健康檢查 + stats）

`haproxy/haproxy.cfg`

```cfg
global
    log stdout format raw daemon

defaults
    log     global
    mode    http
    option  httplog
    option  dontlognull
    timeout connect 5s
    timeout client  50s
    timeout server  50s

frontend http_in
    bind *:80
    default_backend ap_servers

backend ap_servers
    balance roundrobin

    # 對 backend 做 HTTP 健康檢查，打 /health.php
    option httpchk GET /health.php
    http-check expect status 200

    # inter 3000：每 3 秒檢查一次
    # fall 2：連續 2 次失敗就標記為 DOWN
    # rise 3：連續 3 次成功才標記為 UP
    server ap1 ap1:80 check inter 3000 fall 2 rise 3
    server ap2 ap2:80 check inter 3000 fall 2 rise 3

# （推薦）加一個 HAProxy stats 頁面（可視化看 UP/DOWN）
listen stats
    bind *:8404
    mode http
    stats enable
    stats uri /stats
    stats refresh 3s
```

---

# ## 5. AP 端：health.php ＋ hostname 顯示

### health.php

```php
<?php
http_response_code(200);
header('Content-Type: text/plain');
echo "OK";
```

### index.php 最底部加入：

```php
<p>Served by： <?= gethostname() ?> </p>
```

---

# ## 6. 啟動、scale 與測試

### 啟動整套 docker compose

```bash
docker compose up -d --build
```

### 確認容器：

```bash
docker compose ps -a
```

輸出應包含：

```
ap1
ap2
db
haproxy
```

---

# ## 7. 測試流量是否輪詢

執行輪詢測試：

```bash
for i in {1..10}; do
  echo -n "$i: "
  curl -s http://localhost/index.php | grep "Served by" || echo "no resp"
  sleep 1
done
```

預期結果：

```
1: Served by： ap1 (CONTAINER ID)
2: Served by： ap2 (CONTAINER ID)
3: Served by： ap1 (CONTAINER ID)
4: Served by： ap2 (CONTAINER ID)
...
```

---

# ## 8. 模擬 AP 壞掉 → HAProxy 自動踢掉

停掉 AP1：

```bash
docker stop ap1
```

stats 頁面會看到：

```
ap1 變成紅色 DOWN
ap2 持續 UP
```

瀏覽器 / curl 都只會落到 AP2。

重新啟動 AP1：

```bash
docker start ap1
```

HAProxy 會在幾次健康檢查之後把 AP1 自動加入 pool。

---

# ## 9. Log 查詢方式

### 查看所有 service log：

```bash
docker compose logs
```

### 查看單一 service：

```bash
docker compose logs haproxy
docker compose logs ap
docker compose logs db
```

### 只看最近一小時：

```bash
docker compose logs --since 1h
```

---

# ## 10. Log 備份策略

常見做法：

### (A) 手動匯出 log

```bash
docker compose logs > logs-$(date +%Y%m%d-%H%M).log
```

### (B) 匯出最近 1 小時：

```bash
docker compose logs --since 1h > logs-hourly-$(date +%Y%m%d-%H).log
```

### (C) 讓應用程式寫到 volume，再備份資料夾

（正式環境推薦）

---

# ## 11. 自動備份腳本（Linux / Windows）

---

## Linux / WSL

建立：`backup_docker_logs.sh`

```bash
#!/usr/bin/env bash
set -e

PROJECT_DIR="/home/ubuntu/public_private_net"
BACKUP_DIR="$PROJECT_DIR/log-backup"

mkdir -p "$BACKUP_DIR"
cd "$PROJECT_DIR"

FNAME="logs-$(date +%Y%m%d-%H%M).log"
docker compose logs --since 1h > "$BACKUP_DIR/$FNAME"

gzip "$BACKUP_DIR/$FNAME"
```

cron 每小時執行：

```cron
0 * * * * /home/ubuntu/public_private_net/backup_docker_logs.sh >/dev/null 2>&1
```

---

## Windows PowerShell

建立：`backup_docker_logs.ps1`

```powershell
$projectDir = "D:\Downloads\tmp\docker\public_private_net"
$backupDir  = Join-Path $projectDir "log-backup"

New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
Set-Location $projectDir

$timestamp = Get-Date -Format "yyyyMMdd-HHmm"
$fileName  = "logs-$timestamp.log"
$fullPath  = Join-Path $backupDir $fileName

docker compose logs --since 1h | Out-File -FilePath $fullPath -Encoding UTF8

Compress-Archive -Path $fullPath -DestinationPath "$fullPath.zip"
Remove-Item $fullPath
```

使用 Windows Task Scheduler 設定自動執行。

---

# ## 結語

這次練習讓我完整體驗了：

- Docker Compose 建構多網段架構
- HAProxy 的基本 + 健康檢查 + stats
- AP 服務掛掉自動踢除＋復原
- Docker logs 的管理與備份

整套是非常完整的「本機版微型負載平衡系統」，也能直接延伸到正式環境概念。

---
