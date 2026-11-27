# FuckRSS

WordPress 插件：按 User-Agent 关键词识别 RSS 抓取者，命中后改写 Feed 的摘要与最后一篇的标题，其余数据保持原样。

## 功能

- **按 UA 分流**：每条规则绑定一个 UA 关键词与独立文案，关键词为包含匹配、不区分大小写
- **摘要加料**：把配置的句子插入到每条摘要的最后一句之前，原文一句不删
- **标题替换**：把 Feed 最后一篇的标题换成配置的标题，其余文章标题不变
- **数据保真**：链接、作者、时间、`guid`、`content:encoded`、分类等字段不参与处理
- **只作用于 Feed**：普通页面、后台、REST 请求不受影响
- **可回滚**：总开关关闭后立即恢复原文，配置保留
- **卸载干净**：删除插件时清理选项与 transient，不留残留数据

## 安装

1. 将 `fuckrss` 目录放入 `wp-content/plugins/`
2. 在后台「插件」列表启用
3. 进入「设置 → FuckRSS」配置规则

要求 WordPress 5.8+、PHP 7.4+。

## 配置项

| 字段 | 说明 |
| --- | --- |
| 启用 RSS 加料 | 总开关，仅对 Feed 请求生效 |
| UA 关键词 | 包含即命中，不区分大小写 |
| 摘要加料句 | 插入到每条摘要的最后一句之前 |
| 加料标题 | 替换 Feed 最后一篇的标题 |

摘要加料句与加料标题都留空的规则不生效。

## 效果

```
原文：第一句。第二句。第三句。
输出：第一句。第二句。加料句。第三句。
```

- 摘要只有一句时，加料句插在该句之前
- 摘要为空时只输出加料句
- 加料句缺句末标点会自动补「。」（英文补 `. `）
- 覆盖 RSS 2.0、RDF、Atom，对应 `description` / `summary`

## 实现

拦截点在 `template_redirect`（优先级 1），早于 Feed 模板加载。命中后只挂两个过滤器：

| 过滤器 | 作用 |
| --- | --- |
| `the_excerpt_rss` | 拆分摘要句子，插入加料句 |
| `the_title_rss` | 仅改主循环中最后一篇的标题 |

## 缓存

Feed 内容按 UA 变化，而 UA 判定只在 PHP 内执行。只要有缓存层直接返回旧副本，命中 UA 就拿不到加料内容，功能等于失效。因此启用插件后，**所有** Feed 响应都会发送：

```
Cache-Control: no-store, no-cache, must-revalidate, max-age=0
Pragma: no-cache
Expires: Wed, 11 Jan 1984 05:00:00 GMT
X-Accel-Expires: 0
Vary: User-Agent
X-FuckRSS: hit          # 仅命中请求
```

| 头 | 作用 |
| --- | --- |
| `X-Accel-Expires: 0` | Nginx `fastcgi_cache` / `proxy_cache` 据此禁用缓存 |
| `no-store` | 浏览器、CDN、页面缓存插件不再存储 |
| `Vary: User-Agent` | 遵循该头的 CDN 按 UA 分键 |

### FastCGI cache

Nginx 默认遵循上游的 `Cache-Control` 与 `X-Accel-Expires`，上述头即可生效。若站点在 `fastcgi_ignore_headers` 中忽略了它们，PHP 端无法覆盖，需要在 Nginx 侧按 UA 跳过缓存：

```nginx
map $http_user_agent $fuckrss_bypass {
    default 0;
    ~*(feedspot|bloghui) 1;   # 与后台关键词保持一致
}

fastcgi_cache_bypass $fuckrss_bypass;
fastcgi_no_cache    $fuckrss_bypass;
```

注意 `Vary` 只在 CDN 与浏览器层生效，Nginx `fastcgi_cache` 不按 `Vary` 分键（仅 `Vary: *` 会拒绝缓存）。

### 验证

```bash
curl -sI -A "BlogHui/1.0" https://example.com/feed/
```

命中时应看到 `X-FuckRSS: hit` 与 `Cache-Control: no-store`。若缺少，说明请求在到达 PHP 前已被缓存层响应。缓存里若已存有正常副本，该副本会继续服务到过期，期间加料不生效（但不会把加料内容泄露给其他读者）。

## 卸载

`uninstall.php` 删除 `fuckrss_settings` 与 `fuckrss_` 前缀的 transient，多站点下逐站点执行。插件不创建自定义表，也不注册定时任务。

## 许可

GPL-2.0-or-later
