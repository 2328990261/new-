<template>
  <footer class="site-footer">
    <div class="footer-main">
      <div class="footer-brand footer-col">
        <div class="brand-mark">91家教</div>
        <p class="brand-desc">专注家教信息服务平台。连接家长、学生与老师。</p>
      </div>

      <div class="link-group footer-col">
        <h4>我是学员</h4>
        <a href="#">找家教流程</a>
        <a href="#">学员收费标准</a>
        <a href="#">挑选合适教员</a>
      </div>

      <div class="link-group footer-col">
        <h4>我是老师</h4>
        <a href="#">如何预约订单</a>
        <a href="#">教员收费标准</a>
        <a href="#">认证教学资质</a>
      </div>

      <div class="link-group footer-col">
        <h4>关于我们</h4>
        <a href="#">公司简介</a>
        <a href="#">隐私保护</a>
        <a href="#">用户协议</a>
      </div>

      <div class="footer-right footer-col">
        <div class="footer-contact">
          <div class="phone-label">客服电话</div>
          <div class="phone-number">4006-179-958</div>
          <div class="time-text">周一到周日 10:00-21:40 在线服务</div>
        </div>

        <div class="footer-qrcode">
          <div class="qr-title">微信关注“家教114”</div>
          <div class="qrcode-box qrcode-main"><div class="qr-placeholder">QR</div></div>
          <div class="qrcode-tip">扫码访问便捷服务</div>
        </div>
      </div>
    </div>

      <div class="copyright">
        版权所有 © 91家教
      </div>
    
  </footer>
</template>

<style scoped>
.site-footer {
  margin-top: 20px;
  background: #1f2229;
  color: #d1d5db;
}

.footer-main {
  max-width: 1280px;
  margin: 0 auto;
  padding: 26px 20px 18px;
  display: grid;
  grid-template-columns: 1fr 1fr 1fr 1fr 2fr;
  column-gap: 0;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.footer-col {
  padding: 0 18px;
  border-right: 1px solid rgba(255, 255, 255, 0.08);
}

.footer-col:last-child {
  border-right: none;
}

.brand-mark {
  font-size: 42px;
  line-height: 1;
  font-weight: 900;
  color: #f3f4f6;
  margin-bottom: 14px;
}

.brand-desc {
  margin: 0;
  line-height: 1.8;
  font-size: 14px;
  color: #9ca3af;
}

.link-group h4 {
  margin: 0 0 12px;
  font-size: 20px;
  color: #86efac;
}

.link-group a {
  display: block;
  color: #d1d5db;
  text-decoration: none;
  font-size: 14px;
  line-height: 1.6;
  margin-bottom: 10px;
}

.link-group a:hover {
  color: #ffffff;
}

.footer-contact {
  display: flex;
  flex-direction: column;
  justify-content: flex-start;
  gap: 6px;
}

.footer-right {
  display: grid;
  grid-template-columns: 1fr 1fr;
  align-items: start;
  gap: 14px;
}

.phone-label {
  font-size: 14px;
  color: #9ca3af;
}

.phone-number {
  font-size: 30px;
  line-height: 1.15;
  font-weight: 900;
  color: #4ade80;
  white-space: nowrap;
  letter-spacing: 0.5px;
}

.time-text {
  font-size: 14px;
  color: #9ca3af;
}

.footer-qrcode {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
  text-align: center;
  border-left: 1px solid rgba(255, 255, 255, 0.08);
  padding-left: 14px;
}

.qr-title {
  font-size: 13px;
  color: #f59e0b;
  margin-bottom: 10px;
}

.qrcode-main {
  width: 180px;
  height: 180px;
  background: #ffffff;
  border-radius: 4px;
  display: grid;
  place-items: center;
}

.qrcode-main .qr-placeholder {
  width: 136px;
  height: 136px;
  border: 2px solid #111827;
  color: #111827;
  font-weight: 700;
  display: grid;
  place-items: center;
  font-size: 20px;
}

.qrcode-tip {
  margin-top: 10px;
  font-size: 12px;
  color: #9ca3af;
}

.footer-bottom {
  max-width: 1280px;
  margin: 0 auto;
  padding: 12px 20px 16px;
  font-size: 12px;
  color: #9ca3af;
  display: flex;
  justify-content: space-between;
  gap: 10px;
}

@media (max-width: 1100px) {
  .footer-main {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 16px;
  }

  .footer-col {
    border-right: none;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    padding-bottom: 12px;
  }

  .footer-col:last-child {
    border-bottom: none;
    padding-bottom: 0;
  }

  .footer-bottom {
    flex-direction: column;
  }

  .footer-right {
    grid-template-columns: 1fr;
    gap: 12px;
  }

  .footer-qrcode {
    border-left: none;
    padding-left: 0;
    align-items: flex-start;
    text-align: left;
  }
}

@media (max-width: 640px) {
  .phone-number {
    font-size: 34px;
  }

  .link-group h4 {
    font-size: 20px;
  }

  .link-group a {
    font-size: 16px;
  }

  .phone-label,
  .time-text,
  .qr-title,
  .qrcode-tip {
    font-size: 14px;
  }

  .qrcode-main {
    width: 120px;
    height: 120px;
  }

  .qrcode-main .qr-placeholder {
    width: 88px;
    height: 88px;
    font-size: 18px;
  }
}
</style>
