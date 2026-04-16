(function () {
  'use strict';

  const MODULE_LABELS = {
    1: '基础学习',
    2: '补课与师生',
    3: '考试与成绩',
    4: '生活心态与习惯'
  };

  /** 正常匹配时结果区灰色说明文案（「维度越接近」在页面上单独缩小显示） */
  const DEFAULT_RESULT_SUB = '维度越接近，画像越符合；仅供娱乐参考。';

  /**
   * 微信小程序家长端（与 tjiajiao91-main/微信小程序/预约家教小程序/manifest.json 中 mp-weixin.appid 一致）
   * - 家长端首页：pages/parent-home/index
   * - 免费匹配家教（step-booking）：pages/step-booking/index — 开放标签与 Scheme 默认打开此页
   * mpOriginalId：小程序「设置 → 基本设置」中的原始 ID（gh_ 开头），wx-open-launch-weapp 必填
   * urlLink：可选，站外 HTTPS 短链拉起（需公众平台生成）
   * envVersion：留空则仅 appid+path（与你给的 Scheme 一致）；需体验版时填 trial
   */
  const WECHAT_MP_LAUNCH = {
    appId: 'wx60a1da7e69927942',
    path: 'pages/step-booking/index',
    urlLink: '',
    mpOriginalId: 'gh_42af177f6ff9',
    envVersion: ''
  };

  function splitMatchTeacherItems(raw) {
    if (!raw || !String(raw).trim()) return [];
    return String(raw)
      .split('、')
      .map((t) => t.trim())
      .filter(Boolean);
  }

  function splitRecommendCourseItems(raw) {
    if (!raw || !String(raw).trim()) return [];
    return String(raw)
      .split(/\s*\+\s*/)
      .map((t) => t.trim())
      .filter(Boolean);
  }

  function fillRecTagContainer(el, items) {
    if (!el) return;
    const nodes = items.map((text) => {
      const span = document.createElement('span');
      span.className = 'rec-tag';
      span.textContent = text;
      return span;
    });
    el.replaceChildren(...nodes);
  }

  function openWechatMiniProgram() {
    const { appId, path, urlLink } = WECHAT_MP_LAUNCH;
    if (urlLink && /^https?:\/\//i.test(String(urlLink).trim())) {
      window.location.href = String(urlLink).trim();
      return;
    }
    const cleanPath = String(path || '').replace(/^\//, '');
    if (!appId || !cleanPath) return;

    const inWeChat = /MicroMessenger/i.test(navigator.userAgent || '');
    if (inWeChat) {
      const tip = document.getElementById('wxInWechatSchemeTip');
      if (tip) tip.classList.add('is-visible');
      const host = document.getElementById('wxOpenLaunchHost');
      if (host && !host.hasAttribute('hidden')) {
        host.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
      return;
    }

    // 微信外（系统浏览器等）：官方明文格式含 query 参数位
    let href =
      'weixin://dl/business/?appid=' +
      encodeURIComponent(appId) +
      '&path=' +
      encodeURIComponent(cleanPath) +
      '&query=' +
      encodeURIComponent('');
    const env = String(WECHAT_MP_LAUNCH.envVersion || '').trim();
    if (env) {
      href += '&env_version=' + encodeURIComponent(env);
    }
    window.location.href = href;
  }

  const dimensionMeta = {
    S1: { name: 'S1 自尊自信', model: '自我' },
    S2: { name: 'S2 自我清晰度', model: '自我' },
    S3: { name: 'S3 核心价值', model: '自我' },
    E1: { name: 'E1 依恋安全感', model: '情感' },
    E2: { name: 'E2 情感投入度', model: '情感' },
    E3: { name: 'E3 边界与依赖', model: '情感' },
    A1: { name: 'A1 世界观倾向', model: '态度' },
    A2: { name: 'A2 规则灵活度', model: '态度' }
  };

  const DIM_EXPLANATIONS = {
    S1: {
      L: '容易自我怀疑，总担心「我不够好」。',
      M: '自信会随情境波动，有时行有时怂。',
      H: '对自己大致有数，不太被一句话带崩。'
    },
    S2: {
      L: '心里容易乱，说不清自己到底要什么。',
      M: '多数时候认识自己，情绪上来会糊一下。',
      H: '对自己的状态、底线相对清楚。'
    },
    S3: {
      L: '更想歇着、避险，不太想硬扛目标。',
      M: '想变好也想躺，价值排序常打架。',
      H: '容易被目标、进步感或信念推着走。'
    },
    E1: {
      L: '怕批评、怕被嫌弃，安全感警报较灵敏。',
      M: '信任与担心会拉锯，看场合。',
      H: '在关系里更稳，不容易被风吹草动吓到。'
    },
    E2: {
      L: '情绪收着，投入相对克制。',
      M: '会认真，但会给自己留余地。',
      H: '感受来得真、来得深，容易共情也容易累。'
    },
    E3: {
      L: '更独立、不粘人，边界清晰，不太靠别人兜底。',
      M: '亲密和独立都想要一点。',
      H: '更依赖陪伴与照顾，希望有人一起扛、一起商量。'
    },
    A1: {
      L: '容易往压力或悲观那边想。',
      M: '不天真也不彻底丧，边走边看。',
      H: '更愿意相信努力与世界没那么糟。'
    },
    A2: {
      L: '怕犯错、怕规则，变通会紧张。',
      M: '该守守，该灵活也能弯一下。',
      H: '更随性、更敢打破死板条条框框。'
    }
  };

  const QUESTIONS_PER_MODULE = 5;
  const MATCH_FALLBACK_PCT = 60;
  const MAX_MANHATTAN = 16;

  /**
   * 题库里同一维【Sx】既标在「自信」也标在「自卑」上，不能再用「选中次数=该维越高」。
   * 按选项文案关键词判方向：+1 表示该答案在 25 型表里偏向该维的 H 端，-1 偏向 L 端。
   * 与 TYPE_PATTERNS 一致（如 E3：H=依赖陪伴，L=独立少依）。
   */
  const DIM_POLARITY_RULES = {
    S1: [
      [/自卑|不自信|自我否定|很笨|没用|怕被说|不敢说|比不上|丢人|不淡定|坚持不住|太笨|自我施压|责怪自己|不够专注|不够努力|不专心|慌张|粗心.*糟糕|偷懒就是不学|完不成/, -1],
      [/相信自己|有信心|能做好|能完成|越来越好|积极改正|克制|没问题|重新安排|慢慢做完|会越来越好|作业没问题|对自己有信心/, 1]
    ],
    S2: [
      [/不知道|迷茫|不清楚|不确定|该不该|混乱|不知怎么|心里乱/, -1],
      [/清楚|知道自己|知道哪|知道.*没跟上|知道.*尽力|知道.*静不下|知道.*还差|知道.*进步|知道.*没学会|知道.*没懂|知道.*弱/, 1]
    ],
    S3: [
      [/不想|没用|没动力|逃避|懒得|勉强|摆烂|不想学|不想坚持|不想深究|不想听|不想写/, -1],
      [/目标|好胜|证明|上进|要赢|坚持|有要求|想努力|有野心|想变好/, 1]
    ],
    E1: [
      [/不怕|内心安稳|能接受批评|心平|安稳.*评价|信任/, 1],
      [/害怕|怕批评|心里不安|怕被|怕困难|怕放弃|紧张|心里怕|有点怕|特别怕/, -1]
    ],
    E2: [
      [/平淡|无所谓对错|淡漠|不悲不喜|情绪稳.*歇|不太外露/, -1],
      [/委屈|敏感|愧疚|受伤|崩溃|烦躁|容易受影响|情绪.*波动|心里愧疚/, 1]
    ],
    E3: [
      [/独立|不依赖|不麻烦|有边界|有分寸|独自|自己学|不模仿|懂事.*不依赖/, -1],
      [/依赖|希望有人|要安慰|特别依赖|希望.*陪|希望.*允许|希望得到|帮你|家长付出/, 1]
    ],
    A1: [
      [/悲观|消极|烦|压力|残酷|被误解|态度消极|不想学|痛苦|差劲|太累/, -1],
      [/乐观|温暖|相信|好坏都接受|一步一步|看淡|平常心|轻松|有乐|愿意听话/, 1]
    ],
    A2: [
      [/遵守|不敢|严格听话|不敢反抗|不敢随便|不敢再犯|认真听|不敢提问|不敢停/, -1],
      [/灵活|随便|不硬|不被.*绑|摸鱼|跑出去|顺势|硬扛|应付|随性/, 1]
    ]
  };

  function polarityFromLabel(label, dim) {
    const rules = DIM_POLARITY_RULES[dim];
    if (!rules) return 0;
    for (let i = 0; i < rules.length; i++) {
      const [re, sign] = rules[i];
      if (re.test(label)) return sign;
    }
    return 0;
  }

  /** 无关键词命中时的回退：按次数分档（旧逻辑，略偏） */
  function countToLevel(c) {
    if (c <= 2) return 'L';
    if (c <= 4) return 'M';
    return 'H';
  }

  function signedSumToLevel(sum, hadNonZeroPolarity) {
    if (!hadNonZeroPolarity) return null;
    if (sum >= 1) return 'H';
    if (sum <= -1) return 'L';
    return 'M';
  }

  function levelNum(level) {
    return { L: 1, M: 2, H: 3 }[level];
  }

  function shuffle(array) {
    const arr = [...array];
    for (let i = arr.length - 1; i > 0; i--) {
      const j = Math.floor(Math.random() * (i + 1));
      [arr[i], arr[j]] = [arr[j], arr[i]];
    }
    return arr;
  }

  /**
   * 相对 index.html → 绝对 URL。
   * 仅对「含空格等」的路径段做 encodeURIComponent；纯中文文件名整段编码在部分微信/WebView + Nginx 下易 404，触发 onerror 后图会消失。
   */
  function resolveAssetUrl(relativePath) {
    if (!relativePath || typeof relativePath !== 'string') return '';
    try {
      const base = document.documentURI || window.location.href;
      const parts = relativePath.split('/').filter((seg) => seg.length > 0);
      const pathPart = parts
        .map((seg) => (/\s/.test(seg) ? encodeURIComponent(seg) : seg))
        .join('/');
      return new URL(pathPart, base).href;
    } catch (e) {
      return relativePath;
    }
  }

  function isFileProtocol() {
    return typeof window !== 'undefined' && window.location.protocol === 'file:';
  }

  /**
   * file:// 下将本地文件画入 canvas 再 toDataURL 会触发安全限制，整张贴图无法导出。
   * 无法转成 data: 的图片改为占位块，让 html2canvas 得到未污染的、可 toDataURL 的画布。
   */
  async function inlineCaptureImagesForFilePages(root) {
    if (!isFileProtocol()) return;
    const imgs = Array.from(root.querySelectorAll('img'));
    for (const img of imgs) {
      const src = (img.getAttribute('src') || '').trim();
      if (!src) continue;
      if (/^data:/i.test(src)) continue;
      const data = await rasterSrcToDataUrl(src);
      if (data) {
        img.src = data;
        continue;
      }
      const inQr = !!img.closest('.poster-capture-qr');
      const ph = document.createElement('div');
      ph.className = inQr ? 'poster-capture-qr-fallback' : 'poster-capture-img-missing';
      ph.textContent = inQr
        ? '本地打开无法嵌入二维码，用 http 访问后即可显示'
        : '本地打开无法嵌入配图，用 http 访问后即可显示';
      img.replaceWith(ph);
    }
  }

  function getTypeImagesMap() {
    if (typeof window !== 'undefined' && window.TYPE_IMAGES) return window.TYPE_IMAGES;
    if (typeof TYPE_IMAGES !== 'undefined') return TYPE_IMAGES;
    return {};
  }

  function getTypeExtraMap() {
    if (typeof window !== 'undefined' && window.TYPE_EXTRA) return window.TYPE_EXTRA;
    if (typeof TYPE_EXTRA !== 'undefined') return TYPE_EXTRA;
    return {};
  }

  /** 每模块随机抽 5 题，整卷按模块 1→4 连续展示；模块内按原题号升序，便于阅读 */
  function pickSessionQuestions() {
    const ordered = [];
    for (let m = 1; m <= 4; m++) {
      const pool = QUESTION_BANK.filter((q) => q.module === m);
      const five = shuffle(pool).slice(0, QUESTIONS_PER_MODULE);
      five.sort((a, b) => a.num - b.num);
      ordered.push(...five);
    }
    return ordered;
  }

  const app = {
    activeQuestions: [],
    answers: {},
    previewMode: false
  };

  const screens = {
    intro: document.getElementById('intro'),
    test: document.getElementById('test'),
    result: document.getElementById('result')
  };

  const questionList = document.getElementById('questionList');
  const progressBar = document.getElementById('progressBar');
  const progressText = document.getElementById('progressText');
  const submitBtn = document.getElementById('submitBtn');
  const testHint = document.getElementById('testHint');

  function showScreen(name) {
    Object.entries(screens).forEach(([key, el]) => {
      el.classList.toggle('active', key === name);
    });
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function getQuestionMetaLabel(q) {
    const mod = MODULE_LABELS[q.module] || '情境题';
    if (app.previewMode) return `${mod}（模块 ${q.module}）`;
    return mod;
  }

  function renderQuestions() {
    const list = app.activeQuestions;
    questionList.innerHTML = '';
    list.forEach((q, index) => {
      const card = document.createElement('article');
      card.className = 'question';
      card.innerHTML = `
          <div class="question-meta">
            <div class="badge">第 ${index + 1} 题</div>
            <div>${getQuestionMetaLabel(q)}</div>
          </div>
          <div class="question-title">${q.text}</div>
          <div class="options">
            ${q.options
              .map((opt, i) => {
                const code = ['A', 'B', 'C', 'D'][i] || String(i + 1);
                const checked = app.answers[q.id] === opt.value ? 'checked' : '';
                return `
                <label class="option">
                  <input type="radio" name="${q.id}" value="${opt.value}" ${checked} />
                  <div class="option-code">${code}</div>
                  <div>${opt.label}</div>
                </label>
              `;
              })
              .join('')}
          </div>
        `;
      questionList.appendChild(card);
    });

    questionList.querySelectorAll('input[type="radio"]').forEach((input) => {
      input.addEventListener('change', (e) => {
        const { name, value } = e.target;
        app.answers[name] = Number(value);
        updateProgress();
      });
    });

    updateProgress();
  }

  function updateProgress() {
    const total = app.activeQuestions.length;
    const done = app.activeQuestions.filter(
      (q) => app.answers[q.id] !== undefined
    ).length;
    const percent = total ? (done / total) * 100 : 0;
    progressBar.style.width = `${percent}%`;
    progressText.textContent = `${done} / ${total}`;
    const complete = done === total && total > 0;
    submitBtn.disabled = !complete;
    testHint.textContent = complete
      ? '都做完了，提交看看你是哪一款「学习人格」。'
      : '共20道题，全选完才会放行。世界已经够乱了，起码把题做完整。';
  }

  function computeResult() {
    const counts = { S1: 0, S2: 0, S3: 0, E1: 0, E2: 0, E3: 0, A1: 0, A2: 0 };
    const polaritySum = { S1: 0, S2: 0, S3: 0, E1: 0, E2: 0, E3: 0, A1: 0, A2: 0 };
    const hadPolarityHit = { S1: false, S2: false, S3: false, E1: false, E2: false, E3: false, A1: false, A2: false };

    app.activeQuestions.forEach((q) => {
      const v = app.answers[q.id];
      if (v === undefined) return;
      const opt = q.options.find((o) => o.value === v);
      if (!opt || counts[opt.dim] === undefined) return;
      const dim = opt.dim;
      counts[dim] += 1;
      const p = polarityFromLabel(opt.label, dim);
      polaritySum[dim] += p;
      if (p !== 0) hadPolarityHit[dim] = true;
    });

    const levels = {};
    DIMENSION_ORDER.forEach((dim) => {
      const fromSign = signedSumToLevel(polaritySum[dim], hadPolarityHit[dim]);
      levels[dim] = fromSign !== null ? fromSign : countToLevel(counts[dim]);
    });

    const userVector = DIMENSION_ORDER.map((dim) => levelNum(levels[dim]));

    const ranked = TYPE_PATTERNS.map((t) => {
      const vec = t.pattern.split('').map((ch) => levelNum(ch));
      let distance = 0;
      let exact = 0;
      for (let i = 0; i < 8; i++) {
        const diff = Math.abs(userVector[i] - vec[i]);
        distance += diff;
        if (diff === 0) exact += 1;
      }
      const similarity = Math.max(
        0,
        Math.round((1 - distance / MAX_MANHATTAN) * 100)
      );
      const lib = TYPE_LIBRARY[t.code];
      return {
        ...t,
        cn: lib.cn,
        intro: lib.intro,
        desc: lib.desc,
        distance,
        exact,
        similarity
      };
    }).sort((a, b) => {
      if (a.distance !== b.distance) return a.distance - b.distance;
      if (b.exact !== a.exact) return b.exact - a.exact;
      return b.similarity - a.similarity;
    });

    const bestNormal = ranked[0];
    let finalType;
    let modeKicker = '您家孩子性格';
    let badge = `匹配度 ${bestNormal.similarity}% · 命中 ${bestNormal.exact}/8 维`;
    let sub = DEFAULT_RESULT_SUB;
    let special = false;

    if (bestNormal.similarity < MATCH_FALLBACK_PCT) {
      finalType = { ...TYPE_LIBRARY.HHHH };
      modeKicker = '系统强制兜底';
      badge = `标准库最高匹配仅 ${bestNormal.similarity}%（${bestNormal.cn}）`;
      sub = '你的选项组合太「清流」，先送你当傻乐者——开心最重要。';
      special = true;
    } else {
      finalType = { ...TYPE_LIBRARY[bestNormal.code] };
    }

    return {
      counts,
      levels,
      ranked,
      bestNormal,
      finalType,
      modeKicker,
      badge,
      sub,
      special
    };
  }

  function renderDimList(result) {
    const dimList = document.getElementById('dimList');
    const sortedDims = [...DIMENSION_ORDER].sort((a, b) => {
      const ca = result.counts[a] || 0;
      const cb = result.counts[b] || 0;
      if (cb !== ca) return cb - ca;
      return DIMENSION_ORDER.indexOf(a) - DIMENSION_ORDER.indexOf(b);
    });
    dimList.innerHTML = sortedDims.map((dim) => {
      const level = result.levels[dim];
      const explanation = DIM_EXPLANATIONS[dim][level];
      const c = result.counts[dim];
      return `
          <div class="dim-item">
            <div class="dim-item-top">
              <div class="dim-item-name">${dimensionMeta[dim].name}</div>
              <div class="dim-item-score">${level} · 选中 ${c} 次</div>
            </div>
            <p>${explanation}</p>
          </div>
        `;
    }).join('');
  }

  function renderResult() {
    const result = computeResult();
    const type = result.finalType;
    const extra = getTypeExtraMap()[type.code] || {};

    document.getElementById('resultModeKicker').textContent = result.modeKicker;
    document.getElementById('resultTypeName').textContent = `${type.code}（${type.cn}）`;
    document.getElementById('matchBadge').textContent = result.badge;
    const promoEl = document.getElementById('promoText');
    if (promoEl) {
      promoEl.textContent = extra.promoText || '';
      promoEl.style.display = extra.promoText ? '' : 'none';
    }
    const subEl = document.getElementById('resultTypeSub');
    if (subEl) subEl.textContent = result.sub;
    document.getElementById('resultDesc').textContent = type.desc;
    document.getElementById('posterCaption').textContent = type.intro;
    document.getElementById('funNote').textContent = result.special
      ? '本测试仅供娱乐，傻乐者不代表你真是「没性格」，不能替代心理咨询或诊断。'
      : '本测试仅供娱乐，围绕作业、补课、考试等学习情境设计，不能替代心理咨询、诊断或教育决策。笑一笑就好。';

    const posterBox = document.getElementById('posterBox');
    const posterImage = document.getElementById('posterImage');
    const gallery = getTypeImagesMap();
    const rawPath = gallery[type.code] || '';
    const imgUrl = rawPath ? resolveAssetUrl(rawPath) : '';
    posterImage.onerror = null;
    if (imgUrl) {
      posterBox.classList.remove('no-image');
      posterImage.alt = `${type.code}（${type.cn}）`;
      posterImage.onerror = function onPosterError() {
        posterImage.onerror = null;
        posterBox.classList.add('no-image');
        posterImage.removeAttribute('src');
      };
      posterImage.src = imgUrl;
    } else {
      posterBox.classList.add('no-image');
      posterImage.removeAttribute('src');
      posterImage.alt = '';
    }

    renderDimList(result);

    const teacherEl = document.getElementById('matchTeacher');
    const courseEl = document.getElementById('recommendCourse');
    const recGrid = document.getElementById('recGrid');
    if (teacherEl) fillRecTagContainer(teacherEl, splitMatchTeacherItems(extra.matchTeacher));
    if (courseEl) fillRecTagContainer(courseEl, splitRecommendCourseItems(extra.recommendCourse));
    if (recGrid) {
      const hasAny = !!(extra.matchTeacher || extra.recommendCourse);
      recGrid.style.display = hasAny ? '' : 'none';
    }

    showScreen('result');
  }

  function setModalOpen(open) {
    const modal = document.getElementById('posterModal');
    if (!modal) return;
    modal.classList.toggle('active', !!open);
    modal.setAttribute('aria-hidden', open ? 'false' : 'true');
    const sticky = document.getElementById('posterStickyBar');
    if (sticky) sticky.classList.toggle('is-behind-modal', !!open);
    if (!open) {
      const hint = document.getElementById('posterLocalFileHint');
      if (hint) hint.hidden = true;
      const prev = document.getElementById('posterPreviewImg');
      if (prev) {
        prev.removeAttribute('src');
        prev.classList.remove('is-ready');
      }
    }
  }

  function waitPosterImages(root) {
    const imgs = Array.from(root.querySelectorAll('img'));
    return Promise.all(
      imgs.map(
        (img) =>
          new Promise((resolve) => {
            if (img.complete && img.naturalWidth) return resolve();
            const done = () => resolve();
            img.onload = done;
            img.onerror = done;
            setTimeout(done, 5000);
          })
      )
    );
  }

  /**
   * 将图片画入 canvas 再导出 data URL，供 html2canvas 稳定绘制（避免 img 上 crossOrigin 触发 CORS 后整图无法导出）。
   */
  function rasterSrcToDataUrl(src) {
    return new Promise((resolve) => {
      if (!src) {
        resolve('');
        return;
      }
      const img = new Image();
      img.onload = () => {
        try {
          const w = img.naturalWidth;
          const h = img.naturalHeight;
          if (!w || !h) {
            resolve('');
            return;
          }
          const c = document.createElement('canvas');
          c.width = w;
          c.height = h;
          const x = c.getContext('2d');
          if (!x) {
            resolve('');
            return;
          }
          x.drawImage(img, 0, 0);
          resolve(c.toDataURL('image/jpeg', 0.92));
        } catch (e) {
          resolve('');
        }
      };
      img.onerror = () => resolve('');
      img.src = src;
    });
  }

  /**
   * 海报 = 结果卡片 DOM 的截图（略窄宽度 ≈ 缩小版页面）+ 底部公众号二维码。
   */
  async function generatePoster() {
    const canvas = document.getElementById('posterCanvas');
    if (!canvas) return null;
    const ctx = canvas.getContext('2d');
    if (!ctx) return null;

    if (typeof html2canvas !== 'function') {
      alert('海报组件未加载，请检查网络后刷新页面。');
      return null;
    }

    const host = document.getElementById('posterCaptureHost');
    const sourceWrap = document.querySelector('#result .result-wrap');
    if (!host || !sourceWrap) return null;

    host.innerHTML = '';
    const wrap = sourceWrap.cloneNode(true);

    wrap.querySelector('details.author-box')?.remove();
    wrap.querySelector('.dim-box')?.remove();
    wrap.querySelector('.result-actions')?.remove();
    wrap.querySelectorAll('[id]').forEach((el) => el.removeAttribute('id'));

    const layout = wrap.querySelector('.result-layout');
    if (!layout) {
      host.innerHTML = '';
      return null;
    }

    const qrWrap = document.createElement('div');
    qrWrap.className = 'poster-capture-qr';
    const qrSrc = resolveAssetUrl('assets/types/91家教公众号二维码.jpg');
    let qrImgSrc = await rasterSrcToDataUrl(qrSrc);
    if (!qrImgSrc) qrImgSrc = qrSrc;
    const cap = document.createElement('p');
    cap.className = 'poster-capture-qr-cap';
    cap.textContent = '扫码关注 · 获取更多学习方案';
    const qrImg = document.createElement('img');
    qrImg.alt = '91家教公众号二维码';
    qrImg.src = qrImgSrc;
    qrWrap.appendChild(cap);
    qrWrap.appendChild(qrImg);
    layout.appendChild(qrWrap);

    wrap.style.marginTop = '0';
    host.appendChild(wrap);

    await inlineCaptureImagesForFilePages(wrap);

    if (document.fonts && document.fonts.ready) {
      try {
        await document.fonts.ready;
      } catch (_) {
        /* ignore */
      }
    }

    await waitPosterImages(wrap);

    let snap;
    try {
      snap = await html2canvas(wrap, {
        scale: 1.5,
        useCORS: !isFileProtocol(),
        allowTaint: false,
        backgroundColor: '#f6faf6',
        logging: false,
        foreignObjectRendering: false
      });
    } catch (e) {
      console.error(e);
      host.innerHTML = '';
      alert('生成海报失败，请稍后再试。');
      return null;
    }

    host.innerHTML = '';

    if (!snap.width || !snap.height) {
      alert('截图尺寸异常，请关闭后重试「生成海报」。');
      return null;
    }

    canvas.width = snap.width;
    canvas.height = snap.height;
    ctx.drawImage(snap, 0, 0);

    const preview = document.getElementById('posterPreviewImg');
    const hintEl = document.getElementById('posterLocalFileHint');
    if (preview) {
      preview.removeAttribute('src');
      preview.classList.remove('is-ready');
      try {
        // 微信内置浏览器对 blob: 预览常空白；JPEG data URL 体积更小、兼容更好
        preview.src = canvas.toDataURL('image/jpeg', 0.88);
        preview.classList.add('is-ready');
        if (hintEl) hintEl.hidden = !isFileProtocol();
      } catch (e) {
        try {
          preview.src = canvas.toDataURL('image/png');
          preview.classList.add('is-ready');
          if (hintEl) hintEl.hidden = !isFileProtocol();
        } catch (e2) {
          preview.removeAttribute('src');
          preview.classList.remove('is-ready');
          if (hintEl) hintEl.hidden = false;
          if (isFileProtocol()) {
            alert(
              '当前是本地文件（file://）打开，浏览器仍不允许导出画布。请在本目录用 HTTP 访问后再生成海报（见弹窗内黄色说明）。'
            );
          }
        }
      }
    }

    return canvas;
  }

  async function openPosterModal() {
    const prev = document.getElementById('posterPreviewImg');
    if (prev) {
      prev.removeAttribute('src');
      prev.classList.remove('is-ready');
    }
    setModalOpen(true);
    const btn = document.getElementById('posterBtn');
    if (btn) btn.disabled = true;
    try {
      await generatePoster();
    } finally {
      if (btn) btn.disabled = false;
    }
  }

  function downloadPoster() {
    const canvas = document.getElementById('posterCanvas');
    if (!canvas) return;
    const trigger = (href) => {
      const a = document.createElement('a');
      a.download = 'SBTI学习人格-海报.png';
      a.href = href;
      document.body.appendChild(a);
      a.click();
      a.remove();
    };
    try {
      canvas.toBlob((blob) => {
        if (!blob) {
          try {
            trigger(canvas.toDataURL('image/png'));
          } catch (_) {
            /* ignore */
          }
          return;
        }
        const url = URL.createObjectURL(blob);
        trigger(url);
        setTimeout(() => {
          try {
            URL.revokeObjectURL(url);
          } catch (_) {
            /* ignore */
          }
        }, 4000);
      }, 'image/png');
    } catch (_) {
      try {
        trigger(canvas.toDataURL('image/png'));
      } catch (e2) {
        /* ignore */
      }
    }
  }

  function startTest(preview) {
    app.previewMode = !!preview;
    app.answers = {};
    app.activeQuestions = pickSessionQuestions();
    if (app.activeQuestions.length !== 20) {
      console.error('Expected 20 questions, got', app.activeQuestions.length);
    }
    renderQuestions();
    showScreen('test');
  }

  document.getElementById('startBtn').addEventListener('click', () => startTest(false));
  document.getElementById('backIntroBtn').addEventListener('click', () => showScreen('intro'));
  document.getElementById('submitBtn').addEventListener('click', renderResult);
  document.getElementById('restartBtn').addEventListener('click', () => startTest(false));
  document.getElementById('toTopBtn').addEventListener('click', () => showScreen('intro'));

  const posterBtn = document.getElementById('posterBtn');
  if (posterBtn) posterBtn.addEventListener('click', openPosterModal);
  const matchTutorBtn = document.getElementById('matchTutorBtn');
  if (matchTutorBtn) matchTutorBtn.addEventListener('click', openWechatMiniProgram);
  const posterCloseBtn = document.getElementById('posterCloseBtn');
  if (posterCloseBtn) posterCloseBtn.addEventListener('click', () => setModalOpen(false));
  const posterDownloadBtn = document.getElementById('posterDownloadBtn');
  if (posterDownloadBtn) posterDownloadBtn.addEventListener('click', downloadPoster);
  const posterModal = document.getElementById('posterModal');
  if (posterModal) {
    posterModal.addEventListener('click', (e) => {
      const t = e.target;
      if (t && t.getAttribute && t.getAttribute('data-close') === '1') setModalOpen(false);
    });
  }

  const JWEIXIN_SRC = 'https://res.wx.qq.com/open/js/jweixin-1.6.0.js';

  function ensureJweixin() {
    return new Promise((resolve, reject) => {
      if (typeof wx !== 'undefined' && typeof wx.config === 'function') {
        resolve();
        return;
      }
      if (document.querySelector(`script[src="${JWEIXIN_SRC}"]`)) {
        const t0 = Date.now();
        const tick = () => {
          if (typeof wx !== 'undefined' && typeof wx.config === 'function') {
            resolve();
            return;
          }
          if (Date.now() - t0 > 8000) {
            reject(new Error('jweixin timeout'));
            return;
          }
          setTimeout(tick, 50);
        };
        tick();
        return;
      }
      const s = document.createElement('script');
      s.src = JWEIXIN_SRC;
      s.onload = () => resolve();
      s.onerror = () => reject(new Error('jweixin load'));
      document.head.appendChild(s);
    });
  }

  function showWxOpenLaunchUi() {
    const host = document.getElementById('wxOpenLaunchHost');
    if (host) host.removeAttribute('hidden');
    /* 不再隐藏 #matchTutorBtn：开放标签常无可见高度，隐藏后整行按钮会「消失」，由 CSS 叠在备用按钮上方 */
  }

  /** 若页面其它脚本已 wx.config 且含 openTagList，可在 wx.ready 里调用 */
  window.showLearnPersonalityWxOpenLaunch = showWxOpenLaunchUi;

  function resolveJssdkSignPhpHref() {
    try {
      return new URL('wechat_jssdk_sign.php', location.href).href.split('#')[0];
    } catch (e) {
      return 'wechat_jssdk_sign.php';
    }
  }

  async function setupMatchTutorOpenTag() {
    const host = document.getElementById('wxOpenLaunchHost');
    const weapp = document.getElementById('wxOpenLaunchWeapp');
    const fallback = document.getElementById('matchTutorBtn');
    if (!host || !weapp || !fallback) return;

    const mpId = String(WECHAT_MP_LAUNCH.mpOriginalId || '').trim();
    const mpAppId = String(WECHAT_MP_LAUNCH.appId || '').trim();
    const path = String(WECHAT_MP_LAUNCH.path || '').replace(/^\//, '').trim();
    if (mpAppId) weapp.setAttribute('appid', mpAppId);
    if (mpId) weapp.setAttribute('username', mpId);
    if (path) weapp.setAttribute('path', path);

    const isWx = /MicroMessenger/i.test(navigator.userAgent || '');
    if (!isWx || !mpId) return;

    try {
      await ensureJweixin();
    } catch (e) {
      return;
    }

    let cfg = window.WX_JS_SDK_CONFIG;

    if (cfg && cfg.useExternalWx) {
      if (typeof wx !== 'undefined' && wx.ready) wx.ready(() => showWxOpenLaunchUi());
      return;
    }

    const needFetch =
      !cfg ||
      !cfg.appId ||
      cfg.signature == null ||
      cfg.nonceStr == null ||
      cfg.timestamp == null;

    if (needFetch) {
      try {
        const pageUrl = location.href.split('#')[0];
        const signHref = resolveJssdkSignPhpHref();
        const res = await fetch(signHref + '?url=' + encodeURIComponent(pageUrl), {
          credentials: 'same-origin'
        });
        const d = await res.json();
        if (d && d.appId && d.signature != null && d.nonceStr != null && d.timestamp != null) {
          cfg = {
            appId: d.appId,
            timestamp: d.timestamp,
            nonceStr: d.nonceStr,
            signature: d.signature,
            jsApiList: Array.isArray(d.jsApiList) ? d.jsApiList : [],
            debug: !!d.debug
          };
          window.WX_JS_SDK_CONFIG = cfg;
        }
      } catch (e) {
        console.warn('wechat_jssdk_sign fetch', e);
      }
    }

    if (
      !cfg ||
      !cfg.appId ||
      cfg.signature == null ||
      cfg.nonceStr == null ||
      cfg.timestamp == null
    ) {
      return;
    }

    if (typeof wx === 'undefined' || !wx.config) return;

    wx.config({
      debug: !!cfg.debug,
      appId: String(cfg.appId),
      timestamp: Number(cfg.timestamp),
      nonceStr: String(cfg.nonceStr),
      signature: String(cfg.signature),
      jsApiList: Array.isArray(cfg.jsApiList) ? cfg.jsApiList : [],
      openTagList: ['wx-open-launch-weapp']
    });
    wx.ready(() => showWxOpenLaunchUi());
    wx.error((err) => console.warn('wx.config error', err));
  }

  setupMatchTutorOpenTag().catch(() => {});
})();
