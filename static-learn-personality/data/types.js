/* 25 型 · 8 维 L/M/H，顺序 S1 S2 S3 E1 E2 E3 A1 A2 — 来源：25_种孩子人格·8_维度评分（戏谑版·家教专用） */

const DIMENSION_ORDER = ['S1', 'S2', 'S3', 'E1', 'E2', 'E3', 'A1', 'A2'];

/** 人格配图，路径相对 index.html；与 assets/types/ 下文件名一一对应 */
const TYPE_IMAGES = {
  // 注意：这里按你当前上传到 assets/types/ 的“中文+code”文件名映射
  // IMSB 若你没上传图，会自动回退成无图金句卡片
  BOSS: 'assets/types/BOSS（小老板）.png',
  MUM: 'assets/types/妈妈.png',
  FAKE: 'assets/types/伪人.png',
  DEAD: 'assets/types/死者.png',
  ZZZZ: 'assets/types/装死者.png',
  GOGO: 'assets/types/行者.png',
  FUCK: 'assets/types/草者FUCK.png',
  CTRL: 'assets/types/拿捏者.png',
  HHHH: 'assets/types/傻乐者.png',
  SEXY: 'assets/types/优物.png',
  OJBK: 'assets/types/无所谓人.png',
  POOR: 'assets/types/贫穷者.png',
  'OH-NO': 'assets/types/哦不人.png',
  MONK: 'assets/types/憎人.png',
  SHIT: 'assets/types/狗屎人 SHIT.png',
  'THAN-K': 'assets/types/感恩者 THAN-K.png',
  MALO: 'assets/types/吗喽.png',
  'ATM-er': 'assets/types/送钱者.png',
  'THIN-K': 'assets/types/思考者.png',
  SOLO: 'assets/types/孤儿 SOLO.png',
  'LOVE-R': 'assets/types/多情者 LOVE-R.png',
  'WOC!': 'assets/types/稻草人 WOC!.png',
  'DRUN-K': 'assets/types/酒鬼.png',
  IMBW: 'assets/types/废物 IMBW学习.png',
  IMSB: 'assets/types/自我攻击者.png',
};

/* 供 app.js（IIFE 严格模式）稳定读取；勿删 */
if (typeof window !== 'undefined') {
  window.TYPE_IMAGES = TYPE_IMAGES;
  window.DIMENSION_ORDER = DIMENSION_ORDER;
}

/** @type {{ code: string, pattern: string }[]} */
const TYPE_PATTERNS = [
  { code: 'IMSB', pattern: 'LMMLHMML' },
  { code: 'BOSS', pattern: 'HHHMLLMM' },
  { code: 'MUM', pattern: 'LLMHHHHL' },
  { code: 'FAKE', pattern: 'MLMMLMMH' },
  { code: 'DEAD', pattern: 'LMLMLLLH' },
  { code: 'ZZZZ', pattern: 'MLMHMHHH' },
  { code: 'GOGO', pattern: 'MLMMMMLH' },
  { code: 'FUCK', pattern: 'HMMLHLLL' },
  { code: 'CTRL', pattern: 'HHHMLMHM' },
  { code: 'HHHH', pattern: 'HLMHHMHH' },
  { code: 'SEXY', pattern: 'LMMLHMML' },
  { code: 'OJBK', pattern: 'MHLHLLMH' },
  { code: 'POOR', pattern: 'LMMHHMHL' },
  { code: 'OH-NO', pattern: 'LLLLHMLL' },
  { code: 'MONK', pattern: 'MHMHLLHM' },
  { code: 'SHIT', pattern: 'HHMLMLLM' },
  { code: 'THAN-K', pattern: 'MMHHHMHM' },
  { code: 'MALO', pattern: 'HLMHHMHH' },
  { code: 'ATM-er', pattern: 'MLHHHLHM' },
  { code: 'THIN-K', pattern: 'HHHMLLMM' },
  { code: 'SOLO', pattern: 'LMMLHMML' },
  { code: 'LOVE-R', pattern: 'MLMMHHHL' },
  { code: 'WOC!', pattern: 'MLMMHMMM' },
  { code: 'DRUN-K', pattern: 'LMLMMLLH' },
  { code: 'IMBW', pattern: 'LLMLHMLL' }
];

