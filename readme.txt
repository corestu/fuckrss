=== FuckRSS ===
Contributors: fuckrss
Tags: rss, feed, anti-scraping, user-agent
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

按 User-Agent 关键词识别 RSS 抓取者，命中后改写 Feed 摘要与最后一篇标题。

== Description ==

FuckRSS 在 Feed 输出前（template_redirect）检查请求的 User-Agent，命中后台配置的规则后，只在摘要（rss2 / rdf 的 description、atom 的 summary）里插入该规则对应的加料句；若该规则填了加料标题，再把 Feed 中最后一篇的标题换成它。链接、作者、时间、guid、正文（content:encoded）等其余 Feed 数据保持原样。

加料方式：摘要按句切分后原文一句不删，把配置的句子插到最后一句之前，无任何标记或提示，读者端阅读自然。

功能：

* 每条规则包含一个 UA 关键词、一句摘要加料句与一个可选的加料标题，关键词为包含匹配、不区分大小写
* 摘要加料句与加料标题都留空的规则不生效，需逐条填写
* 加料标题只替换 Feed 最后一篇的标题，其余文章标题不变
* 其余 Feed 数据不变
* 启用后 Feed 响应带 no-store 与 X-Accel-Expires: 0，避免缓存层让 UA 判定失效
* 总开关，关闭后摘要与标题立即恢复原样
* 卸载时清理全部配置与 transient，不留残留数据

== Installation ==

1. 上传 `fuckrss` 目录到 `/wp-content/plugins/`
2. 在后台「插件」中启用
3. 进入「设置 → FuckRSS」，逐条添加 UA 关键词、摘要加料句与加料标题

== FAQ ==

= 关键词是怎么匹配的？ =

对 User-Agent 做小写化后做子串匹配，只要包含关键词即命中。例如关键词 `feedspot` 可命中 `FeedspotBot/1.0`。

= 摘要会被改成什么样？ =

原文：`第一句。第二句。第三句。`
输出：`第一句。第二句。加料句。第三句。`

原句全部保留，只多出一句；摘要只有一句时插在该句之前，摘要为空时只输出加料句。加料句若未带句末标点会自动补上「。」。

= 会不会影响正文？ =

不会。`content:encoded` 与其它字段完全不参与处理，只有摘要被改写。

= 加料标题替换的是哪一篇？ =

Feed 输出顺序中的最后一篇（默认排序下即检索结果里最早的一篇）。该规则的加料标题留空时不做替换。

= 缓存会不会让加料失效？ =

会，所以插件在启用后对所有 Feed 响应发送 `Cache-Control: no-store` 与 `X-Accel-Expires: 0`，避免缓存层直接返回正常副本。Nginx 若用 `fastcgi_ignore_headers` 忽略了这两个头，需在服务端按 UA 配置 `fastcgi_cache_bypass`，示例见 README.md 的「缓存」一节。

= 卸载后会留下数据吗？ =

不会。`uninstall.php` 会删除 `fuckrss_settings` 以及 `fuckrss_` 前缀的 transient，多站点下逐站点清理。

== Changelog ==

= 1.0.0 =
* 按 UA 规则在 Feed 摘要中插入专属加料句，其余 Feed 数据保持不变
