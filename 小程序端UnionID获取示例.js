/**
 * 微信小程序端 UnionID 获取示例代码
 * 
 * 使用说明：
 * 1. 将此代码集成到你的小程序项目中
 * 2. 根据实际情况选择合适的方案
 * 3. 替换 API_BASE_URL 为你的实际后端地址
 */

const API_BASE_URL = 'https://你的域名'; // 替换为实际后端地址

// ============================================
// 方案一：直接登录（需要开放平台绑定）
// ============================================

/**
 * 小程序登录 - 如果已绑定开放平台，会自动返回 unionid
 */
function loginWithUnionid() {
  return new Promise((resolve, reject) => {
    wx.login({
      success: (res) => {
        if (res.code) {
          // 将 code 发送到后端
          wx.request({
            url: `${API_BASE_URL}/api/wechat/login`,
            method: 'POST',
            data: {
              code: res.code
            },
            success: (response) => {
              if (response.data.success) {
                const { openid, unionid, token } = response.data;
                
                console.log('登录成功');
                console.log('OpenID:', openid);
                console.log('UnionID:', unionid || '未获取到');
                
                // 保存到本地
                wx.setStorageSync('openid', openid);
                wx.setStorageSync('token', token);
                
                if (unionid) {
                  wx.setStorageSync('unionid', unionid);
                  resolve({ openid, unionid, token });
                } else {
                  // 未获取到 unionid，需要引导用户关注公众号
                  console.warn('未获取到 UnionID，建议引导用户关注公众号');
                  resolve({ openid, unionid: null, token });
                }
              } else {
                reject(new Error(response.data.message || '登录失败'));
              }
            },
            fail: reject
          });
        } else {
          reject(new Error('获取 code 失败'));
        }
      },
      fail: reject
    });
  });
}

// ============================================
// 方案二：公众号扫码关注绑定
// ============================================

/**
 * 生成公众号带参二维码
 * @param {string} miniOpenid 小程序 openid
 */
function generateOfficialQRCode(miniOpenid) {
  return new Promise((resolve, reject) => {
    wx.request({
      url: `${API_BASE_URL}/api/wechat/official/qrcode`,
      method: 'POST',
      data: {
        mini_openid: miniOpenid
      },
      success: (res) => {
        if (res.data.success) {
          resolve(res.data.qrcode_url);
        } else {
          reject(new Error(res.data.message || '生成二维码失败'));
        }
      },
      fail: reject
    });
  });
}

/**
 * 检查公众号绑定状态
 * @param {string} miniOpenid 小程序 openid
 */
function checkBindingStatus(miniOpenid) {
  return new Promise((resolve, reject) => {
    wx.request({
      url: `${API_BASE_URL}/api/wechat/official/bind-status`,
      method: 'GET',
      data: {
        mini_openid: miniOpenid
      },
      success: (res) => {
        if (res.data.success) {
          resolve({
            isBound: res.data.is_bound,
            isSubscribed: res.data.is_subscribed,
            unionid: res.data.unionid,
            mpOpenid: res.data.mp_openid
          });
        } else {
          reject(new Error(res.data.message || '查询失败'));
        }
      },
      fail: reject
    });
  });
}

/**
 * 完整的公众号绑定流程
 * 使用场景：在小程序中展示二维码，引导用户关注公众号
 */
async function bindOfficialAccount() {
  try {
    // 1. 先登录获取 openid
    const loginResult = await loginWithUnionid();
    const miniOpenid = loginResult.openid;
    
    // 如果已经有 unionid，无需绑定
    if (loginResult.unionid) {
      console.log('已有 UnionID，无需绑定');
      return loginResult;
    }
    
    // 2. 生成二维码
    wx.showLoading({ title: '生成二维码中...' });
    const qrcodeUrl = await generateOfficialQRCode(miniOpenid);
    wx.hideLoading();
    
    // 3. 展示二维码（需要在页面中实现）
    // 示例：跳转到二维码展示页面
    wx.navigateTo({
      url: `/pages/bind-official/index?qrcode=${encodeURIComponent(qrcodeUrl)}&openid=${miniOpenid}`
    });
    
    return { qrcodeUrl, miniOpenid };
    
  } catch (error) {
    wx.hideLoading();
    wx.showToast({
      title: error.message || '操作失败',
      icon: 'none'
    });
    throw error;
  }
}

