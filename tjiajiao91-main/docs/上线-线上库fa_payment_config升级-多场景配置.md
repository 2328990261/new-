# 上线文档：线上库 `fa_payment_config` 升级为多场景（default/h5）配置

适用范围：你的线上库当前 `fa_payment_config` 仍是**单条支付配置**结构（仅 `payment_method` 唯一），需要升级为支持多套微信支付配置（按 `scene` 区分，如 `default` / `h5`）。

## 0. 重要结论（先看这个）

- **必须先执行本页 SQL 再发布后端代码**。否则后端会因为缺少字段 `scene / name / is_default` 在查询 `payment_config` 时直接报错，导致 **创建支付订单不可用**。
- 线上旧库若存在 `UNIQUE(payment_method)`，会阻止新增第二条 `wechat` 配置（例如 `scene=h5`），必须改成联合唯一。

---

## 1. 当前差异（你线上 `tjiajiao91.sql` vs 本地 `myjiajiao.sql`）

线上缺少字段（必须补齐）：
- `scene`：配置场景（default/h5/...）
- `name`：配置名称
- `is_default`：同一 `payment_method + scene` 的默认标记

线上索引不匹配（必须改）：
- 线上是 `UNIQUE(payment_method)`（会阻止同一支付方式多套配置）
- 目标是 `UNIQUE(payment_method, scene, name)`（与你本地 `myjiajiao.sql` 一致）

字段定义建议统一（可选但推荐）：
- `refund_follow_qrcode`：你线上是 `varchar(512) NULL`；你本地是 `varchar(255) NOT NULL DEFAULT ''`。如果你线上二维码 URL 可能较长，推荐保留 **512**，同时改成 `NOT NULL DEFAULT ''` 以匹配后台/前端的默认行为。

---

## 2. 上线前备份（强烈建议）

```sql
-- 备份表结构与数据（按你习惯选择一种）
CREATE TABLE fa_payment_config_bak_20260413 LIKE fa_payment_config;
INSERT INTO fa_payment_config_bak_20260413 SELECT * FROM fa_payment_config;
```

---

## 3. 变更 SQL（按顺序执行）

> 说明：以下 SQL 按 MySQL 5.7 编写。若字段/索引已存在，跳过对应语句。

### 3.1 新增字段：`scene` / `name` / `is_default`

```sql
ALTER TABLE fa_payment_config
  ADD COLUMN scene varchar(32) NOT NULL DEFAULT 'default' COMMENT '配置场景(default/h5/...)' AFTER payment_method;

ALTER TABLE fa_payment_config
  ADD COLUMN name varchar(64) NOT NULL DEFAULT '' COMMENT '配置名称' AFTER scene;

ALTER TABLE fa_payment_config
  ADD COLUMN is_default tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否默认(同method+scene唯一)' AFTER is_enabled;
```

### 3.2 新增字段：`fa_payments.wechat_payment_config_id`（必须）

> 作用：下单时把“用了哪一套微信配置”写入支付单，后续回调验签/退款会用到。
>
> 你截图报错 `Unknown column 'wechat_payment_config_id' in 'field list'` 就是因为线上缺这个字段。

```sql
ALTER TABLE fa_payments
  ADD COLUMN wechat_payment_config_id int(11) DEFAULT NULL COMMENT '微信支付配置ID' AFTER payment_method;
```

### 3.3 数据回填：让历史记录变成「default + 有名称 + 默认」

> 目的：避免后续加联合唯一时出现重复（尤其 `name=''` 会冲突）。

```sql
-- 补齐 wechat 记录
UPDATE fa_payment_config
SET scene = 'default',
    name = IF(TRIM(IFNULL(name,'')) = '', '默认微信支付', name),
    is_default = 1
WHERE payment_method = 'wechat';

-- 补齐 alipay 记录（如存在）
UPDATE fa_payment_config
SET scene = 'default',
    name = IF(TRIM(IFNULL(name,'')) = '', '默认支付宝', name),
    is_default = 1
WHERE payment_method = 'alipay';
```

> 若你希望支付宝不设默认，把 `is_default=1` 改成 `0` 即可；微信建议至少保留一条默认。

### 3.4 索引改造：删除单列唯一，新增联合唯一

#### 3.3.1 删除 `payment_method` 单列唯一

> 你的线上 `tjiajiao91.sql` 里是 `UNIQUE KEY payment_method (payment_method)`，必须删掉。

```sql
ALTER TABLE fa_payment_config DROP INDEX payment_method;
```

#### 3.3.2 新增联合唯一（与你本地一致）

```sql
ALTER TABLE fa_payment_config
  ADD UNIQUE KEY uk_method_scene_name (payment_method, scene, name);
```

### 3.5（可选但推荐）统一 `refund_follow_qrcode` 默认值行为

> 若你希望与本地一致（避免 NULL 判断），推荐执行。若你担心 URL 超过 255，保留 512 即可。

**方案 A（推荐，保留 512 长度）**

```sql
ALTER TABLE fa_payment_config
  MODIFY COLUMN refund_follow_qrcode varchar(512) NOT NULL DEFAULT '' COMMENT '退费页关注公众号二维码图片URL';
```

**方案 B（严格对齐你本地 255）**

```sql
ALTER TABLE fa_payment_config
  MODIFY COLUMN refund_follow_qrcode varchar(255) NOT NULL DEFAULT '' COMMENT '退费页关注二维码';
```

---

## 4. 上线后验证 SQL

```sql
SHOW COLUMNS FROM fa_payment_config LIKE 'scene';
SHOW COLUMNS FROM fa_payment_config LIKE 'name';
SHOW COLUMNS FROM fa_payment_config LIKE 'is_default';

SHOW INDEX FROM fa_payment_config;

SELECT id, payment_method, scene, name, is_enabled, is_default
FROM fa_payment_config
ORDER BY id ASC;
```

你至少应看到：
- `payment_method=wechat AND scene=default AND is_default=1`
- 之后可以新增 `payment_method=wechat AND scene=h5 ...`

---

## 5. 为什么不先跑会导致支付不可用（后端依赖点）

以下后端代码会直接查询 `payment_config.scene / is_default / name`：
- `app\model\PaymentConfig::getConfigRow()`：`where('scene', ...)` + `order('is_default','desc')`
- `app\controller\api\Payment::create()`：会调用 `PaymentConfig::getConfigRow('wechat', $wxScene)` 取配置。若字段不存在会抛 SQL 错，**创建支付订单失败**。
- `app\controller\api\RefundApi::gateConfig()`：`where('scene','default')` 取 `refund_follow_qrcode`
- `app\controller\admin\PaymentConfig` / `admin\Payment`：列表/保存均依赖 `scene/is_default/name`

所以：**数据库结构升级必须先于后端上线**。