/** 来自《完整文档：25_种人格匹配课程》 */
const TYPE_EXTRA = {
  IMSB: {
    matchTeacher: '温柔鼓励型、耐心陪伴型、低压力型',
    recommendCourse: '自信重建课 + 基础巩固课',
    promoText: '孩子学不好先骂自己，内耗到崩溃？温柔老师不指责、多鼓励，帮他跳出自我否定，重建学习底气，越学越自信！'
  },
  BOSS: {
    matchTeacher: '民主引导型、实力硬核型、不强势型',
    recommendCourse: '目标管理课 + 逻辑提升课',
    promoText: '不服管、爱指挥，吃软不吃硬？不压制、不命令，用实力征服小领导，引导他把掌控欲变成学习动力，越管越出彩！'
  },
  MUM: {
    matchTeacher: '温暖陪伴型、耐心安抚型、像家人型',
    recommendCourse: '陪伴式辅导 + 情绪稳定课',
    promoText: '太粘人、爱撒娇，没人陪就不写作业？温柔老师全程陪伴，给足安全感，让他安心学习，慢慢变得独立自信！'
  },
  FAKE: {
    matchTeacher: '严格盯梢型、真实反馈型、不纵容型',
    recommendCourse: '专注力训练 + 真实效率课',
    promoText: '看似努力，实则磨洋工、装样子？严格不纵容，老师教他真方法，告别假努力，高效提分不浪费时间！'
  },
  DEAD: {
    matchTeacher: '活力唤醒型、趣味激励型、强带动型',
    recommendCourse: '动力唤醒课 + 低压力闯关课',
    promoText: '无欲无求摆烂到底，连学习都懒得动？活力老师唤醒内驱力，用趣味打破摆烂，让孩子从“毁灭吧”变成“我能行”！'
  },
  ZZZZ: {
    matchTeacher: '幽默互动型、轻松氛围型、不拆穿型',
    recommendCourse: '趣味学习课 + 自律养成课',
    promoText: '一学习就装困、装病，逃避学习不主动？幽默老师不拆穿，把学习变有趣，让他主动醒过来，愿意跟着学！'
  },
  GOGO: {
    matchTeacher: '动静结合型、游戏化教学型、活力型',
    recommendCourse: '专注力训练 + 运动式学习课',
    promoText: '坐不住、爱乱跑，精力旺盛静不下来？动静结合教学，老师陪他边玩边学，把好动劲变成学习力，专注又高效！'
  },
  FUCK: {
    matchTeacher: '情绪稳定型、温和坚定型、不硬碰型',
    recommendCourse: '情绪管理课 + 规则意识课',
    promoText: '一点就炸毛，抗拒被管、爱发脾气？不硬碰硬，温柔坚定老师帮他平复情绪，学会管控脾气，安心投入学习！'
  },
  CTRL: {
    matchTeacher: '高效提升型、逻辑清晰型、专业型',
    recommendCourse: '拔高冲刺课 + 方法优化课',
    promoText: '情绪稳定不内耗，学习稳扎稳打？专业老师精准拔高，优化学习方法，让他保持优势，稳步提分不松懈！'
  },
  HHHH: {
    matchTeacher: '幽默风趣型、轻松课堂型、正能量型',
    recommendCourse: '快乐提分课 + 兴趣激发课',
    promoText: '没心没肺，考差也不emo，学习没动力？幽默老师陪他边笑边学，激发学习兴趣，让他学得开心、提分轻松！'
  },
  SEXY: {
    matchTeacher: '正向肯定型、保护自尊型、鼓励型',
    recommendCourse: '自信提升课 + 体面成长课',
    promoText: '敏感玻璃心，怕批评、怕丢脸，不敢尝试？老师多肯定少指责，保护他的自尊，让他敢于学、大胆练，自信成长！'
  },
  OJBK: {
    matchTeacher: '目标驱动型、轻度激励型、不强压型',
    recommendCourse: '目标唤醒课 + 轻度内卷课',
    promoText: '不卷不争，看似佛系实则没方向？轻度激励不施压，老师帮他找到学习目标，佛系不躺平，稳步提升不焦虑！'
  },
  POOR: {
    matchTeacher: '暖心踏实型、低负担型、真诚型',
    recommendCourse: '高性价比辅导 + 心理安抚课',
    promoText: '太懂事，心疼爸妈花钱，不敢提学习需求？高性价比辅导，老师踏实教学，不花冤枉钱，让懂事的孩子安心学好！'
  },
  'OH-NO': {
    matchTeacher: '低压力型、慢节奏型、温柔引导型',
    recommendCourse: '减压学习课 + 兴趣重建课',
    promoText: '一听学习就崩溃，逃避退缩不敢学？慢节奏温柔引导，老师不逼不骂，帮他重建学习兴趣，告别抗拒不抵触！'
  },
  MONK: {
    matchTeacher: '沉稳学术型、深度讲题型、少话型',
    recommendCourse: '深度理解课 + 逻辑强化课',
    promoText: '安静内敛，默默努力却没人引导？沉稳专业老师，贴合他的节奏深度授课，让他的努力不被辜负，慢慢发光发热！'
  },
  SHIT: {
    matchTeacher: '专业硬核型、实力征服型、不玻璃心型',
    recommendCourse: '逻辑批判课 + 表达规范课',
    promoText: '爱吐槽、嘴挑剔，看谁教得都不行？实力硬核老师，用专业征服他，精准解决学习问题，让他心服口服跟着学！'
  },
  'THAN-K': {
    matchTeacher: '细致负责型、真诚鼓励型、温暖型',
    recommendCourse: '全面提升课 + 习惯养成课',
    promoText: '听话懂感恩，愿意努力却缺方法？细致耐心老师，手把手教他学习技巧，不敷衍不糊弄，让他的努力不白费、越学越优秀！'
  },
  MALO: {
    matchTeacher: '活泼有趣型、互动课堂型、能玩型',
    recommendCourse: '游戏化学习 + 表达力课',
    promoText: '搞怪爱演、人来疯，坐不住却精力足？活泼有趣老师，陪他互动玩学，把学习变成乐趣，让他越玩越爱学、越学越出色！'
  },
  'ATM-er': {
    matchTeacher: '原则清晰型、边界感强型、正直型',
    recommendCourse: '自我保护课 + 互助学习课',
    promoText: '心软不懂拒绝，总帮别人耽误自己学习？老师教他立边界、守原则，既懂善良也懂拒绝，不耽误学习、也不伤害他人！'
  },
  'THIN-K': {
    matchTeacher: '学术深度型、逻辑严谨型、高智商型',
    recommendCourse: '拔高竞赛课 + 思维训练课',
    promoText: '理性爱琢磨，逻辑超强不情绪化？高智商老师，带他拔高思维、突破瓶颈，优化学习思路，冲刺高分更轻松！'
  },
  SOLO: {
    matchTeacher: '共情陪伴型、温暖治愈型、倾听型',
    recommendCourse: '心理陪伴课 + 社交适应课',
    promoText: '孤独缺爱，没人懂他、缺安全感？共情温暖老师，耐心倾听、用心陪伴，给足他安全感，让他不再孤单、勇敢向前学！'
  },
  'LOVE-R': {
    matchTeacher: '情绪稳定型、温柔包容型、高情商型',
    recommendCourse: '情绪调节课 + 情感表达课',
    promoText: '易喜易怒、情绪多变，粘人又怕冷漠？高情商老师，帮他调节情绪、稳定心态，情绪稳了，学习才能更稳定！'
  },
  'WOC!': {
    matchTeacher: '淡定安抚型、轻松解压型、稳重型',
    recommendCourse: '抗压训练课 + 心态建设课',
    promoText: '一点小事就慌神，反应超大、内心脆弱？淡定稳重型老师，帮他建设强大内心，教他从容应对，学习不慌不忙！'
  },
  'DRUN-K': {
    matchTeacher: '自律监督型、轻度鞭策型、不纵容型',
    recommendCourse: '自律养成课 + 责任意识课',
    promoText: '遇事就逃、假装快乐，摆烂不负责？轻度鞭策不纵容，老师帮他养成自律，学会直面学习，慢慢成长不逃避！'
  },
  IMBW: {
    matchTeacher: '极度鼓励型、重建自信型、无条件接纳型',
    recommendCourse: '自信重塑课 + 价值感建立课',
    promoText: '极度自卑，总觉得自己没用、自我攻击？老师无条件接纳、全力鼓励，帮他重建自信，找回价值感，让他知道自己也能学好！'
  }
};