/**
 * 轮询检查绑定状态
 * 使用场景：在二维码展示页面，等待用户扫码关注
 * @param {string} miniOpenid 小程序 openid
 * @param {function} onSuccess 绑定成功回调
 * @param {number} maxAttempts 最大尝试次数
 */
function pollBindingStatus(miniOpenid, onSuccess, maxAttempts = 60) {
  let attempts = 0;
  
  const poll = async () => {
    try {
      attempts++;
      
      const status = await checkBindingStatus(miniOpenid);
      
      if (status.isBound && status.isSubscribed && status.unionid) {
        // 绑定成功
        console.log('绑定成功！UnionID:', status.unionid);
        wx.setStorageSync('unionid', status.unionid);
        
        if (onSuccess) {
          onSuccess(status);
        }
        
        return true;
      }
      
      // 继续轮询
      if (attempts < maxAttempts) {
        setTimeout(poll, 2000); // 每 2 秒检查一次
      } else {
        console.warn('轮询超时，用户可能未关注');
        wx.showToast({
          title: '等待超时，请确认已关注公众号',
          icon: 'none'
        });
      }
      
    } catch (error) {
      console.error('检查绑定状态失败:', error);
      
      // 继续轮询
      if (attempts < maxAttempts) {
        setTimeout(poll, 2000);
      }
    }
  };
  
  poll();
}

// ============================================
// 方案三：公众号 OAuth 授权
// ============================================

/**
 * 获取公众号 OAuth 授权链接
 * @param {string} miniOpenid 小程序 openid
 */
function getOAuthUrl(miniOpenid) {
  return new Promise((resolve, reject) => {
    wx.request({
      url: `${API_BASE_URL}/api/wechat/official/bind-auth-url`,
      method: 'GET',
      data: {
        mini_openid: miniOpenid
      },
      success: (res) => {
        if (res.data.success) {
          resolve(res.data.auth_url);
        } else {
          reject(new Error(res.data.message || '获取授权链接失败'));
        }
      },
      fail: reject
    });
  });
}

/**
 * 通过 OAuth 授权绑定
 * 使用场景：需要跳转到浏览器进行授权
 */
async function bindViaOAuth() {
  try {
    // 1. 先登录获取 openid
    const loginResult = await loginWithUnionid();
    const miniOpenid = loginResult.openid;
    
    // 如果已经有 unionid，无需绑定
    if (loginResult.unionid) {
      console.log('已有 UnionID，无需绑定');
      return loginResult;
    }
    
    // 2. 获取授权链接
    wx.showLoading({ title: '获取授权链接...' });
    const authUrl = await getOAuthUrl(miniOpenid);
    wx.hideLoading();
    
    // 3. 复制链接到剪贴板
    wx.setClipboardData({
      data: authUrl,
      success: () => {
        wx.showModal({
          title: '授权提示',
          content: '授权链接已复制，请在浏览器中打开进行授权',
          confirmText: '我知道了'
        });
      }
    });
    
    return { authUrl, miniOpenid };
    
  } catch (error) {
    wx.hideLoading();
    wx.showToast({
      title: error.message || '操作失败',
      icon: 'none'
    });
    throw error;
  }
}

// ============================================
// 页面示例：二维码展示页面
// ============================================

/**
 * 二维码展示页面示例
 * 文件路径：pages/bind-official/index.js
 */
