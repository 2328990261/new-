<?php
namespace app\controller\api;

use app\BaseController;
use think\facade\Db;

/**
 * 微信/社交平台分享落地页（用于抓取 OG/meta）
 * 说明：微信“直接粘贴链接发送”时会抓取 HTML，不执行 SPA 的运行时代码。
 * 所以这里返回带 OG 标签的静态 HTML，并自动跳转到真实 H5 页面。
 */
class Share extends BaseController
{
    private const REDIRECT_DELAY_SECONDS = 1.5;

    public function refund()
    {
        return $this->renderSharePage(
            '退费申请',
            '请先搜索并选择订单，再按提示提交退费材料。',
            '/refund',
            '/share/refund'
        );
    }

    public function payment()
    {
        return $this->renderSharePage(
            '信息费支付｜家教预约',
            '请在微信内完成支付，支付成功后请保存凭证。',
            '/payment',
            '/share/payment'
        );
    }

    private function renderSharePage(string $title, string $desc, string $redirectPath, string $canonicalPath)
    {
        $nc = Db::name('notification_config')->find(1);
        $domain = !empty($nc['wechat_callback_domain'])
            ? rtrim((string)$nc['wechat_callback_domain'], '/')
            : rtrim((string)$this->request->domain(), '/');

        // 微信抓取页用的 canonical URL（用于生成聊天卡片）
        $url = $domain . $canonicalPath;

        // 实际跳转到 H5 的 URL（保留 query）
        $query = $this->request->server('QUERY_STRING');
        $redirect = $domain . $redirectPath . ($query ? ('?' . $query) : '');

        // 分享缩略图：优先使用后台配置；否则回退到前端静态路径
        $configured = is_array($nc) ? trim((string)($nc['wechat_share_image'] ?? '')) : '';
        $image = $configured !== '' ? $configured : ($domain . '/user/static/images/share-logo.png');

        $escTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $escDesc = htmlspecialchars($desc, ENT_QUOTES, 'UTF-8');
        $escUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $escRedirect = htmlspecialchars($redirect, ENT_QUOTES, 'UTF-8');
        $escImage = htmlspecialchars($image, ENT_QUOTES, 'UTF-8');
        $delay = (string)self::REDIRECT_DELAY_SECONDS;

        $html = <<<HTML
<!doctype html>
<html lang="zh-CN">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{$escTitle}</title>
    <meta name="description" content="{$escDesc}" />
    <meta property="og:title" content="{$escTitle}" />
    <meta property="og:description" content="{$escDesc}" />
    <meta property="og:url" content="{$escUrl}" />
    <meta property="og:image" content="{$escImage}" />
    <meta property="og:type" content="website" />
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:image" content="{$escImage}" />
    <!--
      注意：微信抓取链接卡片时会抓取 HTML，但不一定执行 JS；
      若立即 0 秒跳转，可能导致抓取器来不及读取 OG 标签。
      这里延迟跳转，提高“粘贴链接发送”时生成卡片的稳定性。
    -->
    <meta http-equiv="refresh" content="{$delay};url={$escRedirect}" />
    <script>
      setTimeout(function () {
        window.location.replace({$this->jsonEncode($redirect)});
      }, Math.round({$this->jsonEncode((string)(self::REDIRECT_DELAY_SECONDS * 1000))}));
    </script>
    <style>
      body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,"PingFang SC","Hiragino Sans GB","Microsoft YaHei",sans-serif;margin:0;padding:24px;color:#111}
      .card{max-width:560px;margin:0 auto;border:1px solid #eee;border-radius:12px;padding:16px}
      h1{font-size:18px;margin:0 0 8px}
      p{font-size:14px;line-height:1.6;margin:0 0 12px;color:#555}
      a{color:#07c160;text-decoration:none;word-break:break-all}
      .hint{font-size:12px;color:#888}
    </style>
  </head>
  <body>
    <div class="card">
      <h1>{$escTitle}</h1>
      <p>{$escDesc}</p>
      <div class="hint">正在跳转到页面… 若未跳转请点击：</div>
      <div><a href="{$escRedirect}">{$escRedirect}</a></div>
    </div>
  </body>
</html>
HTML;

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            // 避免被中间层长时间缓存导致卡片更新不生效
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0'
        ]);
    }

    private function jsonEncode(string $s): string
    {
        return json_encode($s, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