if (typeof window !== 'undefined') {
  window.TYPE_EXTRA = TYPE_EXTRA;
}

/** @type {Record<string, { code: string, cn: string, intro: string, desc: string }>} */
const TYPE_LIBRARY = {
  IMSB: {
    code: 'IMSB',
    cn: '自我攻击者',
    intro: '总觉得自己不行，但还想变好。',
    desc: '自尊偏低、依恋安全感不足，情绪敏感易受伤；知道自己内耗却难刹车，怕犯错、不太敢变通。这不是「真傻」，是内心戏超纲——试试把自责换成一句「我已经在努力了」。'
  },
  BOSS: {
    code: 'BOSS',
    cn: '领导者',
    intro: '方向盘给我，我来开。',
    desc: '自信清晰、目标感强，讨厌被管、边界硬、情绪相对淡；信自己多于信别人，更守自己的规则。适合当小团队里那个拍板的人，也要记得给别人留一点发挥空间。'
  },
  MUM: {
    code: 'MUM',
    cn: '妈妈型（依赖黏人）',
    intro: '或许……我可以叫你妈妈吗？',
    desc: '极度依赖家长、情绪丰富爱粘人，自我价值常靠大人肯定；相信大人、世界感觉很安全，但不太敢反抗规则。被理解时会很乖，也需要练习「自己站稳一点点」。'
  },
  FAKE: {
    code: 'FAKE',
    cn: '伪人',
    intro: '已经，没有人类了（演戏模式）。',
    desc: '表面一套内心一套，擅长变通和装；怕看穿所以伪装，投入度偏低。不是坏人，更像在用「表现好」换安全感——偶尔允许自己不演，会轻松很多。'
  },
  DEAD: {
    code: 'DEAD',
    cn: '死者（躺平型）',
    intro: '我，还活着吗？',
    desc: '躺平、麻木、不想努力；世界没意思，怎么舒服怎么来。像通关太多次的玩家删档——若还有一点点想动起来的火苗，可以从极小的一步开始。'
  },
  ZZZZ: {
    code: 'ZZZZ',
    cn: '装死者',
    intro: '我没死，我只是在睡觉。',
    desc: '擅长装累装困装病，靠同情换照顾；想躲学习、借口满分。deadline 前可能突然爆发——你不是懒，是压力应对方式有点「演技派」。'
  },
  GOGO: {
    code: 'GOGO',
    cn: '行者',
    intro: 'gogogo～坐不住。',
    desc: '精力旺、冲动、爱探索；不粘人也不爱守规矩，世界很好玩。需要把「乱跑」升级成「有方向的探索」，否则容易踩线。'
  },
  FUCK: {
    code: 'FUCK',
    cn: '草者',
    intro: '别管我，容易炸。',
    desc: '脾气爆、怕控制、情绪极端；讨厌规则、世界感觉很压抑。爆发力是双刃剑——学会喊停三秒，能减少误伤自己和别人。'
  },
  CTRL: {
    code: 'CTRL',
    cn: '拿捏者',
    intro: '一切尽在掌握。',
    desc: '清醒稳定、关系里不敏感，克制不粘人；有边界也温柔，佛系不极端。像行走的小管家——你已经很强了，偶尔示弱也没关系。'
  },
  HHHH: {
    code: 'HHHH',
    cn: '傻乐者',
    intro: '哈哈哈哈哈哈。',
    desc: '乐观外放、谁都能玩；开心最重要，随性不较真。若匹配度低时系统也会把你丢来这里——说明你的选项组合太「清流」，当快乐吉祥物挺好。'
  },
  SEXY: {
    code: 'SEXY',
    cn: '尤物（在意眼光）',
    intro: '超在意别人怎么看我。',
    desc: '怕丢脸、怕评价，情绪细腻易自卑；世界像在看脸看表现。你不需要完美才值得被喜欢——缩小「观众席」会轻松很多。'
  },
  OJBK: {
    code: 'OJBK',
    cn: '无所谓人',
    intro: '我说随便，是真的随便。',
    desc: '清醒摆烂、情绪淡漠，不粘人不依赖；怎么轻松怎么来。不是没主见，是懒得为琐事燃烧 CPU。'
  },
  POOR: {
    code: 'POOR',
    cn: '贫穷者（懂事敏感）',
    intro: '我穷，但我很专。',
    desc: '懂事、怕花钱、体贴家人；心软重感情，不敢浪费、不敢多依赖。你的「省」里全是爱——也记得给自己留一点点任性额度。'
  },
  'OH-NO': {
    code: 'OH-NO',
    cn: '哦不人',
    intro: '哦不！压力好大！',
    desc: '一学习就崩溃、怕批评怕任务；情绪脆弱易崩，想躲想被保护。把大任务切成「哦不级别」超小步，会没那么吓人。'
  },
  MONK: {
    code: 'MONK',
    cn: '僧人',
    intro: '没有那种世俗的欲望。',
    desc: '安静低调、内心清醒，独处舒服；情绪内敛、边界清晰，平和不惹事。你的结界很强——偶尔开一条小缝给信任的人。'
  },
  SHIT: {
    code: 'SHIT',
    cn: '狗屎人',
    intro: '这个世界，构石一坨。',
    desc: '毒舌清醒、挑剔真实；不信任爱吐槽，边界强难接近。嘴上狠心里可能很认真——吐槽是外壳，别让它挡住真正想靠近的人。'
  },
  'THAN-K': {
    code: 'THAN-K',
    cn: '感恩者',
    intro: '我感谢苍天！我感谢大地！',
    desc: '乖巧懂事、想变好想报答；信任大人、重感情，守规矩懂礼貌。感恩是超能力——你不必为所有事都说谢谢才配被爱。'
  },
  MALO: {
    code: 'MALO',
    cn: '吗喽',
    intro: '人生副本里的一只吗喽。',
    desc: '搞笑人来疯、冲动爱玩；社交牛、情绪外放，舞台就是世界。不守规矩怎么好玩怎么来——把创意用在安全区里会更长久。'
  },
  'ATM-er': {
    code: 'ATM-er',
    cn: '送钱者',
    intro: '老好人，容易付出。',
    desc: '心软不懂拒绝，想助人想和谐；信任别人、怕冲突。你像情绪 ATM——记得设置「余额不足请改日」的边界。'
  },
  'THIN-K': {
    code: 'THIN-K',
    cn: '思考者',
    intro: '已深度思考 100s。',
    desc: '理性爱琢磨、逻辑强、想变强；独立不粘、情绪淡、边界清。世界讲事实——偶尔也让感受上桌吃顿饭。'
  },
  SOLO: {
    code: 'SOLO',
    cn: '孤儿（孤独敏感）',
    intro: '我哭了，我怎么这么孤单？',
    desc: '敏感缺安全感、怕孤独又怕受伤；世界冷漠要靠自己，怕变化。刺猬外壳下可能是「别离开我」——找到一个小出口慢慢说。'
  },
  'LOVE-R': {
    code: 'LOVE-R',
    cn: '多情者',
    intro: '爱意太满，现实有点挤。',
    desc: '情绪起伏大、粘人怕失去；世界要靠温度靠情感。爱得用力不是错——学会自我调节，关系会更稳。'
  },
  'WOC!': {
    code: 'WOC!',
    cn: '稻草人',
    intro: '卧槽，又怎么了我？',
    desc: '一惊一乍、反应大、爱吐槽；怕麻烦怕压力，能躲就躲。用夸张消化焦虑很常见——试试把「WOC」后面加一句「那下一步呢」。'
  },
  'DRUN-K': {
    code: 'DRUN-K',
    cn: '酒鬼（孩子版·逃避）',
    intro: '遇事就想逃，快乐优先。',
    desc: '摆烂逃避、不想负责；靠逃避缓压，假装快乐。不是真喝酒，是心理「躲一躲」——小目标拉回现实，比一直逃更省能量。'
  },
  IMBW: {
    code: 'IMBW',
    cn: '废物（自我否定型）',
    intro: '我真的……不行吗？',
    desc: '极度自我否定、内耗严重；怕被嫌弃、情绪脆弱，世界很残酷自己很差。名字是戏谑——你值得被温柔对待，包括来自自己的那一份。'
  }
};