const QRCodePageExample = {
  data: {
    qrcodeUrl: '',
    miniOpenid: '',
    isBinding: false
  },
  
  onLoad(options) {
    this.setData({
      qrcodeUrl: decodeURIComponent(options.qrcode),
      miniOpenid: options.openid
    });
    
    // 开始轮询
    this.startPolling();
  },
  
  startPolling() {
    this.setData({ isBinding: true });
    
    pollBindingStatus(
      this.data.miniOpenid,
      (status) => {
        // 绑定成功
        wx.showToast({
          title: '绑定成功！',
          icon: 'success'
        });
        
        setTimeout(() => {
          wx.navigateBack();
        }, 1500);
      }
    );
  },
  
  onUnload() {
    // 页面卸载时停止轮询
    this.setData({ isBinding: false });
  }
};

/**
 * 二维码展示页面 WXML 示例
 * 文件路径：pages/bind-official/index.wxml
 */
const WXMLExample = `
<view class="container">
  <view class="title">关注公众号获取更多服务</view>
  
  <view class="qrcode-wrapper">
    <image class="qrcode" src="{{qrcodeUrl}}" mode="aspectFit"></image>
  </view>
  
  <view class="tips">
    <text>请长按识别二维码关注公众号</text>
    <text class="sub-tips">关注后即可接收消息通知</text>
  </view>
  
  <view class="status" wx:if="{{isBinding}}">
    <text>等待关注中...</text>
    <view class="loading"></view>
  </view>
</view>
`;

// ============================================
// 使用示例
// ============================================

/**
 * 在 app.js 中的使用示例
 */
const AppExample = {
  onLaunch() {
    // 小程序启动时尝试登录
    this.autoLogin();
  },
  
  async autoLogin() {
    try {
      const result = await loginWithUnionid();
      
      if (result.unionid) {
        console.log('登录成功，已获取 UnionID');
        // 继续业务逻辑
      } else {
        console.log('登录成功，但未获取 UnionID');
        // 可以在合适的时机引导用户关注公众号
        // 例如：在个人中心页面显示"关注公众号"按钮
      }
      
    } catch (error) {
      console.error('自动登录失败:', error);
    }
  }
};

/**
 * 在个人中心页面的使用示例
 */
const ProfilePageExample = {
  data: {
    hasUnionid: false
  },
  
  onLoad() {
    // 检查是否有 unionid
    const unionid = wx.getStorageSync('unionid');
    this.setData({
      hasUnionid: !!unionid
    });
  },
  
  // 点击"关注公众号"按钮
  async onBindOfficial() {
    try {
      await bindOfficialAccount();
    } catch (error) {
      console.error('绑定失败:', error);
    }
  }
};

// ============================================
// 导出方法
// ============================================

module.exports = {
  // 方案一：直接登录
  loginWithUnionid,
  
  // 方案二：公众号扫码
  generateOfficialQRCode,
  checkBindingStatus,
  bindOfficialAccount,
  pollBindingStatus,
  
  // 方案三：OAuth 授权
  getOAuthUrl,
  bindViaOAuth,
  
  // 示例
  QRCodePageExample,
  AppExample,
  ProfilePageExample
};

// ============================================
// 注意事项
// ============================================

/**
 * 1. 开放平台绑定（推荐）
 *    - 完成绑定后，loginWithUnionid() 会自动返回 unionid
 *    - 无需额外开发，用户体验最好
 * 
 * 2. 公众号扫码绑定
 *    - 适合暂时无法完成开放平台绑定的情况
 *    - 需要引导用户关注公众号
 *    - 使用 bindOfficialAccount() 和 pollBindingStatus()
 * 
 * 3. OAuth 授权
 *    - 需要跳转到浏览器
 *    - 用户体验较差，不推荐
 *    - 使用 bindViaOAuth()
 * 
 * 4. 错误处理
 *    - 所有方法都返回 Promise
 *    - 使用 try-catch 捕获错误
 *    - 根据错误信息给用户友好提示
 * 
 * 5. 数据存储
 *    - openid 和 token 必须保存
 *    - unionid 获取后也要保存
 *    - 使用 wx.setStorageSync() 持久化
 */
