-- ============================================
-- 完整的中国省市区数据导入SQL脚本
-- 数据来源：pca-code.json
-- 数据规模：34省 + 城市 + 区县
-- 生成时间: 2025-10-05
-- ============================================

USE myjiajiao;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 确保表结构完整
SET @dbname = DATABASE();

-- 确保fa_provinces表存在code字段
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'fa_provinces' AND COLUMN_NAME = 'code'
  ) > 0,
  'SELECT "code字段已存在" AS info',
  'ALTER TABLE fa_provinces ADD COLUMN code varchar(20) DEFAULT NULL COMMENT "省份行政区划代码" AFTER id, ADD UNIQUE KEY uk_code (code)'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 确保fa_cities表有province_id和code字段
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'fa_cities' AND COLUMN_NAME = 'province_id'
  ) > 0,
  'SELECT "province_id字段已存在" AS info',
  'ALTER TABLE fa_cities ADD COLUMN province_id int(11) DEFAULT NULL COMMENT "所属省份ID" AFTER id, ADD KEY idx_province_id (province_id)'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'fa_cities' AND COLUMN_NAME = 'code'
  ) > 0,
  'SELECT "code字段已存在" AS info',
  'ALTER TABLE fa_cities ADD COLUMN code varchar(20) DEFAULT NULL COMMENT "城市行政区划代码" AFTER province_id'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 确保fa_districts表有code字段
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'fa_districts' AND COLUMN_NAME = 'code'
  ) > 0,
  'SELECT "code字段已存在" AS info',
  'ALTER TABLE fa_districts ADD COLUMN code varchar(20) DEFAULT NULL COMMENT "区县行政区划代码" AFTER city_id'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 删除可能存在的唯一索引（城市名称允许重复）
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'fa_cities' AND INDEX_NAME = 'uk_name'
  ) > 0,
  'ALTER TABLE fa_cities DROP INDEX uk_name',
  'SELECT "uk_name索引不存在，跳过删除" AS info'
));
PREPARE alterIfExists FROM @preparedStatement;
EXECUTE alterIfExists;
DEALLOCATE PREPARE alterIfExists;

-- 清空现有数据
DELETE FROM fa_districts;
DELETE FROM fa_cities;
DELETE FROM fa_provinces;
ALTER TABLE fa_districts AUTO_INCREMENT = 1;
ALTER TABLE fa_cities AUTO_INCREMENT = 1;
ALTER TABLE fa_provinces AUTO_INCREMENT = 1;

-- ============================================
-- 导入省份数据
-- ============================================
INSERT INTO fa_provinces (code, name, short_name, sort, status) VALUES
('110000', '北京市', '京', 1, 1),
('120000', '天津市', '津', 2, 1),
('130000', '河北省', '冀', 3, 1),
('140000', '山西省', '晋', 4, 1),
('150000', '内蒙古自治区', '蒙', 5, 1),
('210000', '辽宁省', '辽', 6, 1),
('220000', '吉林省', '吉', 7, 1),
('230000', '黑龙江省', '黑', 8, 1),
('310000', '上海市', '沪', 9, 1),
('320000', '江苏省', '苏', 10, 1),
('330000', '浙江省', '浙', 11, 1),
('340000', '安徽省', '皖', 12, 1),
('350000', '福建省', '闽', 13, 1),
('360000', '江西省', '赣', 14, 1),
('370000', '山东省', '鲁', 15, 1),
('410000', '河南省', '豫', 16, 1),
('420000', '湖北省', '鄂', 17, 1),
('430000', '湖南省', '湘', 18, 1),
('440000', '广东省', '粤', 19, 1),
('450000', '广西壮族自治区', '桂', 20, 1),
('460000', '海南省', '琼', 21, 1),
('500000', '重庆市', '渝', 22, 1),
('510000', '四川省', '川', 23, 1),
('520000', '贵州省', '黔', 24, 1),
('530000', '云南省', '滇', 25, 1),
('540000', '西藏自治区', '藏', 26, 1),
('610000', '陕西省', '陕', 27, 1),
('620000', '甘肃省', '甘', 28, 1),
('630000', '青海省', '青', 29, 1),
('640000', '宁夏回族自治区', '宁', 30, 1),
('650000', '新疆维吾尔自治区', '新', 31, 1);

-- ============================================
-- 导入城市数据
-- ============================================
INSERT INTO fa_cities (province_id, code, name, level, sort, status) VALUES
(1, '110100', '北京市', '一线城市', 1, 1),
(2, '120100', '天津市', '新一线城市', 1, 1),
(3, '130100', '石家庄市', '三线城市', 1, 1),
(3, '130200', '唐山市', '三线城市', 1, 1),
(3, '130300', '秦皇岛市', '三线城市', 1, 1),
(3, '130400', '邯郸市', '三线城市', 1, 1),
(3, '130500', '邢台市', '三线城市', 1, 1),
(3, '130600', '保定市', '三线城市', 1, 1),
(3, '130700', '张家口市', '三线城市', 1, 1),
(3, '130800', '承德市', '三线城市', 1, 1),
(3, '130900', '沧州市', '三线城市', 1, 1),
(3, '131000', '廊坊市', '三线城市', 1, 1),
(3, '131100', '衡水市', '三线城市', 1, 1),
(4, '140100', '太原市', '三线城市', 1, 1),
(4, '140200', '大同市', '三线城市', 1, 1),
(4, '140300', '阳泉市', '三线城市', 1, 1),
(4, '140400', '长治市', '三线城市', 1, 1),
(4, '140500', '晋城市', '三线城市', 1, 1),
(4, '140600', '朔州市', '三线城市', 1, 1),
(4, '140700', '晋中市', '三线城市', 1, 1),
(4, '140800', '运城市', '三线城市', 1, 1),
(4, '140900', '忻州市', '三线城市', 1, 1),
(4, '141000', '临汾市', '三线城市', 1, 1),
(4, '141100', '吕梁市', '三线城市', 1, 1),
(5, '150100', '呼和浩特市', '三线城市', 1, 1),
(5, '150200', '包头市', '三线城市', 1, 1),
(5, '150300', '乌海市', '三线城市', 1, 1),
(5, '150400', '赤峰市', '三线城市', 1, 1),
(5, '150500', '通辽市', '三线城市', 1, 1),
(5, '150600', '鄂尔多斯市', '三线城市', 1, 1),
(5, '150700', '呼伦贝尔市', '三线城市', 1, 1),
(5, '150800', '巴彦淖尔市', '三线城市', 1, 1),
(5, '150900', '乌兰察布市', '三线城市', 1, 1),
(5, '152200', '兴安盟', '三线城市', 1, 1),
(5, '152500', '锡林郭勒盟', '三线城市', 1, 1),
(5, '152900', '阿拉善盟', '三线城市', 1, 1),
(6, '210100', '沈阳市', '新一线城市', 1, 1),
(6, '210200', '大连市', '二线城市', 1, 1),
(6, '210300', '鞍山市', '三线城市', 1, 1),
(6, '210400', '抚顺市', '三线城市', 1, 1),
(6, '210500', '本溪市', '三线城市', 1, 1),
(6, '210600', '丹东市', '三线城市', 1, 1),
(6, '210700', '锦州市', '三线城市', 1, 1),
(6, '210800', '营口市', '三线城市', 1, 1),
(6, '210900', '阜新市', '三线城市', 1, 1),
(6, '211000', '辽阳市', '三线城市', 1, 1),
(6, '211100', '盘锦市', '三线城市', 1, 1),
(6, '211200', '铁岭市', '三线城市', 1, 1),
(6, '211300', '朝阳市', '三线城市', 1, 1),
(6, '211400', '葫芦岛市', '三线城市', 1, 1),
(7, '220100', '长春市', '三线城市', 1, 1),
(7, '220200', '吉林市', '三线城市', 1, 1),
(7, '220300', '四平市', '三线城市', 1, 1),
(7, '220400', '辽源市', '三线城市', 1, 1),
(7, '220500', '通化市', '三线城市', 1, 1),
(7, '220600', '白山市', '三线城市', 1, 1),
(7, '220700', '松原市', '三线城市', 1, 1),
(7, '220800', '白城市', '三线城市', 1, 1),
(7, '222400', '延边朝鲜族自治州', '三线城市', 1, 1),
(8, '230100', '哈尔滨市', '二线城市', 1, 1),
(8, '230200', '齐齐哈尔市', '三线城市', 1, 1),
(8, '230300', '鸡西市', '三线城市', 1, 1),
(8, '230400', '鹤岗市', '三线城市', 1, 1),
(8, '230500', '双鸭山市', '三线城市', 1, 1),
(8, '230600', '大庆市', '三线城市', 1, 1),
(8, '230700', '伊春市', '三线城市', 1, 1),
(8, '230800', '佳木斯市', '三线城市', 1, 1),
(8, '230900', '七台河市', '三线城市', 1, 1),
(8, '231000', '牡丹江市', '三线城市', 1, 1),
(8, '231100', '黑河市', '三线城市', 1, 1),
(8, '231200', '绥化市', '三线城市', 1, 1),
(8, '232700', '大兴安岭地区', '三线城市', 1, 1),
(9, '310100', '上海市', '一线城市', 1, 1),
(10, '320100', '南京市', '新一线城市', 1, 1),
(10, '320200', '无锡市', '二线城市', 1, 1),
(10, '320300', '徐州市', '三线城市', 1, 1),
(10, '320400', '常州市', '三线城市', 1, 1),
(10, '320500', '苏州市', '新一线城市', 1, 1),
(10, '320600', '南通市', '三线城市', 1, 1),
(10, '320700', '连云港市', '三线城市', 1, 1),
(10, '320800', '淮安市', '三线城市', 1, 1),
(10, '320900', '盐城市', '三线城市', 1, 1),
(10, '321000', '扬州市', '三线城市', 1, 1),
(10, '321100', '镇江市', '三线城市', 1, 1),
(10, '321200', '泰州市', '三线城市', 1, 1),
(10, '321300', '宿迁市', '三线城市', 1, 1),
(11, '330100', '杭州市', '新一线城市', 1, 1),
(11, '330200', '宁波市', '三线城市', 1, 1),
(11, '330300', '温州市', '二线城市', 1, 1),
(11, '330400', '嘉兴市', '三线城市', 1, 1),
(11, '330500', '湖州市', '三线城市', 1, 1),
(11, '330600', '绍兴市', '三线城市', 1, 1),
(11, '330700', '金华市', '三线城市', 1, 1),
(11, '330800', '衢州市', '三线城市', 1, 1),
(11, '330900', '舟山市', '三线城市', 1, 1),
(11, '331000', '台州市', '三线城市', 1, 1),
(11, '331100', '丽水市', '三线城市', 1, 1),
(12, '340100', '合肥市', '新一线城市', 1, 1),
(12, '340200', '芜湖市', '三线城市', 1, 1),
(12, '340300', '蚌埠市', '三线城市', 1, 1),
(12, '340400', '淮南市', '三线城市', 1, 1),
(12, '340500', '马鞍山市', '三线城市', 1, 1),
(12, '340600', '淮北市', '三线城市', 1, 1),
(12, '340700', '铜陵市', '三线城市', 1, 1),
(12, '340800', '安庆市', '三线城市', 1, 1),
(12, '341000', '黄山市', '三线城市', 1, 1),
(12, '341100', '滁州市', '三线城市', 1, 1),
(12, '341200', '阜阳市', '三线城市', 1, 1),
(12, '341300', '宿州市', '三线城市', 1, 1),
(12, '341500', '六安市', '三线城市', 1, 1),
(12, '341600', '亳州市', '三线城市', 1, 1),
(12, '341700', '池州市', '三线城市', 1, 1),
(12, '341800', '宣城市', '三线城市', 1, 1),
(13, '350100', '福州市', '二线城市', 1, 1),
(13, '350200', '厦门市', '二线城市', 1, 1),
(13, '350300', '莆田市', '三线城市', 1, 1),
(13, '350400', '三明市', '三线城市', 1, 1),
(13, '350500', '泉州市', '三线城市', 1, 1),
(13, '350600', '漳州市', '三线城市', 1, 1),
(13, '350700', '南平市', '三线城市', 1, 1),
(13, '350800', '龙岩市', '三线城市', 1, 1),
(13, '350900', '宁德市', '三线城市', 1, 1),
(14, '360100', '南昌市', '三线城市', 1, 1),
(14, '360200', '景德镇市', '三线城市', 1, 1),
(14, '360300', '萍乡市', '三线城市', 1, 1),
(14, '360400', '九江市', '三线城市', 1, 1),
(14, '360500', '新余市', '三线城市', 1, 1),
(14, '360600', '鹰潭市', '三线城市', 1, 1),
(14, '360700', '赣州市', '三线城市', 1, 1),
(14, '360800', '吉安市', '三线城市', 1, 1),
(14, '360900', '宜春市', '三线城市', 1, 1),
(14, '361000', '抚州市', '三线城市', 1, 1),
(14, '361100', '上饶市', '三线城市', 1, 1),
(15, '370100', '济南市', '二线城市', 1, 1),
(15, '370200', '青岛市', '新一线城市', 1, 1),
(15, '370300', '淄博市', '三线城市', 1, 1),
(15, '370400', '枣庄市', '三线城市', 1, 1),
(15, '370500', '东营市', '三线城市', 1, 1),
(15, '370600', '烟台市', '三线城市', 1, 1),
(15, '370700', '潍坊市', '三线城市', 1, 1),
(15, '370800', '济宁市', '三线城市', 1, 1),
(15, '370900', '泰安市', '三线城市', 1, 1),
(15, '371000', '威海市', '三线城市', 1, 1),
(15, '371100', '日照市', '三线城市', 1, 1),
(15, '371300', '临沂市', '三线城市', 1, 1),
(15, '371400', '德州市', '三线城市', 1, 1),
(15, '371500', '聊城市', '三线城市', 1, 1),
(15, '371600', '滨州市', '三线城市', 1, 1),
(15, '371700', '菏泽市', '三线城市', 1, 1),
(16, '410100', '郑州市', '新一线城市', 1, 1),
(16, '410200', '开封市', '三线城市', 1, 1),
(16, '410300', '洛阳市', '三线城市', 1, 1),
(16, '410400', '平顶山市', '三线城市', 1, 1),
(16, '410500', '安阳市', '三线城市', 1, 1),
(16, '410600', '鹤壁市', '三线城市', 1, 1),
(16, '410700', '新乡市', '三线城市', 1, 1),
(16, '410800', '焦作市', '三线城市', 1, 1),
(16, '410900', '濮阳市', '三线城市', 1, 1),
(16, '411000', '许昌市', '三线城市', 1, 1),
(16, '411100', '漯河市', '三线城市', 1, 1),
(16, '411200', '三门峡市', '三线城市', 1, 1),
(16, '411300', '南阳市', '三线城市', 1, 1),
(16, '411400', '商丘市', '三线城市', 1, 1),
(16, '411500', '信阳市', '三线城市', 1, 1),
(16, '411600', '周口市', '三线城市', 1, 1),
(16, '411700', '驻马店市', '三线城市', 1, 1),
(17, '420100', '武汉市', '新一线城市', 1, 1),
(17, '420200', '黄石市', '三线城市', 1, 1),
(17, '420300', '十堰市', '三线城市', 1, 1),
(17, '420500', '宜昌市', '三线城市', 1, 1),
(17, '420600', '襄阳市', '三线城市', 1, 1),
(17, '420700', '鄂州市', '三线城市', 1, 1),
(17, '420800', '荆门市', '三线城市', 1, 1),
(17, '420900', '孝感市', '三线城市', 1, 1),
(17, '421000', '荆州市', '三线城市', 1, 1),
(17, '421100', '黄冈市', '三线城市', 1, 1),
(17, '421200', '咸宁市', '三线城市', 1, 1),
(17, '421300', '随州市', '三线城市', 1, 1),
(17, '422800', '恩施土家族苗族自治州', '三线城市', 1, 1),
(18, '430100', '长沙市', '新一线城市', 1, 1),
(18, '430200', '株洲市', '三线城市', 1, 1),
(18, '430300', '湘潭市', '三线城市', 1, 1),
(18, '430400', '衡阳市', '三线城市', 1, 1),
(18, '430500', '邵阳市', '三线城市', 1, 1),
(18, '430600', '岳阳市', '三线城市', 1, 1),
(18, '430700', '常德市', '三线城市', 1, 1),
(18, '430800', '张家界市', '三线城市', 1, 1),
(18, '430900', '益阳市', '三线城市', 1, 1),
(18, '431000', '郴州市', '三线城市', 1, 1),
(18, '431100', '永州市', '三线城市', 1, 1),
(18, '431200', '怀化市', '三线城市', 1, 1),
(18, '431300', '娄底市', '三线城市', 1, 1),
(18, '433100', '湘西土家族苗族自治州', '三线城市', 1, 1),
(19, '440100', '广州市', '一线城市', 1, 1),
(19, '440200', '韶关市', '三线城市', 1, 1),
(19, '440300', '深圳市', '一线城市', 1, 1),
(19, '440400', '珠海市', '三线城市', 1, 1),
(19, '440500', '汕头市', '三线城市', 1, 1),
(19, '440600', '佛山市', '新一线城市', 1, 1),
(19, '440700', '江门市', '三线城市', 1, 1),
(19, '440800', '湛江市', '三线城市', 1, 1),
(19, '440900', '茂名市', '三线城市', 1, 1),
(19, '441200', '肇庆市', '三线城市', 1, 1),
(19, '441300', '惠州市', '三线城市', 1, 1),
(19, '441400', '梅州市', '三线城市', 1, 1),
(19, '441500', '汕尾市', '三线城市', 1, 1),
(19, '441600', '河源市', '三线城市', 1, 1),
(19, '441700', '阳江市', '三线城市', 1, 1),
(19, '441800', '清远市', '三线城市', 1, 1),
(19, '441900', '东莞市', '新一线城市', 1, 1),
(19, '442000', '中山市', '三线城市', 1, 1),
(19, '445100', '潮州市', '三线城市', 1, 1),
(19, '445200', '揭阳市', '三线城市', 1, 1),
(19, '445300', '云浮市', '三线城市', 1, 1),
(20, '450100', '南宁市', '三线城市', 1, 1),
(20, '450200', '柳州市', '三线城市', 1, 1),
(20, '450300', '桂林市', '三线城市', 1, 1),
(20, '450400', '梧州市', '三线城市', 1, 1),
(20, '450500', '北海市', '三线城市', 1, 1),
(20, '450600', '防城港市', '三线城市', 1, 1),
(20, '450700', '钦州市', '三线城市', 1, 1),
(20, '450800', '贵港市', '三线城市', 1, 1),
(20, '450900', '玉林市', '三线城市', 1, 1),
(20, '451000', '百色市', '三线城市', 1, 1),
(20, '451100', '贺州市', '三线城市', 1, 1),
(20, '451200', '河池市', '三线城市', 1, 1),
(20, '451300', '来宾市', '三线城市', 1, 1),
(20, '451400', '崇左市', '三线城市', 1, 1),
(21, '460100', '海口市', '三线城市', 1, 1),
(21, '460200', '三亚市', '三线城市', 1, 1),
(21, '460300', '三沙市', '三线城市', 1, 1),
(21, '460400', '儋州市', '三线城市', 1, 1),
(22, '500100', '重庆市', '新一线城市', 1, 1),
(23, '510100', '成都市', '新一线城市', 1, 1),
(23, '510300', '自贡市', '三线城市', 1, 1),
(23, '510400', '攀枝花市', '三线城市', 1, 1),
(23, '510500', '泸州市', '三线城市', 1, 1),
(23, '510600', '德阳市', '三线城市', 1, 1),
(23, '510700', '绵阳市', '三线城市', 1, 1),
(23, '510800', '广元市', '三线城市', 1, 1),
(23, '510900', '遂宁市', '三线城市', 1, 1),
(23, '511000', '内江市', '三线城市', 1, 1),
(23, '511100', '乐山市', '三线城市', 1, 1),
(23, '511300', '南充市', '三线城市', 1, 1),
(23, '511400', '眉山市', '三线城市', 1, 1),
(23, '511500', '宜宾市', '三线城市', 1, 1),
(23, '511600', '广安市', '三线城市', 1, 1),
(23, '511700', '达州市', '三线城市', 1, 1),
(23, '511800', '雅安市', '三线城市', 1, 1),
(23, '511900', '巴中市', '三线城市', 1, 1),
(23, '512000', '资阳市', '三线城市', 1, 1),
(23, '513200', '阿坝藏族羌族自治州', '三线城市', 1, 1),
(23, '513300', '甘孜藏族自治州', '三线城市', 1, 1),
(23, '513400', '凉山彝族自治州', '三线城市', 1, 1),
(24, '520100', '贵阳市', '三线城市', 1, 1),
(24, '520200', '六盘水市', '三线城市', 1, 1),
(24, '520300', '遵义市', '三线城市', 1, 1),
(24, '520400', '安顺市', '三线城市', 1, 1),
(24, '520500', '毕节市', '三线城市', 1, 1),
(24, '520600', '铜仁市', '三线城市', 1, 1),
(24, '522300', '黔西南布依族苗族自治州', '三线城市', 1, 1),
(24, '522600', '黔东南苗族侗族自治州', '三线城市', 1, 1),
(24, '522700', '黔南布依族苗族自治州', '三线城市', 1, 1),
(25, '530100', '昆明市', '二线城市', 1, 1),
(25, '530300', '曲靖市', '三线城市', 1, 1),
(25, '530400', '玉溪市', '三线城市', 1, 1),
(25, '530500', '保山市', '三线城市', 1, 1),
(25, '530600', '昭通市', '三线城市', 1, 1),
(25, '530700', '丽江市', '三线城市', 1, 1),
(25, '530800', '普洱市', '三线城市', 1, 1),
(25, '530900', '临沧市', '三线城市', 1, 1),
(25, '532300', '楚雄彝族自治州', '三线城市', 1, 1),
(25, '532500', '红河哈尼族彝族自治州', '三线城市', 1, 1),
(25, '532600', '文山壮族苗族自治州', '三线城市', 1, 1),
(25, '532800', '西双版纳傣族自治州', '三线城市', 1, 1),
(25, '532900', '大理白族自治州', '三线城市', 1, 1),
(25, '533100', '德宏傣族景颇族自治州', '三线城市', 1, 1),
(25, '533300', '怒江傈僳族自治州', '三线城市', 1, 1),
(25, '533400', '迪庆藏族自治州', '三线城市', 1, 1),
(26, '540100', '拉萨市', '三线城市', 1, 1),
(26, '540200', '日喀则市', '三线城市', 1, 1),
(26, '540300', '昌都市', '三线城市', 1, 1),
(26, '540400', '林芝市', '三线城市', 1, 1),
(26, '540500', '山南市', '三线城市', 1, 1),
(26, '540600', '那曲市', '三线城市', 1, 1),
(26, '542500', '阿里地区', '三线城市', 1, 1),
(27, '610100', '西安市', '新一线城市', 1, 1),
(27, '610200', '铜川市', '三线城市', 1, 1),
(27, '610300', '宝鸡市', '三线城市', 1, 1),
(27, '610400', '咸阳市', '三线城市', 1, 1),
(27, '610500', '渭南市', '三线城市', 1, 1),
(27, '610600', '延安市', '三线城市', 1, 1),
(27, '610700', '汉中市', '三线城市', 1, 1),
(27, '610800', '榆林市', '三线城市', 1, 1),
(27, '610900', '安康市', '三线城市', 1, 1),
(27, '611000', '商洛市', '三线城市', 1, 1),
(28, '620100', '兰州市', '三线城市', 1, 1),
(28, '620200', '嘉峪关市', '三线城市', 1, 1),
(28, '620300', '金昌市', '三线城市', 1, 1),
(28, '620400', '白银市', '三线城市', 1, 1),
(28, '620500', '天水市', '三线城市', 1, 1),
(28, '620600', '武威市', '三线城市', 1, 1),
(28, '620700', '张掖市', '三线城市', 1, 1),
(28, '620800', '平凉市', '三线城市', 1, 1),
(28, '620900', '酒泉市', '三线城市', 1, 1),
(28, '621000', '庆阳市', '三线城市', 1, 1),
(28, '621100', '定西市', '三线城市', 1, 1),
(28, '621200', '陇南市', '三线城市', 1, 1),
(28, '622900', '临夏回族自治州', '三线城市', 1, 1),
(28, '623000', '甘南藏族自治州', '三线城市', 1, 1),
(29, '630100', '西宁市', '三线城市', 1, 1),
(29, '630200', '海东市', '三线城市', 1, 1),
(29, '632200', '海北藏族自治州', '三线城市', 1, 1),
(29, '632300', '黄南藏族自治州', '三线城市', 1, 1),
(29, '632500', '海南藏族自治州', '三线城市', 1, 1),
(29, '632600', '果洛藏族自治州', '三线城市', 1, 1),
(29, '632700', '玉树藏族自治州', '三线城市', 1, 1),
(29, '632800', '海西蒙古族藏族自治州', '三线城市', 1, 1),
(30, '640100', '银川市', '三线城市', 1, 1),
(30, '640200', '石嘴山市', '三线城市', 1, 1),
(30, '640300', '吴忠市', '三线城市', 1, 1),
(30, '640400', '固原市', '三线城市', 1, 1),
(30, '640500', '中卫市', '三线城市', 1, 1),
(31, '650100', '乌鲁木齐市', '三线城市', 1, 1),
(31, '650200', '克拉玛依市', '三线城市', 1, 1),
(31, '650400', '吐鲁番市', '三线城市', 1, 1),
(31, '650500', '哈密市', '三线城市', 1, 1),
(31, '652300', '昌吉回族自治州', '三线城市', 1, 1),
(31, '652700', '博尔塔拉蒙古自治州', '三线城市', 1, 1),
(31, '652800', '巴音郭楞蒙古自治州', '三线城市', 1, 1),
(31, '652900', '阿克苏地区', '三线城市', 1, 1),
(31, '653000', '克孜勒苏柯尔克孜自治州', '三线城市', 1, 1),
(31, '653100', '喀什地区', '三线城市', 1, 1),
(31, '653200', '和田地区', '三线城市', 1, 1),
(31, '654000', '伊犁哈萨克自治州', '三线城市', 1, 1),
(31, '654200', '塔城地区', '三线城市', 1, 1),
(31, '654300', '阿勒泰地区', '三线城市', 1, 1);

-- ============================================
-- 导入区县数据
-- ============================================
-- 使用临时表建立城市映射
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '110101', '东城区', 1 FROM fa_cities WHERE code = '110100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '110102', '西城区', 1 FROM fa_cities WHERE code = '110100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '110105', '朝阳区', 1 FROM fa_cities WHERE code = '110100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '110106', '丰台区', 1 FROM fa_cities WHERE code = '110100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '110107', '石景山区', 1 FROM fa_cities WHERE code = '110100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '110108', '海淀区', 1 FROM fa_cities WHERE code = '110100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '110109', '门头沟区', 1 FROM fa_cities WHERE code = '110100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '110111', '房山区', 1 FROM fa_cities WHERE code = '110100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '110112', '通州区', 1 FROM fa_cities WHERE code = '110100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '110113', '顺义区', 1 FROM fa_cities WHERE code = '110100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '110114', '昌平区', 1 FROM fa_cities WHERE code = '110100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '110115', '大兴区', 1 FROM fa_cities WHERE code = '110100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '110116', '怀柔区', 1 FROM fa_cities WHERE code = '110100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '110117', '平谷区', 1 FROM fa_cities WHERE code = '110100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '110118', '密云区', 1 FROM fa_cities WHERE code = '110100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '110119', '延庆区', 1 FROM fa_cities WHERE code = '110100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '120101', '和平区', 1 FROM fa_cities WHERE code = '120100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '120102', '河东区', 1 FROM fa_cities WHERE code = '120100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '120103', '河西区', 1 FROM fa_cities WHERE code = '120100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '120104', '南开区', 1 FROM fa_cities WHERE code = '120100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '120105', '河北区', 1 FROM fa_cities WHERE code = '120100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '120106', '红桥区', 1 FROM fa_cities WHERE code = '120100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '120110', '东丽区', 1 FROM fa_cities WHERE code = '120100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '120111', '西青区', 1 FROM fa_cities WHERE code = '120100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '120112', '津南区', 1 FROM fa_cities WHERE code = '120100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '120113', '北辰区', 1 FROM fa_cities WHERE code = '120100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '120114', '武清区', 1 FROM fa_cities WHERE code = '120100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '120115', '宝坻区', 1 FROM fa_cities WHERE code = '120100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '120116', '滨海新区', 1 FROM fa_cities WHERE code = '120100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '120117', '宁河区', 1 FROM fa_cities WHERE code = '120100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '120118', '静海区', 1 FROM fa_cities WHERE code = '120100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '120119', '蓟州区', 1 FROM fa_cities WHERE code = '120100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130102', '长安区', 1 FROM fa_cities WHERE code = '130100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130104', '桥西区', 1 FROM fa_cities WHERE code = '130100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130105', '新华区', 1 FROM fa_cities WHERE code = '130100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130107', '井陉矿区', 1 FROM fa_cities WHERE code = '130100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130108', '裕华区', 1 FROM fa_cities WHERE code = '130100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130109', '藁城区', 1 FROM fa_cities WHERE code = '130100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130110', '鹿泉区', 1 FROM fa_cities WHERE code = '130100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130111', '栾城区', 1 FROM fa_cities WHERE code = '130100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130121', '井陉县', 1 FROM fa_cities WHERE code = '130100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130123', '正定县', 1 FROM fa_cities WHERE code = '130100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130125', '行唐县', 1 FROM fa_cities WHERE code = '130100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130126', '灵寿县', 1 FROM fa_cities WHERE code = '130100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130127', '高邑县', 1 FROM fa_cities WHERE code = '130100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130128', '深泽县', 1 FROM fa_cities WHERE code = '130100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130129', '赞皇县', 1 FROM fa_cities WHERE code = '130100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130130', '无极县', 1 FROM fa_cities WHERE code = '130100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130131', '平山县', 1 FROM fa_cities WHERE code = '130100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130132', '元氏县', 1 FROM fa_cities WHERE code = '130100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130133', '赵县', 1 FROM fa_cities WHERE code = '130100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130171', '石家庄高新技术产业开发区', 1 FROM fa_cities WHERE code = '130100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130172', '石家庄循环化工园区', 1 FROM fa_cities WHERE code = '130100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130181', '辛集市', 1 FROM fa_cities WHERE code = '130100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130183', '晋州市', 1 FROM fa_cities WHERE code = '130100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130184', '新乐市', 1 FROM fa_cities WHERE code = '130100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130202', '路南区', 1 FROM fa_cities WHERE code = '130200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130203', '路北区', 1 FROM fa_cities WHERE code = '130200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130204', '古冶区', 1 FROM fa_cities WHERE code = '130200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130205', '开平区', 1 FROM fa_cities WHERE code = '130200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130207', '丰南区', 1 FROM fa_cities WHERE code = '130200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130208', '丰润区', 1 FROM fa_cities WHERE code = '130200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130209', '曹妃甸区', 1 FROM fa_cities WHERE code = '130200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130224', '滦南县', 1 FROM fa_cities WHERE code = '130200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130225', '乐亭县', 1 FROM fa_cities WHERE code = '130200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130227', '迁西县', 1 FROM fa_cities WHERE code = '130200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130229', '玉田县', 1 FROM fa_cities WHERE code = '130200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130271', '河北唐山芦台经济开发区', 1 FROM fa_cities WHERE code = '130200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130272', '唐山市汉沽管理区', 1 FROM fa_cities WHERE code = '130200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130273', '唐山高新技术产业开发区', 1 FROM fa_cities WHERE code = '130200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130274', '河北唐山海港经济开发区', 1 FROM fa_cities WHERE code = '130200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130281', '遵化市', 1 FROM fa_cities WHERE code = '130200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130283', '迁安市', 1 FROM fa_cities WHERE code = '130200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130284', '滦州市', 1 FROM fa_cities WHERE code = '130200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130302', '海港区', 1 FROM fa_cities WHERE code = '130300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130303', '山海关区', 1 FROM fa_cities WHERE code = '130300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130304', '北戴河区', 1 FROM fa_cities WHERE code = '130300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130306', '抚宁区', 1 FROM fa_cities WHERE code = '130300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130321', '青龙满族自治县', 1 FROM fa_cities WHERE code = '130300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130322', '昌黎县', 1 FROM fa_cities WHERE code = '130300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130324', '卢龙县', 1 FROM fa_cities WHERE code = '130300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130371', '秦皇岛市经济技术开发区', 1 FROM fa_cities WHERE code = '130300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130372', '北戴河新区', 1 FROM fa_cities WHERE code = '130300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130402', '邯山区', 1 FROM fa_cities WHERE code = '130400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130403', '丛台区', 1 FROM fa_cities WHERE code = '130400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130404', '复兴区', 1 FROM fa_cities WHERE code = '130400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130406', '峰峰矿区', 1 FROM fa_cities WHERE code = '130400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130407', '肥乡区', 1 FROM fa_cities WHERE code = '130400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130408', '永年区', 1 FROM fa_cities WHERE code = '130400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130423', '临漳县', 1 FROM fa_cities WHERE code = '130400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130424', '成安县', 1 FROM fa_cities WHERE code = '130400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130425', '大名县', 1 FROM fa_cities WHERE code = '130400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130426', '涉县', 1 FROM fa_cities WHERE code = '130400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130427', '磁县', 1 FROM fa_cities WHERE code = '130400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130430', '邱县', 1 FROM fa_cities WHERE code = '130400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130431', '鸡泽县', 1 FROM fa_cities WHERE code = '130400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130432', '广平县', 1 FROM fa_cities WHERE code = '130400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130433', '馆陶县', 1 FROM fa_cities WHERE code = '130400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130434', '魏县', 1 FROM fa_cities WHERE code = '130400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130435', '曲周县', 1 FROM fa_cities WHERE code = '130400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130471', '邯郸经济技术开发区', 1 FROM fa_cities WHERE code = '130400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130473', '邯郸冀南新区', 1 FROM fa_cities WHERE code = '130400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130481', '武安市', 1 FROM fa_cities WHERE code = '130400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130502', '襄都区', 1 FROM fa_cities WHERE code = '130500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130503', '信都区', 1 FROM fa_cities WHERE code = '130500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130505', '任泽区', 1 FROM fa_cities WHERE code = '130500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130506', '南和区', 1 FROM fa_cities WHERE code = '130500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130522', '临城县', 1 FROM fa_cities WHERE code = '130500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130523', '内丘县', 1 FROM fa_cities WHERE code = '130500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130524', '柏乡县', 1 FROM fa_cities WHERE code = '130500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130525', '隆尧县', 1 FROM fa_cities WHERE code = '130500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130528', '宁晋县', 1 FROM fa_cities WHERE code = '130500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130529', '巨鹿县', 1 FROM fa_cities WHERE code = '130500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130530', '新河县', 1 FROM fa_cities WHERE code = '130500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130531', '广宗县', 1 FROM fa_cities WHERE code = '130500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130532', '平乡县', 1 FROM fa_cities WHERE code = '130500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130533', '威县', 1 FROM fa_cities WHERE code = '130500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130534', '清河县', 1 FROM fa_cities WHERE code = '130500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130535', '临西县', 1 FROM fa_cities WHERE code = '130500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130571', '河北邢台经济开发区', 1 FROM fa_cities WHERE code = '130500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130581', '南宫市', 1 FROM fa_cities WHERE code = '130500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130582', '沙河市', 1 FROM fa_cities WHERE code = '130500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130602', '竞秀区', 1 FROM fa_cities WHERE code = '130600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130606', '莲池区', 1 FROM fa_cities WHERE code = '130600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130607', '满城区', 1 FROM fa_cities WHERE code = '130600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130608', '清苑区', 1 FROM fa_cities WHERE code = '130600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130609', '徐水区', 1 FROM fa_cities WHERE code = '130600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130623', '涞水县', 1 FROM fa_cities WHERE code = '130600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130624', '阜平县', 1 FROM fa_cities WHERE code = '130600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130626', '定兴县', 1 FROM fa_cities WHERE code = '130600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130627', '唐县', 1 FROM fa_cities WHERE code = '130600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130628', '高阳县', 1 FROM fa_cities WHERE code = '130600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130629', '容城县', 1 FROM fa_cities WHERE code = '130600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130630', '涞源县', 1 FROM fa_cities WHERE code = '130600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130631', '望都县', 1 FROM fa_cities WHERE code = '130600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130632', '安新县', 1 FROM fa_cities WHERE code = '130600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130633', '易县', 1 FROM fa_cities WHERE code = '130600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130634', '曲阳县', 1 FROM fa_cities WHERE code = '130600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130635', '蠡县', 1 FROM fa_cities WHERE code = '130600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130636', '顺平县', 1 FROM fa_cities WHERE code = '130600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130637', '博野县', 1 FROM fa_cities WHERE code = '130600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130638', '雄县', 1 FROM fa_cities WHERE code = '130600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130671', '保定高新技术产业开发区', 1 FROM fa_cities WHERE code = '130600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130672', '保定白沟新城', 1 FROM fa_cities WHERE code = '130600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130681', '涿州市', 1 FROM fa_cities WHERE code = '130600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130682', '定州市', 1 FROM fa_cities WHERE code = '130600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130683', '安国市', 1 FROM fa_cities WHERE code = '130600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130684', '高碑店市', 1 FROM fa_cities WHERE code = '130600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130702', '桥东区', 1 FROM fa_cities WHERE code = '130700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130703', '桥西区', 1 FROM fa_cities WHERE code = '130700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130705', '宣化区', 1 FROM fa_cities WHERE code = '130700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130706', '下花园区', 1 FROM fa_cities WHERE code = '130700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130708', '万全区', 1 FROM fa_cities WHERE code = '130700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130709', '崇礼区', 1 FROM fa_cities WHERE code = '130700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130722', '张北县', 1 FROM fa_cities WHERE code = '130700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130723', '康保县', 1 FROM fa_cities WHERE code = '130700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130724', '沽源县', 1 FROM fa_cities WHERE code = '130700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130725', '尚义县', 1 FROM fa_cities WHERE code = '130700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130726', '蔚县', 1 FROM fa_cities WHERE code = '130700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130727', '阳原县', 1 FROM fa_cities WHERE code = '130700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130728', '怀安县', 1 FROM fa_cities WHERE code = '130700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130730', '怀来县', 1 FROM fa_cities WHERE code = '130700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130731', '涿鹿县', 1 FROM fa_cities WHERE code = '130700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130732', '赤城县', 1 FROM fa_cities WHERE code = '130700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130771', '张家口经济开发区', 1 FROM fa_cities WHERE code = '130700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130772', '张家口市察北管理区', 1 FROM fa_cities WHERE code = '130700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130773', '张家口市塞北管理区', 1 FROM fa_cities WHERE code = '130700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130802', '双桥区', 1 FROM fa_cities WHERE code = '130800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130803', '双滦区', 1 FROM fa_cities WHERE code = '130800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130804', '鹰手营子矿区', 1 FROM fa_cities WHERE code = '130800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130821', '承德县', 1 FROM fa_cities WHERE code = '130800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130822', '兴隆县', 1 FROM fa_cities WHERE code = '130800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130824', '滦平县', 1 FROM fa_cities WHERE code = '130800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130825', '隆化县', 1 FROM fa_cities WHERE code = '130800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130826', '丰宁满族自治县', 1 FROM fa_cities WHERE code = '130800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130827', '宽城满族自治县', 1 FROM fa_cities WHERE code = '130800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130828', '围场满族蒙古族自治县', 1 FROM fa_cities WHERE code = '130800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130871', '承德高新技术产业开发区', 1 FROM fa_cities WHERE code = '130800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130881', '平泉市', 1 FROM fa_cities WHERE code = '130800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130902', '新华区', 1 FROM fa_cities WHERE code = '130900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130903', '运河区', 1 FROM fa_cities WHERE code = '130900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130921', '沧县', 1 FROM fa_cities WHERE code = '130900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130922', '青县', 1 FROM fa_cities WHERE code = '130900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130923', '东光县', 1 FROM fa_cities WHERE code = '130900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130924', '海兴县', 1 FROM fa_cities WHERE code = '130900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130925', '盐山县', 1 FROM fa_cities WHERE code = '130900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130926', '肃宁县', 1 FROM fa_cities WHERE code = '130900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130927', '南皮县', 1 FROM fa_cities WHERE code = '130900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130928', '吴桥县', 1 FROM fa_cities WHERE code = '130900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130929', '献县', 1 FROM fa_cities WHERE code = '130900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130930', '孟村回族自治县', 1 FROM fa_cities WHERE code = '130900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130971', '河北沧州经济开发区', 1 FROM fa_cities WHERE code = '130900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130972', '沧州高新技术产业开发区', 1 FROM fa_cities WHERE code = '130900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130973', '沧州渤海新区', 1 FROM fa_cities WHERE code = '130900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130981', '泊头市', 1 FROM fa_cities WHERE code = '130900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130982', '任丘市', 1 FROM fa_cities WHERE code = '130900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130983', '黄骅市', 1 FROM fa_cities WHERE code = '130900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '130984', '河间市', 1 FROM fa_cities WHERE code = '130900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '131002', '安次区', 1 FROM fa_cities WHERE code = '131000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '131003', '广阳区', 1 FROM fa_cities WHERE code = '131000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '131022', '固安县', 1 FROM fa_cities WHERE code = '131000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '131023', '永清县', 1 FROM fa_cities WHERE code = '131000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '131024', '香河县', 1 FROM fa_cities WHERE code = '131000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '131025', '大城县', 1 FROM fa_cities WHERE code = '131000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '131026', '文安县', 1 FROM fa_cities WHERE code = '131000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '131028', '大厂回族自治县', 1 FROM fa_cities WHERE code = '131000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '131071', '廊坊经济技术开发区', 1 FROM fa_cities WHERE code = '131000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '131081', '霸州市', 1 FROM fa_cities WHERE code = '131000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '131082', '三河市', 1 FROM fa_cities WHERE code = '131000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '131102', '桃城区', 1 FROM fa_cities WHERE code = '131100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '131103', '冀州区', 1 FROM fa_cities WHERE code = '131100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '131121', '枣强县', 1 FROM fa_cities WHERE code = '131100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '131122', '武邑县', 1 FROM fa_cities WHERE code = '131100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '131123', '武强县', 1 FROM fa_cities WHERE code = '131100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '131124', '饶阳县', 1 FROM fa_cities WHERE code = '131100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '131125', '安平县', 1 FROM fa_cities WHERE code = '131100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '131126', '故城县', 1 FROM fa_cities WHERE code = '131100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '131127', '景县', 1 FROM fa_cities WHERE code = '131100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '131128', '阜城县', 1 FROM fa_cities WHERE code = '131100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '131171', '河北衡水高新技术产业开发区', 1 FROM fa_cities WHERE code = '131100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '131172', '衡水滨湖新区', 1 FROM fa_cities WHERE code = '131100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '131182', '深州市', 1 FROM fa_cities WHERE code = '131100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140105', '小店区', 1 FROM fa_cities WHERE code = '140100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140106', '迎泽区', 1 FROM fa_cities WHERE code = '140100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140107', '杏花岭区', 1 FROM fa_cities WHERE code = '140100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140108', '尖草坪区', 1 FROM fa_cities WHERE code = '140100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140109', '万柏林区', 1 FROM fa_cities WHERE code = '140100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140110', '晋源区', 1 FROM fa_cities WHERE code = '140100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140121', '清徐县', 1 FROM fa_cities WHERE code = '140100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140122', '阳曲县', 1 FROM fa_cities WHERE code = '140100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140123', '娄烦县', 1 FROM fa_cities WHERE code = '140100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140171', '山西转型综合改革示范区', 1 FROM fa_cities WHERE code = '140100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140181', '古交市', 1 FROM fa_cities WHERE code = '140100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140212', '新荣区', 1 FROM fa_cities WHERE code = '140200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140213', '平城区', 1 FROM fa_cities WHERE code = '140200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140214', '云冈区', 1 FROM fa_cities WHERE code = '140200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140215', '云州区', 1 FROM fa_cities WHERE code = '140200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140221', '阳高县', 1 FROM fa_cities WHERE code = '140200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140222', '天镇县', 1 FROM fa_cities WHERE code = '140200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140223', '广灵县', 1 FROM fa_cities WHERE code = '140200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140224', '灵丘县', 1 FROM fa_cities WHERE code = '140200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140225', '浑源县', 1 FROM fa_cities WHERE code = '140200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140226', '左云县', 1 FROM fa_cities WHERE code = '140200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140271', '山西大同经济开发区', 1 FROM fa_cities WHERE code = '140200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140302', '城区', 1 FROM fa_cities WHERE code = '140300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140303', '矿区', 1 FROM fa_cities WHERE code = '140300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140311', '郊区', 1 FROM fa_cities WHERE code = '140300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140321', '平定县', 1 FROM fa_cities WHERE code = '140300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140322', '盂县', 1 FROM fa_cities WHERE code = '140300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140403', '潞州区', 1 FROM fa_cities WHERE code = '140400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140404', '上党区', 1 FROM fa_cities WHERE code = '140400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140405', '屯留区', 1 FROM fa_cities WHERE code = '140400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140406', '潞城区', 1 FROM fa_cities WHERE code = '140400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140423', '襄垣县', 1 FROM fa_cities WHERE code = '140400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140425', '平顺县', 1 FROM fa_cities WHERE code = '140400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140426', '黎城县', 1 FROM fa_cities WHERE code = '140400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140427', '壶关县', 1 FROM fa_cities WHERE code = '140400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140428', '长子县', 1 FROM fa_cities WHERE code = '140400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140429', '武乡县', 1 FROM fa_cities WHERE code = '140400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140430', '沁县', 1 FROM fa_cities WHERE code = '140400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140431', '沁源县', 1 FROM fa_cities WHERE code = '140400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140502', '城区', 1 FROM fa_cities WHERE code = '140500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140521', '沁水县', 1 FROM fa_cities WHERE code = '140500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140522', '阳城县', 1 FROM fa_cities WHERE code = '140500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140524', '陵川县', 1 FROM fa_cities WHERE code = '140500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140525', '泽州县', 1 FROM fa_cities WHERE code = '140500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140581', '高平市', 1 FROM fa_cities WHERE code = '140500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140602', '朔城区', 1 FROM fa_cities WHERE code = '140600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140603', '平鲁区', 1 FROM fa_cities WHERE code = '140600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140621', '山阴县', 1 FROM fa_cities WHERE code = '140600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140622', '应县', 1 FROM fa_cities WHERE code = '140600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140623', '右玉县', 1 FROM fa_cities WHERE code = '140600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140671', '山西朔州经济开发区', 1 FROM fa_cities WHERE code = '140600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140681', '怀仁市', 1 FROM fa_cities WHERE code = '140600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140702', '榆次区', 1 FROM fa_cities WHERE code = '140700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140703', '太谷区', 1 FROM fa_cities WHERE code = '140700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140721', '榆社县', 1 FROM fa_cities WHERE code = '140700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140722', '左权县', 1 FROM fa_cities WHERE code = '140700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140723', '和顺县', 1 FROM fa_cities WHERE code = '140700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140724', '昔阳县', 1 FROM fa_cities WHERE code = '140700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140725', '寿阳县', 1 FROM fa_cities WHERE code = '140700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140727', '祁县', 1 FROM fa_cities WHERE code = '140700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140728', '平遥县', 1 FROM fa_cities WHERE code = '140700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140729', '灵石县', 1 FROM fa_cities WHERE code = '140700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140781', '介休市', 1 FROM fa_cities WHERE code = '140700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140802', '盐湖区', 1 FROM fa_cities WHERE code = '140800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140821', '临猗县', 1 FROM fa_cities WHERE code = '140800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140822', '万荣县', 1 FROM fa_cities WHERE code = '140800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140823', '闻喜县', 1 FROM fa_cities WHERE code = '140800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140824', '稷山县', 1 FROM fa_cities WHERE code = '140800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140825', '新绛县', 1 FROM fa_cities WHERE code = '140800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140826', '绛县', 1 FROM fa_cities WHERE code = '140800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140827', '垣曲县', 1 FROM fa_cities WHERE code = '140800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140828', '夏县', 1 FROM fa_cities WHERE code = '140800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140829', '平陆县', 1 FROM fa_cities WHERE code = '140800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140830', '芮城县', 1 FROM fa_cities WHERE code = '140800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140881', '永济市', 1 FROM fa_cities WHERE code = '140800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140882', '河津市', 1 FROM fa_cities WHERE code = '140800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140902', '忻府区', 1 FROM fa_cities WHERE code = '140900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140921', '定襄县', 1 FROM fa_cities WHERE code = '140900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140922', '五台县', 1 FROM fa_cities WHERE code = '140900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140923', '代县', 1 FROM fa_cities WHERE code = '140900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140924', '繁峙县', 1 FROM fa_cities WHERE code = '140900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140925', '宁武县', 1 FROM fa_cities WHERE code = '140900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140926', '静乐县', 1 FROM fa_cities WHERE code = '140900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140927', '神池县', 1 FROM fa_cities WHERE code = '140900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140928', '五寨县', 1 FROM fa_cities WHERE code = '140900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140929', '岢岚县', 1 FROM fa_cities WHERE code = '140900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140930', '河曲县', 1 FROM fa_cities WHERE code = '140900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140931', '保德县', 1 FROM fa_cities WHERE code = '140900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140932', '偏关县', 1 FROM fa_cities WHERE code = '140900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140971', '五台山风景名胜区', 1 FROM fa_cities WHERE code = '140900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '140981', '原平市', 1 FROM fa_cities WHERE code = '140900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '141002', '尧都区', 1 FROM fa_cities WHERE code = '141000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '141021', '曲沃县', 1 FROM fa_cities WHERE code = '141000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '141022', '翼城县', 1 FROM fa_cities WHERE code = '141000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '141023', '襄汾县', 1 FROM fa_cities WHERE code = '141000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '141024', '洪洞县', 1 FROM fa_cities WHERE code = '141000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '141025', '古县', 1 FROM fa_cities WHERE code = '141000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '141026', '安泽县', 1 FROM fa_cities WHERE code = '141000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '141027', '浮山县', 1 FROM fa_cities WHERE code = '141000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '141028', '吉县', 1 FROM fa_cities WHERE code = '141000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '141029', '乡宁县', 1 FROM fa_cities WHERE code = '141000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '141030', '大宁县', 1 FROM fa_cities WHERE code = '141000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '141031', '隰县', 1 FROM fa_cities WHERE code = '141000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '141032', '永和县', 1 FROM fa_cities WHERE code = '141000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '141033', '蒲县', 1 FROM fa_cities WHERE code = '141000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '141034', '汾西县', 1 FROM fa_cities WHERE code = '141000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '141081', '侯马市', 1 FROM fa_cities WHERE code = '141000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '141082', '霍州市', 1 FROM fa_cities WHERE code = '141000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '141102', '离石区', 1 FROM fa_cities WHERE code = '141100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '141121', '文水县', 1 FROM fa_cities WHERE code = '141100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '141122', '交城县', 1 FROM fa_cities WHERE code = '141100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '141123', '兴县', 1 FROM fa_cities WHERE code = '141100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '141124', '临县', 1 FROM fa_cities WHERE code = '141100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '141125', '柳林县', 1 FROM fa_cities WHERE code = '141100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '141126', '石楼县', 1 FROM fa_cities WHERE code = '141100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '141127', '岚县', 1 FROM fa_cities WHERE code = '141100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '141128', '方山县', 1 FROM fa_cities WHERE code = '141100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '141129', '中阳县', 1 FROM fa_cities WHERE code = '141100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '141130', '交口县', 1 FROM fa_cities WHERE code = '141100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '141181', '孝义市', 1 FROM fa_cities WHERE code = '141100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '141182', '汾阳市', 1 FROM fa_cities WHERE code = '141100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150102', '新城区', 1 FROM fa_cities WHERE code = '150100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150103', '回民区', 1 FROM fa_cities WHERE code = '150100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150104', '玉泉区', 1 FROM fa_cities WHERE code = '150100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150105', '赛罕区', 1 FROM fa_cities WHERE code = '150100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150121', '土默特左旗', 1 FROM fa_cities WHERE code = '150100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150122', '托克托县', 1 FROM fa_cities WHERE code = '150100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150123', '和林格尔县', 1 FROM fa_cities WHERE code = '150100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150124', '清水河县', 1 FROM fa_cities WHERE code = '150100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150125', '武川县', 1 FROM fa_cities WHERE code = '150100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150172', '呼和浩特经济技术开发区', 1 FROM fa_cities WHERE code = '150100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150202', '东河区', 1 FROM fa_cities WHERE code = '150200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150203', '昆都仑区', 1 FROM fa_cities WHERE code = '150200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150204', '青山区', 1 FROM fa_cities WHERE code = '150200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150205', '石拐区', 1 FROM fa_cities WHERE code = '150200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150206', '白云鄂博矿区', 1 FROM fa_cities WHERE code = '150200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150207', '九原区', 1 FROM fa_cities WHERE code = '150200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150221', '土默特右旗', 1 FROM fa_cities WHERE code = '150200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150222', '固阳县', 1 FROM fa_cities WHERE code = '150200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150223', '达尔罕茂明安联合旗', 1 FROM fa_cities WHERE code = '150200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150271', '包头稀土高新技术产业开发区', 1 FROM fa_cities WHERE code = '150200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150302', '海勃湾区', 1 FROM fa_cities WHERE code = '150300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150303', '海南区', 1 FROM fa_cities WHERE code = '150300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150304', '乌达区', 1 FROM fa_cities WHERE code = '150300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150402', '红山区', 1 FROM fa_cities WHERE code = '150400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150403', '元宝山区', 1 FROM fa_cities WHERE code = '150400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150404', '松山区', 1 FROM fa_cities WHERE code = '150400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150421', '阿鲁科尔沁旗', 1 FROM fa_cities WHERE code = '150400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150422', '巴林左旗', 1 FROM fa_cities WHERE code = '150400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150423', '巴林右旗', 1 FROM fa_cities WHERE code = '150400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150424', '林西县', 1 FROM fa_cities WHERE code = '150400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150425', '克什克腾旗', 1 FROM fa_cities WHERE code = '150400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150426', '翁牛特旗', 1 FROM fa_cities WHERE code = '150400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150428', '喀喇沁旗', 1 FROM fa_cities WHERE code = '150400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150429', '宁城县', 1 FROM fa_cities WHERE code = '150400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150430', '敖汉旗', 1 FROM fa_cities WHERE code = '150400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150502', '科尔沁区', 1 FROM fa_cities WHERE code = '150500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150521', '科尔沁左翼中旗', 1 FROM fa_cities WHERE code = '150500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150522', '科尔沁左翼后旗', 1 FROM fa_cities WHERE code = '150500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150523', '开鲁县', 1 FROM fa_cities WHERE code = '150500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150524', '库伦旗', 1 FROM fa_cities WHERE code = '150500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150525', '奈曼旗', 1 FROM fa_cities WHERE code = '150500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150526', '扎鲁特旗', 1 FROM fa_cities WHERE code = '150500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150571', '通辽经济技术开发区', 1 FROM fa_cities WHERE code = '150500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150581', '霍林郭勒市', 1 FROM fa_cities WHERE code = '150500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150602', '东胜区', 1 FROM fa_cities WHERE code = '150600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150603', '康巴什区', 1 FROM fa_cities WHERE code = '150600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150621', '达拉特旗', 1 FROM fa_cities WHERE code = '150600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150622', '准格尔旗', 1 FROM fa_cities WHERE code = '150600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150623', '鄂托克前旗', 1 FROM fa_cities WHERE code = '150600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150624', '鄂托克旗', 1 FROM fa_cities WHERE code = '150600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150625', '杭锦旗', 1 FROM fa_cities WHERE code = '150600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150626', '乌审旗', 1 FROM fa_cities WHERE code = '150600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150627', '伊金霍洛旗', 1 FROM fa_cities WHERE code = '150600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150702', '海拉尔区', 1 FROM fa_cities WHERE code = '150700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150703', '扎赉诺尔区', 1 FROM fa_cities WHERE code = '150700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150721', '阿荣旗', 1 FROM fa_cities WHERE code = '150700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150722', '莫力达瓦达斡尔族自治旗', 1 FROM fa_cities WHERE code = '150700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150723', '鄂伦春自治旗', 1 FROM fa_cities WHERE code = '150700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150724', '鄂温克族自治旗', 1 FROM fa_cities WHERE code = '150700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150725', '陈巴尔虎旗', 1 FROM fa_cities WHERE code = '150700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150726', '新巴尔虎左旗', 1 FROM fa_cities WHERE code = '150700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150727', '新巴尔虎右旗', 1 FROM fa_cities WHERE code = '150700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150781', '满洲里市', 1 FROM fa_cities WHERE code = '150700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150782', '牙克石市', 1 FROM fa_cities WHERE code = '150700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150783', '扎兰屯市', 1 FROM fa_cities WHERE code = '150700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150784', '额尔古纳市', 1 FROM fa_cities WHERE code = '150700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150785', '根河市', 1 FROM fa_cities WHERE code = '150700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150802', '临河区', 1 FROM fa_cities WHERE code = '150800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150821', '五原县', 1 FROM fa_cities WHERE code = '150800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150822', '磴口县', 1 FROM fa_cities WHERE code = '150800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150823', '乌拉特前旗', 1 FROM fa_cities WHERE code = '150800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150824', '乌拉特中旗', 1 FROM fa_cities WHERE code = '150800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150825', '乌拉特后旗', 1 FROM fa_cities WHERE code = '150800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150826', '杭锦后旗', 1 FROM fa_cities WHERE code = '150800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150902', '集宁区', 1 FROM fa_cities WHERE code = '150900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150921', '卓资县', 1 FROM fa_cities WHERE code = '150900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150922', '化德县', 1 FROM fa_cities WHERE code = '150900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150923', '商都县', 1 FROM fa_cities WHERE code = '150900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150924', '兴和县', 1 FROM fa_cities WHERE code = '150900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150925', '凉城县', 1 FROM fa_cities WHERE code = '150900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150926', '察哈尔右翼前旗', 1 FROM fa_cities WHERE code = '150900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150927', '察哈尔右翼中旗', 1 FROM fa_cities WHERE code = '150900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150928', '察哈尔右翼后旗', 1 FROM fa_cities WHERE code = '150900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150929', '四子王旗', 1 FROM fa_cities WHERE code = '150900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '150981', '丰镇市', 1 FROM fa_cities WHERE code = '150900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '152201', '乌兰浩特市', 1 FROM fa_cities WHERE code = '152200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '152202', '阿尔山市', 1 FROM fa_cities WHERE code = '152200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '152221', '科尔沁右翼前旗', 1 FROM fa_cities WHERE code = '152200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '152222', '科尔沁右翼中旗', 1 FROM fa_cities WHERE code = '152200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '152223', '扎赉特旗', 1 FROM fa_cities WHERE code = '152200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '152224', '突泉县', 1 FROM fa_cities WHERE code = '152200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '152501', '二连浩特市', 1 FROM fa_cities WHERE code = '152500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '152502', '锡林浩特市', 1 FROM fa_cities WHERE code = '152500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '152522', '阿巴嘎旗', 1 FROM fa_cities WHERE code = '152500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '152523', '苏尼特左旗', 1 FROM fa_cities WHERE code = '152500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '152524', '苏尼特右旗', 1 FROM fa_cities WHERE code = '152500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '152525', '东乌珠穆沁旗', 1 FROM fa_cities WHERE code = '152500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '152526', '西乌珠穆沁旗', 1 FROM fa_cities WHERE code = '152500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '152527', '太仆寺旗', 1 FROM fa_cities WHERE code = '152500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '152528', '镶黄旗', 1 FROM fa_cities WHERE code = '152500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '152529', '正镶白旗', 1 FROM fa_cities WHERE code = '152500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '152530', '正蓝旗', 1 FROM fa_cities WHERE code = '152500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '152531', '多伦县', 1 FROM fa_cities WHERE code = '152500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '152571', '乌拉盖管理区管委会', 1 FROM fa_cities WHERE code = '152500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '152921', '阿拉善左旗', 1 FROM fa_cities WHERE code = '152900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '152922', '阿拉善右旗', 1 FROM fa_cities WHERE code = '152900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '152923', '额济纳旗', 1 FROM fa_cities WHERE code = '152900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '152971', '内蒙古阿拉善高新技术产业开发区', 1 FROM fa_cities WHERE code = '152900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210102', '和平区', 1 FROM fa_cities WHERE code = '210100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210103', '沈河区', 1 FROM fa_cities WHERE code = '210100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210104', '大东区', 1 FROM fa_cities WHERE code = '210100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210105', '皇姑区', 1 FROM fa_cities WHERE code = '210100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210106', '铁西区', 1 FROM fa_cities WHERE code = '210100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210111', '苏家屯区', 1 FROM fa_cities WHERE code = '210100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210112', '浑南区', 1 FROM fa_cities WHERE code = '210100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210113', '沈北新区', 1 FROM fa_cities WHERE code = '210100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210114', '于洪区', 1 FROM fa_cities WHERE code = '210100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210115', '辽中区', 1 FROM fa_cities WHERE code = '210100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210123', '康平县', 1 FROM fa_cities WHERE code = '210100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210124', '法库县', 1 FROM fa_cities WHERE code = '210100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210181', '新民市', 1 FROM fa_cities WHERE code = '210100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210202', '中山区', 1 FROM fa_cities WHERE code = '210200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210203', '西岗区', 1 FROM fa_cities WHERE code = '210200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210204', '沙河口区', 1 FROM fa_cities WHERE code = '210200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210211', '甘井子区', 1 FROM fa_cities WHERE code = '210200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210212', '旅顺口区', 1 FROM fa_cities WHERE code = '210200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210213', '金州区', 1 FROM fa_cities WHERE code = '210200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210214', '普兰店区', 1 FROM fa_cities WHERE code = '210200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210224', '长海县', 1 FROM fa_cities WHERE code = '210200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210281', '瓦房店市', 1 FROM fa_cities WHERE code = '210200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210283', '庄河市', 1 FROM fa_cities WHERE code = '210200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210302', '铁东区', 1 FROM fa_cities WHERE code = '210300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210303', '铁西区', 1 FROM fa_cities WHERE code = '210300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210304', '立山区', 1 FROM fa_cities WHERE code = '210300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210311', '千山区', 1 FROM fa_cities WHERE code = '210300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210321', '台安县', 1 FROM fa_cities WHERE code = '210300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210323', '岫岩满族自治县', 1 FROM fa_cities WHERE code = '210300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210381', '海城市', 1 FROM fa_cities WHERE code = '210300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210402', '新抚区', 1 FROM fa_cities WHERE code = '210400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210403', '东洲区', 1 FROM fa_cities WHERE code = '210400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210404', '望花区', 1 FROM fa_cities WHERE code = '210400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210411', '顺城区', 1 FROM fa_cities WHERE code = '210400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210421', '抚顺县', 1 FROM fa_cities WHERE code = '210400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210422', '新宾满族自治县', 1 FROM fa_cities WHERE code = '210400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210423', '清原满族自治县', 1 FROM fa_cities WHERE code = '210400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210502', '平山区', 1 FROM fa_cities WHERE code = '210500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210503', '溪湖区', 1 FROM fa_cities WHERE code = '210500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210504', '明山区', 1 FROM fa_cities WHERE code = '210500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210505', '南芬区', 1 FROM fa_cities WHERE code = '210500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210521', '本溪满族自治县', 1 FROM fa_cities WHERE code = '210500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210522', '桓仁满族自治县', 1 FROM fa_cities WHERE code = '210500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210602', '元宝区', 1 FROM fa_cities WHERE code = '210600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210603', '振兴区', 1 FROM fa_cities WHERE code = '210600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210604', '振安区', 1 FROM fa_cities WHERE code = '210600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210624', '宽甸满族自治县', 1 FROM fa_cities WHERE code = '210600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210681', '东港市', 1 FROM fa_cities WHERE code = '210600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210682', '凤城市', 1 FROM fa_cities WHERE code = '210600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210702', '古塔区', 1 FROM fa_cities WHERE code = '210700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210703', '凌河区', 1 FROM fa_cities WHERE code = '210700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210711', '太和区', 1 FROM fa_cities WHERE code = '210700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210726', '黑山县', 1 FROM fa_cities WHERE code = '210700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210727', '义县', 1 FROM fa_cities WHERE code = '210700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210781', '凌海市', 1 FROM fa_cities WHERE code = '210700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210782', '北镇市', 1 FROM fa_cities WHERE code = '210700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210802', '站前区', 1 FROM fa_cities WHERE code = '210800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210803', '西市区', 1 FROM fa_cities WHERE code = '210800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210804', '鲅鱼圈区', 1 FROM fa_cities WHERE code = '210800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210811', '老边区', 1 FROM fa_cities WHERE code = '210800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210881', '盖州市', 1 FROM fa_cities WHERE code = '210800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210882', '大石桥市', 1 FROM fa_cities WHERE code = '210800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210902', '海州区', 1 FROM fa_cities WHERE code = '210900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210903', '新邱区', 1 FROM fa_cities WHERE code = '210900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210904', '太平区', 1 FROM fa_cities WHERE code = '210900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210905', '清河门区', 1 FROM fa_cities WHERE code = '210900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210911', '细河区', 1 FROM fa_cities WHERE code = '210900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210921', '阜新蒙古族自治县', 1 FROM fa_cities WHERE code = '210900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '210922', '彰武县', 1 FROM fa_cities WHERE code = '210900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '211002', '白塔区', 1 FROM fa_cities WHERE code = '211000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '211003', '文圣区', 1 FROM fa_cities WHERE code = '211000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '211004', '宏伟区', 1 FROM fa_cities WHERE code = '211000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '211005', '弓长岭区', 1 FROM fa_cities WHERE code = '211000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '211011', '太子河区', 1 FROM fa_cities WHERE code = '211000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '211021', '辽阳县', 1 FROM fa_cities WHERE code = '211000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '211081', '灯塔市', 1 FROM fa_cities WHERE code = '211000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '211102', '双台子区', 1 FROM fa_cities WHERE code = '211100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '211103', '兴隆台区', 1 FROM fa_cities WHERE code = '211100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '211104', '大洼区', 1 FROM fa_cities WHERE code = '211100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '211122', '盘山县', 1 FROM fa_cities WHERE code = '211100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '211202', '银州区', 1 FROM fa_cities WHERE code = '211200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '211204', '清河区', 1 FROM fa_cities WHERE code = '211200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '211221', '铁岭县', 1 FROM fa_cities WHERE code = '211200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '211223', '西丰县', 1 FROM fa_cities WHERE code = '211200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '211224', '昌图县', 1 FROM fa_cities WHERE code = '211200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '211281', '调兵山市', 1 FROM fa_cities WHERE code = '211200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '211282', '开原市', 1 FROM fa_cities WHERE code = '211200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '211302', '双塔区', 1 FROM fa_cities WHERE code = '211300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '211303', '龙城区', 1 FROM fa_cities WHERE code = '211300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '211321', '朝阳县', 1 FROM fa_cities WHERE code = '211300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '211322', '建平县', 1 FROM fa_cities WHERE code = '211300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '211324', '喀喇沁左翼蒙古族自治县', 1 FROM fa_cities WHERE code = '211300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '211381', '北票市', 1 FROM fa_cities WHERE code = '211300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '211382', '凌源市', 1 FROM fa_cities WHERE code = '211300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '211402', '连山区', 1 FROM fa_cities WHERE code = '211400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '211403', '龙港区', 1 FROM fa_cities WHERE code = '211400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '211404', '南票区', 1 FROM fa_cities WHERE code = '211400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '211421', '绥中县', 1 FROM fa_cities WHERE code = '211400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '211422', '建昌县', 1 FROM fa_cities WHERE code = '211400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '211481', '兴城市', 1 FROM fa_cities WHERE code = '211400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220102', '南关区', 1 FROM fa_cities WHERE code = '220100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220103', '宽城区', 1 FROM fa_cities WHERE code = '220100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220104', '朝阳区', 1 FROM fa_cities WHERE code = '220100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220105', '二道区', 1 FROM fa_cities WHERE code = '220100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220106', '绿园区', 1 FROM fa_cities WHERE code = '220100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220112', '双阳区', 1 FROM fa_cities WHERE code = '220100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220113', '九台区', 1 FROM fa_cities WHERE code = '220100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220122', '农安县', 1 FROM fa_cities WHERE code = '220100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220171', '长春经济技术开发区', 1 FROM fa_cities WHERE code = '220100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220172', '长春净月高新技术产业开发区', 1 FROM fa_cities WHERE code = '220100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220173', '长春高新技术产业开发区', 1 FROM fa_cities WHERE code = '220100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220174', '长春汽车经济技术开发区', 1 FROM fa_cities WHERE code = '220100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220182', '榆树市', 1 FROM fa_cities WHERE code = '220100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220183', '德惠市', 1 FROM fa_cities WHERE code = '220100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220184', '公主岭市', 1 FROM fa_cities WHERE code = '220100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220202', '昌邑区', 1 FROM fa_cities WHERE code = '220200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220203', '龙潭区', 1 FROM fa_cities WHERE code = '220200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220204', '船营区', 1 FROM fa_cities WHERE code = '220200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220211', '丰满区', 1 FROM fa_cities WHERE code = '220200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220221', '永吉县', 1 FROM fa_cities WHERE code = '220200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220271', '吉林经济开发区', 1 FROM fa_cities WHERE code = '220200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220272', '吉林高新技术产业开发区', 1 FROM fa_cities WHERE code = '220200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220273', '吉林中国新加坡食品区', 1 FROM fa_cities WHERE code = '220200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220281', '蛟河市', 1 FROM fa_cities WHERE code = '220200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220282', '桦甸市', 1 FROM fa_cities WHERE code = '220200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220283', '舒兰市', 1 FROM fa_cities WHERE code = '220200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220284', '磐石市', 1 FROM fa_cities WHERE code = '220200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220302', '铁西区', 1 FROM fa_cities WHERE code = '220300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220303', '铁东区', 1 FROM fa_cities WHERE code = '220300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220322', '梨树县', 1 FROM fa_cities WHERE code = '220300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220323', '伊通满族自治县', 1 FROM fa_cities WHERE code = '220300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220382', '双辽市', 1 FROM fa_cities WHERE code = '220300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220402', '龙山区', 1 FROM fa_cities WHERE code = '220400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220403', '西安区', 1 FROM fa_cities WHERE code = '220400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220421', '东丰县', 1 FROM fa_cities WHERE code = '220400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220422', '东辽县', 1 FROM fa_cities WHERE code = '220400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220502', '东昌区', 1 FROM fa_cities WHERE code = '220500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220503', '二道江区', 1 FROM fa_cities WHERE code = '220500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220521', '通化县', 1 FROM fa_cities WHERE code = '220500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220523', '辉南县', 1 FROM fa_cities WHERE code = '220500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220524', '柳河县', 1 FROM fa_cities WHERE code = '220500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220581', '梅河口市', 1 FROM fa_cities WHERE code = '220500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220582', '集安市', 1 FROM fa_cities WHERE code = '220500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220602', '浑江区', 1 FROM fa_cities WHERE code = '220600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220605', '江源区', 1 FROM fa_cities WHERE code = '220600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220621', '抚松县', 1 FROM fa_cities WHERE code = '220600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220622', '靖宇县', 1 FROM fa_cities WHERE code = '220600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220623', '长白朝鲜族自治县', 1 FROM fa_cities WHERE code = '220600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220681', '临江市', 1 FROM fa_cities WHERE code = '220600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220702', '宁江区', 1 FROM fa_cities WHERE code = '220700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220721', '前郭尔罗斯蒙古族自治县', 1 FROM fa_cities WHERE code = '220700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220722', '长岭县', 1 FROM fa_cities WHERE code = '220700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220723', '乾安县', 1 FROM fa_cities WHERE code = '220700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220771', '吉林松原经济开发区', 1 FROM fa_cities WHERE code = '220700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220781', '扶余市', 1 FROM fa_cities WHERE code = '220700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220802', '洮北区', 1 FROM fa_cities WHERE code = '220800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220821', '镇赉县', 1 FROM fa_cities WHERE code = '220800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220822', '通榆县', 1 FROM fa_cities WHERE code = '220800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220871', '吉林白城经济开发区', 1 FROM fa_cities WHERE code = '220800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220881', '洮南市', 1 FROM fa_cities WHERE code = '220800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '220882', '大安市', 1 FROM fa_cities WHERE code = '220800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '222401', '延吉市', 1 FROM fa_cities WHERE code = '222400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '222402', '图们市', 1 FROM fa_cities WHERE code = '222400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '222403', '敦化市', 1 FROM fa_cities WHERE code = '222400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '222404', '珲春市', 1 FROM fa_cities WHERE code = '222400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '222405', '龙井市', 1 FROM fa_cities WHERE code = '222400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '222406', '和龙市', 1 FROM fa_cities WHERE code = '222400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '222424', '汪清县', 1 FROM fa_cities WHERE code = '222400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '222426', '安图县', 1 FROM fa_cities WHERE code = '222400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230102', '道里区', 1 FROM fa_cities WHERE code = '230100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230103', '南岗区', 1 FROM fa_cities WHERE code = '230100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230104', '道外区', 1 FROM fa_cities WHERE code = '230100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230108', '平房区', 1 FROM fa_cities WHERE code = '230100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230109', '松北区', 1 FROM fa_cities WHERE code = '230100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230110', '香坊区', 1 FROM fa_cities WHERE code = '230100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230111', '呼兰区', 1 FROM fa_cities WHERE code = '230100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230112', '阿城区', 1 FROM fa_cities WHERE code = '230100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230113', '双城区', 1 FROM fa_cities WHERE code = '230100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230123', '依兰县', 1 FROM fa_cities WHERE code = '230100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230124', '方正县', 1 FROM fa_cities WHERE code = '230100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230125', '宾县', 1 FROM fa_cities WHERE code = '230100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230126', '巴彦县', 1 FROM fa_cities WHERE code = '230100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230127', '木兰县', 1 FROM fa_cities WHERE code = '230100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230128', '通河县', 1 FROM fa_cities WHERE code = '230100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230129', '延寿县', 1 FROM fa_cities WHERE code = '230100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230183', '尚志市', 1 FROM fa_cities WHERE code = '230100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230184', '五常市', 1 FROM fa_cities WHERE code = '230100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230202', '龙沙区', 1 FROM fa_cities WHERE code = '230200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230203', '建华区', 1 FROM fa_cities WHERE code = '230200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230204', '铁锋区', 1 FROM fa_cities WHERE code = '230200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230205', '昂昂溪区', 1 FROM fa_cities WHERE code = '230200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230206', '富拉尔基区', 1 FROM fa_cities WHERE code = '230200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230207', '碾子山区', 1 FROM fa_cities WHERE code = '230200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230208', '梅里斯达斡尔族区', 1 FROM fa_cities WHERE code = '230200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230221', '龙江县', 1 FROM fa_cities WHERE code = '230200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230223', '依安县', 1 FROM fa_cities WHERE code = '230200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230224', '泰来县', 1 FROM fa_cities WHERE code = '230200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230225', '甘南县', 1 FROM fa_cities WHERE code = '230200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230227', '富裕县', 1 FROM fa_cities WHERE code = '230200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230229', '克山县', 1 FROM fa_cities WHERE code = '230200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230230', '克东县', 1 FROM fa_cities WHERE code = '230200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230231', '拜泉县', 1 FROM fa_cities WHERE code = '230200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230281', '讷河市', 1 FROM fa_cities WHERE code = '230200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230302', '鸡冠区', 1 FROM fa_cities WHERE code = '230300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230303', '恒山区', 1 FROM fa_cities WHERE code = '230300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230304', '滴道区', 1 FROM fa_cities WHERE code = '230300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230305', '梨树区', 1 FROM fa_cities WHERE code = '230300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230306', '城子河区', 1 FROM fa_cities WHERE code = '230300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230307', '麻山区', 1 FROM fa_cities WHERE code = '230300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230321', '鸡东县', 1 FROM fa_cities WHERE code = '230300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230381', '虎林市', 1 FROM fa_cities WHERE code = '230300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230382', '密山市', 1 FROM fa_cities WHERE code = '230300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230402', '向阳区', 1 FROM fa_cities WHERE code = '230400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230403', '工农区', 1 FROM fa_cities WHERE code = '230400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230404', '南山区', 1 FROM fa_cities WHERE code = '230400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230405', '兴安区', 1 FROM fa_cities WHERE code = '230400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230406', '东山区', 1 FROM fa_cities WHERE code = '230400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230407', '兴山区', 1 FROM fa_cities WHERE code = '230400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230421', '萝北县', 1 FROM fa_cities WHERE code = '230400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230422', '绥滨县', 1 FROM fa_cities WHERE code = '230400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230502', '尖山区', 1 FROM fa_cities WHERE code = '230500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230503', '岭东区', 1 FROM fa_cities WHERE code = '230500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230505', '四方台区', 1 FROM fa_cities WHERE code = '230500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230506', '宝山区', 1 FROM fa_cities WHERE code = '230500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230521', '集贤县', 1 FROM fa_cities WHERE code = '230500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230522', '友谊县', 1 FROM fa_cities WHERE code = '230500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230523', '宝清县', 1 FROM fa_cities WHERE code = '230500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230524', '饶河县', 1 FROM fa_cities WHERE code = '230500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230602', '萨尔图区', 1 FROM fa_cities WHERE code = '230600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230603', '龙凤区', 1 FROM fa_cities WHERE code = '230600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230604', '让胡路区', 1 FROM fa_cities WHERE code = '230600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230605', '红岗区', 1 FROM fa_cities WHERE code = '230600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230606', '大同区', 1 FROM fa_cities WHERE code = '230600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230621', '肇州县', 1 FROM fa_cities WHERE code = '230600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230622', '肇源县', 1 FROM fa_cities WHERE code = '230600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230623', '林甸县', 1 FROM fa_cities WHERE code = '230600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230624', '杜尔伯特蒙古族自治县', 1 FROM fa_cities WHERE code = '230600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230671', '大庆高新技术产业开发区', 1 FROM fa_cities WHERE code = '230600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230717', '伊美区', 1 FROM fa_cities WHERE code = '230700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230718', '乌翠区', 1 FROM fa_cities WHERE code = '230700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230719', '友好区', 1 FROM fa_cities WHERE code = '230700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230722', '嘉荫县', 1 FROM fa_cities WHERE code = '230700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230723', '汤旺县', 1 FROM fa_cities WHERE code = '230700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230724', '丰林县', 1 FROM fa_cities WHERE code = '230700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230725', '大箐山县', 1 FROM fa_cities WHERE code = '230700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230726', '南岔县', 1 FROM fa_cities WHERE code = '230700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230751', '金林区', 1 FROM fa_cities WHERE code = '230700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230781', '铁力市', 1 FROM fa_cities WHERE code = '230700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230803', '向阳区', 1 FROM fa_cities WHERE code = '230800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230804', '前进区', 1 FROM fa_cities WHERE code = '230800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230805', '东风区', 1 FROM fa_cities WHERE code = '230800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230811', '郊区', 1 FROM fa_cities WHERE code = '230800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230822', '桦南县', 1 FROM fa_cities WHERE code = '230800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230826', '桦川县', 1 FROM fa_cities WHERE code = '230800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230828', '汤原县', 1 FROM fa_cities WHERE code = '230800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230881', '同江市', 1 FROM fa_cities WHERE code = '230800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230882', '富锦市', 1 FROM fa_cities WHERE code = '230800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230883', '抚远市', 1 FROM fa_cities WHERE code = '230800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230902', '新兴区', 1 FROM fa_cities WHERE code = '230900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230903', '桃山区', 1 FROM fa_cities WHERE code = '230900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230904', '茄子河区', 1 FROM fa_cities WHERE code = '230900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '230921', '勃利县', 1 FROM fa_cities WHERE code = '230900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '231002', '东安区', 1 FROM fa_cities WHERE code = '231000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '231003', '阳明区', 1 FROM fa_cities WHERE code = '231000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '231004', '爱民区', 1 FROM fa_cities WHERE code = '231000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '231005', '西安区', 1 FROM fa_cities WHERE code = '231000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '231025', '林口县', 1 FROM fa_cities WHERE code = '231000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '231081', '绥芬河市', 1 FROM fa_cities WHERE code = '231000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '231083', '海林市', 1 FROM fa_cities WHERE code = '231000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '231084', '宁安市', 1 FROM fa_cities WHERE code = '231000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '231085', '穆棱市', 1 FROM fa_cities WHERE code = '231000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '231086', '东宁市', 1 FROM fa_cities WHERE code = '231000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '231102', '爱辉区', 1 FROM fa_cities WHERE code = '231100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '231123', '逊克县', 1 FROM fa_cities WHERE code = '231100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '231124', '孙吴县', 1 FROM fa_cities WHERE code = '231100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '231181', '北安市', 1 FROM fa_cities WHERE code = '231100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '231182', '五大连池市', 1 FROM fa_cities WHERE code = '231100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '231183', '嫩江市', 1 FROM fa_cities WHERE code = '231100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '231202', '北林区', 1 FROM fa_cities WHERE code = '231200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '231221', '望奎县', 1 FROM fa_cities WHERE code = '231200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '231222', '兰西县', 1 FROM fa_cities WHERE code = '231200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '231223', '青冈县', 1 FROM fa_cities WHERE code = '231200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '231224', '庆安县', 1 FROM fa_cities WHERE code = '231200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '231225', '明水县', 1 FROM fa_cities WHERE code = '231200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '231226', '绥棱县', 1 FROM fa_cities WHERE code = '231200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '231281', '安达市', 1 FROM fa_cities WHERE code = '231200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '231282', '肇东市', 1 FROM fa_cities WHERE code = '231200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '231283', '海伦市', 1 FROM fa_cities WHERE code = '231200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '232701', '漠河市', 1 FROM fa_cities WHERE code = '232700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '232721', '呼玛县', 1 FROM fa_cities WHERE code = '232700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '232722', '塔河县', 1 FROM fa_cities WHERE code = '232700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '232761', '加格达奇区', 1 FROM fa_cities WHERE code = '232700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '232762', '松岭区', 1 FROM fa_cities WHERE code = '232700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '232763', '新林区', 1 FROM fa_cities WHERE code = '232700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '232764', '呼中区', 1 FROM fa_cities WHERE code = '232700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '310101', '黄浦区', 1 FROM fa_cities WHERE code = '310100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '310104', '徐汇区', 1 FROM fa_cities WHERE code = '310100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '310105', '长宁区', 1 FROM fa_cities WHERE code = '310100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '310106', '静安区', 1 FROM fa_cities WHERE code = '310100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '310107', '普陀区', 1 FROM fa_cities WHERE code = '310100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '310109', '虹口区', 1 FROM fa_cities WHERE code = '310100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '310110', '杨浦区', 1 FROM fa_cities WHERE code = '310100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '310112', '闵行区', 1 FROM fa_cities WHERE code = '310100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '310113', '宝山区', 1 FROM fa_cities WHERE code = '310100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '310114', '嘉定区', 1 FROM fa_cities WHERE code = '310100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '310115', '浦东新区', 1 FROM fa_cities WHERE code = '310100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '310116', '金山区', 1 FROM fa_cities WHERE code = '310100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '310117', '松江区', 1 FROM fa_cities WHERE code = '310100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '310118', '青浦区', 1 FROM fa_cities WHERE code = '310100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '310120', '奉贤区', 1 FROM fa_cities WHERE code = '310100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '310151', '崇明区', 1 FROM fa_cities WHERE code = '310100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320102', '玄武区', 1 FROM fa_cities WHERE code = '320100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320104', '秦淮区', 1 FROM fa_cities WHERE code = '320100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320105', '建邺区', 1 FROM fa_cities WHERE code = '320100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320106', '鼓楼区', 1 FROM fa_cities WHERE code = '320100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320111', '浦口区', 1 FROM fa_cities WHERE code = '320100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320113', '栖霞区', 1 FROM fa_cities WHERE code = '320100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320114', '雨花台区', 1 FROM fa_cities WHERE code = '320100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320115', '江宁区', 1 FROM fa_cities WHERE code = '320100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320116', '六合区', 1 FROM fa_cities WHERE code = '320100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320117', '溧水区', 1 FROM fa_cities WHERE code = '320100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320118', '高淳区', 1 FROM fa_cities WHERE code = '320100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320205', '锡山区', 1 FROM fa_cities WHERE code = '320200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320206', '惠山区', 1 FROM fa_cities WHERE code = '320200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320211', '滨湖区', 1 FROM fa_cities WHERE code = '320200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320213', '梁溪区', 1 FROM fa_cities WHERE code = '320200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320214', '新吴区', 1 FROM fa_cities WHERE code = '320200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320281', '江阴市', 1 FROM fa_cities WHERE code = '320200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320282', '宜兴市', 1 FROM fa_cities WHERE code = '320200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320302', '鼓楼区', 1 FROM fa_cities WHERE code = '320300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320303', '云龙区', 1 FROM fa_cities WHERE code = '320300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320305', '贾汪区', 1 FROM fa_cities WHERE code = '320300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320311', '泉山区', 1 FROM fa_cities WHERE code = '320300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320312', '铜山区', 1 FROM fa_cities WHERE code = '320300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320321', '丰县', 1 FROM fa_cities WHERE code = '320300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320322', '沛县', 1 FROM fa_cities WHERE code = '320300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320324', '睢宁县', 1 FROM fa_cities WHERE code = '320300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320371', '徐州经济技术开发区', 1 FROM fa_cities WHERE code = '320300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320381', '新沂市', 1 FROM fa_cities WHERE code = '320300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320382', '邳州市', 1 FROM fa_cities WHERE code = '320300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320402', '天宁区', 1 FROM fa_cities WHERE code = '320400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320404', '钟楼区', 1 FROM fa_cities WHERE code = '320400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320411', '新北区', 1 FROM fa_cities WHERE code = '320400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320412', '武进区', 1 FROM fa_cities WHERE code = '320400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320413', '金坛区', 1 FROM fa_cities WHERE code = '320400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320481', '溧阳市', 1 FROM fa_cities WHERE code = '320400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320505', '虎丘区', 1 FROM fa_cities WHERE code = '320500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320506', '吴中区', 1 FROM fa_cities WHERE code = '320500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320507', '相城区', 1 FROM fa_cities WHERE code = '320500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320508', '姑苏区', 1 FROM fa_cities WHERE code = '320500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320509', '吴江区', 1 FROM fa_cities WHERE code = '320500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320576', '苏州工业园区', 1 FROM fa_cities WHERE code = '320500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320581', '常熟市', 1 FROM fa_cities WHERE code = '320500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320582', '张家港市', 1 FROM fa_cities WHERE code = '320500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320583', '昆山市', 1 FROM fa_cities WHERE code = '320500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320585', '太仓市', 1 FROM fa_cities WHERE code = '320500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320612', '通州区', 1 FROM fa_cities WHERE code = '320600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320613', '崇川区', 1 FROM fa_cities WHERE code = '320600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320614', '海门区', 1 FROM fa_cities WHERE code = '320600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320623', '如东县', 1 FROM fa_cities WHERE code = '320600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320671', '南通经济技术开发区', 1 FROM fa_cities WHERE code = '320600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320681', '启东市', 1 FROM fa_cities WHERE code = '320600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320682', '如皋市', 1 FROM fa_cities WHERE code = '320600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320685', '海安市', 1 FROM fa_cities WHERE code = '320600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320703', '连云区', 1 FROM fa_cities WHERE code = '320700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320706', '海州区', 1 FROM fa_cities WHERE code = '320700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320707', '赣榆区', 1 FROM fa_cities WHERE code = '320700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320722', '东海县', 1 FROM fa_cities WHERE code = '320700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320723', '灌云县', 1 FROM fa_cities WHERE code = '320700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320724', '灌南县', 1 FROM fa_cities WHERE code = '320700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320771', '连云港经济技术开发区', 1 FROM fa_cities WHERE code = '320700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320803', '淮安区', 1 FROM fa_cities WHERE code = '320800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320804', '淮阴区', 1 FROM fa_cities WHERE code = '320800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320812', '清江浦区', 1 FROM fa_cities WHERE code = '320800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320813', '洪泽区', 1 FROM fa_cities WHERE code = '320800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320826', '涟水县', 1 FROM fa_cities WHERE code = '320800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320830', '盱眙县', 1 FROM fa_cities WHERE code = '320800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320831', '金湖县', 1 FROM fa_cities WHERE code = '320800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320871', '淮安经济技术开发区', 1 FROM fa_cities WHERE code = '320800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320902', '亭湖区', 1 FROM fa_cities WHERE code = '320900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320903', '盐都区', 1 FROM fa_cities WHERE code = '320900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320904', '大丰区', 1 FROM fa_cities WHERE code = '320900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320921', '响水县', 1 FROM fa_cities WHERE code = '320900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320922', '滨海县', 1 FROM fa_cities WHERE code = '320900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320923', '阜宁县', 1 FROM fa_cities WHERE code = '320900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320924', '射阳县', 1 FROM fa_cities WHERE code = '320900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320925', '建湖县', 1 FROM fa_cities WHERE code = '320900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320971', '盐城经济技术开发区', 1 FROM fa_cities WHERE code = '320900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '320981', '东台市', 1 FROM fa_cities WHERE code = '320900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '321002', '广陵区', 1 FROM fa_cities WHERE code = '321000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '321003', '邗江区', 1 FROM fa_cities WHERE code = '321000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '321012', '江都区', 1 FROM fa_cities WHERE code = '321000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '321023', '宝应县', 1 FROM fa_cities WHERE code = '321000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '321071', '扬州经济技术开发区', 1 FROM fa_cities WHERE code = '321000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '321081', '仪征市', 1 FROM fa_cities WHERE code = '321000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '321084', '高邮市', 1 FROM fa_cities WHERE code = '321000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '321102', '京口区', 1 FROM fa_cities WHERE code = '321100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '321111', '润州区', 1 FROM fa_cities WHERE code = '321100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '321112', '丹徒区', 1 FROM fa_cities WHERE code = '321100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '321171', '镇江新区', 1 FROM fa_cities WHERE code = '321100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '321181', '丹阳市', 1 FROM fa_cities WHERE code = '321100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '321182', '扬中市', 1 FROM fa_cities WHERE code = '321100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '321183', '句容市', 1 FROM fa_cities WHERE code = '321100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '321202', '海陵区', 1 FROM fa_cities WHERE code = '321200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '321203', '高港区', 1 FROM fa_cities WHERE code = '321200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '321204', '姜堰区', 1 FROM fa_cities WHERE code = '321200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '321281', '兴化市', 1 FROM fa_cities WHERE code = '321200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '321282', '靖江市', 1 FROM fa_cities WHERE code = '321200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '321283', '泰兴市', 1 FROM fa_cities WHERE code = '321200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '321302', '宿城区', 1 FROM fa_cities WHERE code = '321300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '321311', '宿豫区', 1 FROM fa_cities WHERE code = '321300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '321322', '沭阳县', 1 FROM fa_cities WHERE code = '321300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '321323', '泗阳县', 1 FROM fa_cities WHERE code = '321300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '321324', '泗洪县', 1 FROM fa_cities WHERE code = '321300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '321371', '宿迁经济技术开发区', 1 FROM fa_cities WHERE code = '321300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330102', '上城区', 1 FROM fa_cities WHERE code = '330100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330105', '拱墅区', 1 FROM fa_cities WHERE code = '330100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330106', '西湖区', 1 FROM fa_cities WHERE code = '330100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330108', '滨江区', 1 FROM fa_cities WHERE code = '330100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330109', '萧山区', 1 FROM fa_cities WHERE code = '330100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330110', '余杭区', 1 FROM fa_cities WHERE code = '330100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330111', '富阳区', 1 FROM fa_cities WHERE code = '330100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330112', '临安区', 1 FROM fa_cities WHERE code = '330100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330113', '临平区', 1 FROM fa_cities WHERE code = '330100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330114', '钱塘区', 1 FROM fa_cities WHERE code = '330100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330122', '桐庐县', 1 FROM fa_cities WHERE code = '330100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330127', '淳安县', 1 FROM fa_cities WHERE code = '330100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330182', '建德市', 1 FROM fa_cities WHERE code = '330100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330203', '海曙区', 1 FROM fa_cities WHERE code = '330200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330205', '江北区', 1 FROM fa_cities WHERE code = '330200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330206', '北仑区', 1 FROM fa_cities WHERE code = '330200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330211', '镇海区', 1 FROM fa_cities WHERE code = '330200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330212', '鄞州区', 1 FROM fa_cities WHERE code = '330200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330213', '奉化区', 1 FROM fa_cities WHERE code = '330200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330225', '象山县', 1 FROM fa_cities WHERE code = '330200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330226', '宁海县', 1 FROM fa_cities WHERE code = '330200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330281', '余姚市', 1 FROM fa_cities WHERE code = '330200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330282', '慈溪市', 1 FROM fa_cities WHERE code = '330200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330302', '鹿城区', 1 FROM fa_cities WHERE code = '330300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330303', '龙湾区', 1 FROM fa_cities WHERE code = '330300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330304', '瓯海区', 1 FROM fa_cities WHERE code = '330300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330305', '洞头区', 1 FROM fa_cities WHERE code = '330300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330324', '永嘉县', 1 FROM fa_cities WHERE code = '330300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330326', '平阳县', 1 FROM fa_cities WHERE code = '330300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330327', '苍南县', 1 FROM fa_cities WHERE code = '330300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330328', '文成县', 1 FROM fa_cities WHERE code = '330300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330329', '泰顺县', 1 FROM fa_cities WHERE code = '330300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330381', '瑞安市', 1 FROM fa_cities WHERE code = '330300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330382', '乐清市', 1 FROM fa_cities WHERE code = '330300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330383', '龙港市', 1 FROM fa_cities WHERE code = '330300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330402', '南湖区', 1 FROM fa_cities WHERE code = '330400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330411', '秀洲区', 1 FROM fa_cities WHERE code = '330400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330421', '嘉善县', 1 FROM fa_cities WHERE code = '330400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330424', '海盐县', 1 FROM fa_cities WHERE code = '330400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330481', '海宁市', 1 FROM fa_cities WHERE code = '330400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330482', '平湖市', 1 FROM fa_cities WHERE code = '330400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330483', '桐乡市', 1 FROM fa_cities WHERE code = '330400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330502', '吴兴区', 1 FROM fa_cities WHERE code = '330500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330503', '南浔区', 1 FROM fa_cities WHERE code = '330500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330521', '德清县', 1 FROM fa_cities WHERE code = '330500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330522', '长兴县', 1 FROM fa_cities WHERE code = '330500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330523', '安吉县', 1 FROM fa_cities WHERE code = '330500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330602', '越城区', 1 FROM fa_cities WHERE code = '330600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330603', '柯桥区', 1 FROM fa_cities WHERE code = '330600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330604', '上虞区', 1 FROM fa_cities WHERE code = '330600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330624', '新昌县', 1 FROM fa_cities WHERE code = '330600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330681', '诸暨市', 1 FROM fa_cities WHERE code = '330600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330683', '嵊州市', 1 FROM fa_cities WHERE code = '330600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330702', '婺城区', 1 FROM fa_cities WHERE code = '330700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330703', '金东区', 1 FROM fa_cities WHERE code = '330700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330723', '武义县', 1 FROM fa_cities WHERE code = '330700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330726', '浦江县', 1 FROM fa_cities WHERE code = '330700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330727', '磐安县', 1 FROM fa_cities WHERE code = '330700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330781', '兰溪市', 1 FROM fa_cities WHERE code = '330700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330782', '义乌市', 1 FROM fa_cities WHERE code = '330700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330783', '东阳市', 1 FROM fa_cities WHERE code = '330700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330784', '永康市', 1 FROM fa_cities WHERE code = '330700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330802', '柯城区', 1 FROM fa_cities WHERE code = '330800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330803', '衢江区', 1 FROM fa_cities WHERE code = '330800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330822', '常山县', 1 FROM fa_cities WHERE code = '330800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330824', '开化县', 1 FROM fa_cities WHERE code = '330800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330825', '龙游县', 1 FROM fa_cities WHERE code = '330800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330881', '江山市', 1 FROM fa_cities WHERE code = '330800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330902', '定海区', 1 FROM fa_cities WHERE code = '330900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330903', '普陀区', 1 FROM fa_cities WHERE code = '330900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330921', '岱山县', 1 FROM fa_cities WHERE code = '330900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '330922', '嵊泗县', 1 FROM fa_cities WHERE code = '330900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '331002', '椒江区', 1 FROM fa_cities WHERE code = '331000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '331003', '黄岩区', 1 FROM fa_cities WHERE code = '331000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '331004', '路桥区', 1 FROM fa_cities WHERE code = '331000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '331022', '三门县', 1 FROM fa_cities WHERE code = '331000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '331023', '天台县', 1 FROM fa_cities WHERE code = '331000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '331024', '仙居县', 1 FROM fa_cities WHERE code = '331000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '331081', '温岭市', 1 FROM fa_cities WHERE code = '331000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '331082', '临海市', 1 FROM fa_cities WHERE code = '331000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '331083', '玉环市', 1 FROM fa_cities WHERE code = '331000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '331102', '莲都区', 1 FROM fa_cities WHERE code = '331100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '331121', '青田县', 1 FROM fa_cities WHERE code = '331100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '331122', '缙云县', 1 FROM fa_cities WHERE code = '331100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '331123', '遂昌县', 1 FROM fa_cities WHERE code = '331100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '331124', '松阳县', 1 FROM fa_cities WHERE code = '331100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '331125', '云和县', 1 FROM fa_cities WHERE code = '331100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '331126', '庆元县', 1 FROM fa_cities WHERE code = '331100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '331127', '景宁畲族自治县', 1 FROM fa_cities WHERE code = '331100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '331181', '龙泉市', 1 FROM fa_cities WHERE code = '331100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340102', '瑶海区', 1 FROM fa_cities WHERE code = '340100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340103', '庐阳区', 1 FROM fa_cities WHERE code = '340100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340104', '蜀山区', 1 FROM fa_cities WHERE code = '340100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340111', '包河区', 1 FROM fa_cities WHERE code = '340100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340121', '长丰县', 1 FROM fa_cities WHERE code = '340100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340122', '肥东县', 1 FROM fa_cities WHERE code = '340100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340123', '肥西县', 1 FROM fa_cities WHERE code = '340100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340124', '庐江县', 1 FROM fa_cities WHERE code = '340100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340176', '合肥高新技术产业开发区', 1 FROM fa_cities WHERE code = '340100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340177', '合肥经济技术开发区', 1 FROM fa_cities WHERE code = '340100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340178', '合肥新站高新技术产业开发区', 1 FROM fa_cities WHERE code = '340100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340181', '巢湖市', 1 FROM fa_cities WHERE code = '340100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340202', '镜湖区', 1 FROM fa_cities WHERE code = '340200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340207', '鸠江区', 1 FROM fa_cities WHERE code = '340200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340209', '弋江区', 1 FROM fa_cities WHERE code = '340200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340210', '湾沚区', 1 FROM fa_cities WHERE code = '340200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340212', '繁昌区', 1 FROM fa_cities WHERE code = '340200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340223', '南陵县', 1 FROM fa_cities WHERE code = '340200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340271', '芜湖经济技术开发区', 1 FROM fa_cities WHERE code = '340200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340272', '安徽芜湖三山经济开发区', 1 FROM fa_cities WHERE code = '340200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340281', '无为市', 1 FROM fa_cities WHERE code = '340200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340302', '龙子湖区', 1 FROM fa_cities WHERE code = '340300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340303', '蚌山区', 1 FROM fa_cities WHERE code = '340300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340304', '禹会区', 1 FROM fa_cities WHERE code = '340300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340311', '淮上区', 1 FROM fa_cities WHERE code = '340300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340321', '怀远县', 1 FROM fa_cities WHERE code = '340300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340322', '五河县', 1 FROM fa_cities WHERE code = '340300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340323', '固镇县', 1 FROM fa_cities WHERE code = '340300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340371', '蚌埠市高新技术开发区', 1 FROM fa_cities WHERE code = '340300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340372', '蚌埠市经济开发区', 1 FROM fa_cities WHERE code = '340300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340402', '大通区', 1 FROM fa_cities WHERE code = '340400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340403', '田家庵区', 1 FROM fa_cities WHERE code = '340400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340404', '谢家集区', 1 FROM fa_cities WHERE code = '340400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340405', '八公山区', 1 FROM fa_cities WHERE code = '340400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340406', '潘集区', 1 FROM fa_cities WHERE code = '340400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340421', '凤台县', 1 FROM fa_cities WHERE code = '340400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340422', '寿县', 1 FROM fa_cities WHERE code = '340400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340503', '花山区', 1 FROM fa_cities WHERE code = '340500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340504', '雨山区', 1 FROM fa_cities WHERE code = '340500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340506', '博望区', 1 FROM fa_cities WHERE code = '340500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340521', '当涂县', 1 FROM fa_cities WHERE code = '340500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340522', '含山县', 1 FROM fa_cities WHERE code = '340500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340523', '和县', 1 FROM fa_cities WHERE code = '340500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340602', '杜集区', 1 FROM fa_cities WHERE code = '340600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340603', '相山区', 1 FROM fa_cities WHERE code = '340600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340604', '烈山区', 1 FROM fa_cities WHERE code = '340600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340621', '濉溪县', 1 FROM fa_cities WHERE code = '340600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340705', '铜官区', 1 FROM fa_cities WHERE code = '340700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340706', '义安区', 1 FROM fa_cities WHERE code = '340700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340711', '郊区', 1 FROM fa_cities WHERE code = '340700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340722', '枞阳县', 1 FROM fa_cities WHERE code = '340700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340802', '迎江区', 1 FROM fa_cities WHERE code = '340800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340803', '大观区', 1 FROM fa_cities WHERE code = '340800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340811', '宜秀区', 1 FROM fa_cities WHERE code = '340800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340822', '怀宁县', 1 FROM fa_cities WHERE code = '340800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340825', '太湖县', 1 FROM fa_cities WHERE code = '340800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340826', '宿松县', 1 FROM fa_cities WHERE code = '340800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340827', '望江县', 1 FROM fa_cities WHERE code = '340800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340828', '岳西县', 1 FROM fa_cities WHERE code = '340800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340871', '安徽安庆经济开发区', 1 FROM fa_cities WHERE code = '340800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340881', '桐城市', 1 FROM fa_cities WHERE code = '340800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '340882', '潜山市', 1 FROM fa_cities WHERE code = '340800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341002', '屯溪区', 1 FROM fa_cities WHERE code = '341000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341003', '黄山区', 1 FROM fa_cities WHERE code = '341000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341004', '徽州区', 1 FROM fa_cities WHERE code = '341000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341021', '歙县', 1 FROM fa_cities WHERE code = '341000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341022', '休宁县', 1 FROM fa_cities WHERE code = '341000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341023', '黟县', 1 FROM fa_cities WHERE code = '341000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341024', '祁门县', 1 FROM fa_cities WHERE code = '341000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341102', '琅琊区', 1 FROM fa_cities WHERE code = '341100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341103', '南谯区', 1 FROM fa_cities WHERE code = '341100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341122', '来安县', 1 FROM fa_cities WHERE code = '341100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341124', '全椒县', 1 FROM fa_cities WHERE code = '341100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341125', '定远县', 1 FROM fa_cities WHERE code = '341100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341126', '凤阳县', 1 FROM fa_cities WHERE code = '341100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341171', '中新苏滁高新技术产业开发区', 1 FROM fa_cities WHERE code = '341100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341172', '滁州经济技术开发区', 1 FROM fa_cities WHERE code = '341100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341181', '天长市', 1 FROM fa_cities WHERE code = '341100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341182', '明光市', 1 FROM fa_cities WHERE code = '341100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341202', '颍州区', 1 FROM fa_cities WHERE code = '341200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341203', '颍东区', 1 FROM fa_cities WHERE code = '341200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341204', '颍泉区', 1 FROM fa_cities WHERE code = '341200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341221', '临泉县', 1 FROM fa_cities WHERE code = '341200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341222', '太和县', 1 FROM fa_cities WHERE code = '341200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341225', '阜南县', 1 FROM fa_cities WHERE code = '341200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341226', '颍上县', 1 FROM fa_cities WHERE code = '341200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341271', '阜阳合肥现代产业园区', 1 FROM fa_cities WHERE code = '341200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341272', '阜阳经济技术开发区', 1 FROM fa_cities WHERE code = '341200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341282', '界首市', 1 FROM fa_cities WHERE code = '341200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341302', '埇桥区', 1 FROM fa_cities WHERE code = '341300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341321', '砀山县', 1 FROM fa_cities WHERE code = '341300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341322', '萧县', 1 FROM fa_cities WHERE code = '341300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341323', '灵璧县', 1 FROM fa_cities WHERE code = '341300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341324', '泗县', 1 FROM fa_cities WHERE code = '341300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341371', '宿州马鞍山现代产业园区', 1 FROM fa_cities WHERE code = '341300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341372', '宿州经济技术开发区', 1 FROM fa_cities WHERE code = '341300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341502', '金安区', 1 FROM fa_cities WHERE code = '341500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341503', '裕安区', 1 FROM fa_cities WHERE code = '341500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341504', '叶集区', 1 FROM fa_cities WHERE code = '341500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341522', '霍邱县', 1 FROM fa_cities WHERE code = '341500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341523', '舒城县', 1 FROM fa_cities WHERE code = '341500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341524', '金寨县', 1 FROM fa_cities WHERE code = '341500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341525', '霍山县', 1 FROM fa_cities WHERE code = '341500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341602', '谯城区', 1 FROM fa_cities WHERE code = '341600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341621', '涡阳县', 1 FROM fa_cities WHERE code = '341600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341622', '蒙城县', 1 FROM fa_cities WHERE code = '341600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341623', '利辛县', 1 FROM fa_cities WHERE code = '341600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341702', '贵池区', 1 FROM fa_cities WHERE code = '341700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341721', '东至县', 1 FROM fa_cities WHERE code = '341700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341722', '石台县', 1 FROM fa_cities WHERE code = '341700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341723', '青阳县', 1 FROM fa_cities WHERE code = '341700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341802', '宣州区', 1 FROM fa_cities WHERE code = '341800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341821', '郎溪县', 1 FROM fa_cities WHERE code = '341800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341823', '泾县', 1 FROM fa_cities WHERE code = '341800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341824', '绩溪县', 1 FROM fa_cities WHERE code = '341800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341825', '旌德县', 1 FROM fa_cities WHERE code = '341800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341871', '宣城市经济开发区', 1 FROM fa_cities WHERE code = '341800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341881', '宁国市', 1 FROM fa_cities WHERE code = '341800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '341882', '广德市', 1 FROM fa_cities WHERE code = '341800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350102', '鼓楼区', 1 FROM fa_cities WHERE code = '350100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350103', '台江区', 1 FROM fa_cities WHERE code = '350100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350104', '仓山区', 1 FROM fa_cities WHERE code = '350100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350105', '马尾区', 1 FROM fa_cities WHERE code = '350100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350111', '晋安区', 1 FROM fa_cities WHERE code = '350100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350112', '长乐区', 1 FROM fa_cities WHERE code = '350100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350121', '闽侯县', 1 FROM fa_cities WHERE code = '350100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350122', '连江县', 1 FROM fa_cities WHERE code = '350100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350123', '罗源县', 1 FROM fa_cities WHERE code = '350100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350124', '闽清县', 1 FROM fa_cities WHERE code = '350100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350125', '永泰县', 1 FROM fa_cities WHERE code = '350100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350128', '平潭县', 1 FROM fa_cities WHERE code = '350100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350181', '福清市', 1 FROM fa_cities WHERE code = '350100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350203', '思明区', 1 FROM fa_cities WHERE code = '350200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350205', '海沧区', 1 FROM fa_cities WHERE code = '350200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350206', '湖里区', 1 FROM fa_cities WHERE code = '350200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350211', '集美区', 1 FROM fa_cities WHERE code = '350200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350212', '同安区', 1 FROM fa_cities WHERE code = '350200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350213', '翔安区', 1 FROM fa_cities WHERE code = '350200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350302', '城厢区', 1 FROM fa_cities WHERE code = '350300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350303', '涵江区', 1 FROM fa_cities WHERE code = '350300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350304', '荔城区', 1 FROM fa_cities WHERE code = '350300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350305', '秀屿区', 1 FROM fa_cities WHERE code = '350300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350322', '仙游县', 1 FROM fa_cities WHERE code = '350300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350404', '三元区', 1 FROM fa_cities WHERE code = '350400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350405', '沙县区', 1 FROM fa_cities WHERE code = '350400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350421', '明溪县', 1 FROM fa_cities WHERE code = '350400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350423', '清流县', 1 FROM fa_cities WHERE code = '350400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350424', '宁化县', 1 FROM fa_cities WHERE code = '350400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350425', '大田县', 1 FROM fa_cities WHERE code = '350400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350426', '尤溪县', 1 FROM fa_cities WHERE code = '350400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350428', '将乐县', 1 FROM fa_cities WHERE code = '350400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350429', '泰宁县', 1 FROM fa_cities WHERE code = '350400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350430', '建宁县', 1 FROM fa_cities WHERE code = '350400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350481', '永安市', 1 FROM fa_cities WHERE code = '350400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350502', '鲤城区', 1 FROM fa_cities WHERE code = '350500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350503', '丰泽区', 1 FROM fa_cities WHERE code = '350500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350504', '洛江区', 1 FROM fa_cities WHERE code = '350500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350505', '泉港区', 1 FROM fa_cities WHERE code = '350500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350521', '惠安县', 1 FROM fa_cities WHERE code = '350500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350524', '安溪县', 1 FROM fa_cities WHERE code = '350500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350525', '永春县', 1 FROM fa_cities WHERE code = '350500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350526', '德化县', 1 FROM fa_cities WHERE code = '350500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350527', '金门县', 1 FROM fa_cities WHERE code = '350500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350581', '石狮市', 1 FROM fa_cities WHERE code = '350500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350582', '晋江市', 1 FROM fa_cities WHERE code = '350500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350583', '南安市', 1 FROM fa_cities WHERE code = '350500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350602', '芗城区', 1 FROM fa_cities WHERE code = '350600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350603', '龙文区', 1 FROM fa_cities WHERE code = '350600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350604', '龙海区', 1 FROM fa_cities WHERE code = '350600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350605', '长泰区', 1 FROM fa_cities WHERE code = '350600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350622', '云霄县', 1 FROM fa_cities WHERE code = '350600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350623', '漳浦县', 1 FROM fa_cities WHERE code = '350600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350624', '诏安县', 1 FROM fa_cities WHERE code = '350600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350626', '东山县', 1 FROM fa_cities WHERE code = '350600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350627', '南靖县', 1 FROM fa_cities WHERE code = '350600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350628', '平和县', 1 FROM fa_cities WHERE code = '350600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350629', '华安县', 1 FROM fa_cities WHERE code = '350600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350702', '延平区', 1 FROM fa_cities WHERE code = '350700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350703', '建阳区', 1 FROM fa_cities WHERE code = '350700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350721', '顺昌县', 1 FROM fa_cities WHERE code = '350700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350722', '浦城县', 1 FROM fa_cities WHERE code = '350700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350723', '光泽县', 1 FROM fa_cities WHERE code = '350700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350724', '松溪县', 1 FROM fa_cities WHERE code = '350700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350725', '政和县', 1 FROM fa_cities WHERE code = '350700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350781', '邵武市', 1 FROM fa_cities WHERE code = '350700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350782', '武夷山市', 1 FROM fa_cities WHERE code = '350700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350783', '建瓯市', 1 FROM fa_cities WHERE code = '350700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350802', '新罗区', 1 FROM fa_cities WHERE code = '350800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350803', '永定区', 1 FROM fa_cities WHERE code = '350800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350821', '长汀县', 1 FROM fa_cities WHERE code = '350800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350823', '上杭县', 1 FROM fa_cities WHERE code = '350800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350824', '武平县', 1 FROM fa_cities WHERE code = '350800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350825', '连城县', 1 FROM fa_cities WHERE code = '350800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350881', '漳平市', 1 FROM fa_cities WHERE code = '350800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350902', '蕉城区', 1 FROM fa_cities WHERE code = '350900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350921', '霞浦县', 1 FROM fa_cities WHERE code = '350900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350922', '古田县', 1 FROM fa_cities WHERE code = '350900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350923', '屏南县', 1 FROM fa_cities WHERE code = '350900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350924', '寿宁县', 1 FROM fa_cities WHERE code = '350900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350925', '周宁县', 1 FROM fa_cities WHERE code = '350900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350926', '柘荣县', 1 FROM fa_cities WHERE code = '350900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350981', '福安市', 1 FROM fa_cities WHERE code = '350900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '350982', '福鼎市', 1 FROM fa_cities WHERE code = '350900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360102', '东湖区', 1 FROM fa_cities WHERE code = '360100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360103', '西湖区', 1 FROM fa_cities WHERE code = '360100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360104', '青云谱区', 1 FROM fa_cities WHERE code = '360100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360111', '青山湖区', 1 FROM fa_cities WHERE code = '360100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360112', '新建区', 1 FROM fa_cities WHERE code = '360100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360113', '红谷滩区', 1 FROM fa_cities WHERE code = '360100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360121', '南昌县', 1 FROM fa_cities WHERE code = '360100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360123', '安义县', 1 FROM fa_cities WHERE code = '360100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360124', '进贤县', 1 FROM fa_cities WHERE code = '360100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360202', '昌江区', 1 FROM fa_cities WHERE code = '360200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360203', '珠山区', 1 FROM fa_cities WHERE code = '360200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360222', '浮梁县', 1 FROM fa_cities WHERE code = '360200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360281', '乐平市', 1 FROM fa_cities WHERE code = '360200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360302', '安源区', 1 FROM fa_cities WHERE code = '360300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360313', '湘东区', 1 FROM fa_cities WHERE code = '360300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360321', '莲花县', 1 FROM fa_cities WHERE code = '360300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360322', '上栗县', 1 FROM fa_cities WHERE code = '360300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360323', '芦溪县', 1 FROM fa_cities WHERE code = '360300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360402', '濂溪区', 1 FROM fa_cities WHERE code = '360400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360403', '浔阳区', 1 FROM fa_cities WHERE code = '360400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360404', '柴桑区', 1 FROM fa_cities WHERE code = '360400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360423', '武宁县', 1 FROM fa_cities WHERE code = '360400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360424', '修水县', 1 FROM fa_cities WHERE code = '360400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360425', '永修县', 1 FROM fa_cities WHERE code = '360400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360426', '德安县', 1 FROM fa_cities WHERE code = '360400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360428', '都昌县', 1 FROM fa_cities WHERE code = '360400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360429', '湖口县', 1 FROM fa_cities WHERE code = '360400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360430', '彭泽县', 1 FROM fa_cities WHERE code = '360400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360481', '瑞昌市', 1 FROM fa_cities WHERE code = '360400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360482', '共青城市', 1 FROM fa_cities WHERE code = '360400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360483', '庐山市', 1 FROM fa_cities WHERE code = '360400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360502', '渝水区', 1 FROM fa_cities WHERE code = '360500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360521', '分宜县', 1 FROM fa_cities WHERE code = '360500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360602', '月湖区', 1 FROM fa_cities WHERE code = '360600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360603', '余江区', 1 FROM fa_cities WHERE code = '360600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360681', '贵溪市', 1 FROM fa_cities WHERE code = '360600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360702', '章贡区', 1 FROM fa_cities WHERE code = '360700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360703', '南康区', 1 FROM fa_cities WHERE code = '360700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360704', '赣县区', 1 FROM fa_cities WHERE code = '360700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360722', '信丰县', 1 FROM fa_cities WHERE code = '360700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360723', '大余县', 1 FROM fa_cities WHERE code = '360700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360724', '上犹县', 1 FROM fa_cities WHERE code = '360700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360725', '崇义县', 1 FROM fa_cities WHERE code = '360700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360726', '安远县', 1 FROM fa_cities WHERE code = '360700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360728', '定南县', 1 FROM fa_cities WHERE code = '360700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360729', '全南县', 1 FROM fa_cities WHERE code = '360700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360730', '宁都县', 1 FROM fa_cities WHERE code = '360700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360731', '于都县', 1 FROM fa_cities WHERE code = '360700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360732', '兴国县', 1 FROM fa_cities WHERE code = '360700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360733', '会昌县', 1 FROM fa_cities WHERE code = '360700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360734', '寻乌县', 1 FROM fa_cities WHERE code = '360700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360735', '石城县', 1 FROM fa_cities WHERE code = '360700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360781', '瑞金市', 1 FROM fa_cities WHERE code = '360700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360783', '龙南市', 1 FROM fa_cities WHERE code = '360700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360802', '吉州区', 1 FROM fa_cities WHERE code = '360800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360803', '青原区', 1 FROM fa_cities WHERE code = '360800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360821', '吉安县', 1 FROM fa_cities WHERE code = '360800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360822', '吉水县', 1 FROM fa_cities WHERE code = '360800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360823', '峡江县', 1 FROM fa_cities WHERE code = '360800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360824', '新干县', 1 FROM fa_cities WHERE code = '360800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360825', '永丰县', 1 FROM fa_cities WHERE code = '360800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360826', '泰和县', 1 FROM fa_cities WHERE code = '360800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360827', '遂川县', 1 FROM fa_cities WHERE code = '360800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360828', '万安县', 1 FROM fa_cities WHERE code = '360800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360829', '安福县', 1 FROM fa_cities WHERE code = '360800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360830', '永新县', 1 FROM fa_cities WHERE code = '360800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360881', '井冈山市', 1 FROM fa_cities WHERE code = '360800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360902', '袁州区', 1 FROM fa_cities WHERE code = '360900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360921', '奉新县', 1 FROM fa_cities WHERE code = '360900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360922', '万载县', 1 FROM fa_cities WHERE code = '360900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360923', '上高县', 1 FROM fa_cities WHERE code = '360900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360924', '宜丰县', 1 FROM fa_cities WHERE code = '360900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360925', '靖安县', 1 FROM fa_cities WHERE code = '360900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360926', '铜鼓县', 1 FROM fa_cities WHERE code = '360900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360981', '丰城市', 1 FROM fa_cities WHERE code = '360900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360982', '樟树市', 1 FROM fa_cities WHERE code = '360900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '360983', '高安市', 1 FROM fa_cities WHERE code = '360900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '361002', '临川区', 1 FROM fa_cities WHERE code = '361000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '361003', '东乡区', 1 FROM fa_cities WHERE code = '361000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '361021', '南城县', 1 FROM fa_cities WHERE code = '361000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '361022', '黎川县', 1 FROM fa_cities WHERE code = '361000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '361023', '南丰县', 1 FROM fa_cities WHERE code = '361000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '361024', '崇仁县', 1 FROM fa_cities WHERE code = '361000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '361025', '乐安县', 1 FROM fa_cities WHERE code = '361000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '361026', '宜黄县', 1 FROM fa_cities WHERE code = '361000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '361027', '金溪县', 1 FROM fa_cities WHERE code = '361000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '361028', '资溪县', 1 FROM fa_cities WHERE code = '361000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '361030', '广昌县', 1 FROM fa_cities WHERE code = '361000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '361102', '信州区', 1 FROM fa_cities WHERE code = '361100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '361103', '广丰区', 1 FROM fa_cities WHERE code = '361100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '361104', '广信区', 1 FROM fa_cities WHERE code = '361100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '361123', '玉山县', 1 FROM fa_cities WHERE code = '361100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '361124', '铅山县', 1 FROM fa_cities WHERE code = '361100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '361125', '横峰县', 1 FROM fa_cities WHERE code = '361100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '361126', '弋阳县', 1 FROM fa_cities WHERE code = '361100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '361127', '余干县', 1 FROM fa_cities WHERE code = '361100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '361128', '鄱阳县', 1 FROM fa_cities WHERE code = '361100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '361129', '万年县', 1 FROM fa_cities WHERE code = '361100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '361130', '婺源县', 1 FROM fa_cities WHERE code = '361100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '361181', '德兴市', 1 FROM fa_cities WHERE code = '361100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370102', '历下区', 1 FROM fa_cities WHERE code = '370100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370103', '市中区', 1 FROM fa_cities WHERE code = '370100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370104', '槐荫区', 1 FROM fa_cities WHERE code = '370100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370105', '天桥区', 1 FROM fa_cities WHERE code = '370100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370112', '历城区', 1 FROM fa_cities WHERE code = '370100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370113', '长清区', 1 FROM fa_cities WHERE code = '370100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370114', '章丘区', 1 FROM fa_cities WHERE code = '370100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370115', '济阳区', 1 FROM fa_cities WHERE code = '370100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370116', '莱芜区', 1 FROM fa_cities WHERE code = '370100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370117', '钢城区', 1 FROM fa_cities WHERE code = '370100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370124', '平阴县', 1 FROM fa_cities WHERE code = '370100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370126', '商河县', 1 FROM fa_cities WHERE code = '370100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370176', '济南高新技术产业开发区', 1 FROM fa_cities WHERE code = '370100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370202', '市南区', 1 FROM fa_cities WHERE code = '370200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370203', '市北区', 1 FROM fa_cities WHERE code = '370200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370211', '黄岛区', 1 FROM fa_cities WHERE code = '370200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370212', '崂山区', 1 FROM fa_cities WHERE code = '370200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370213', '李沧区', 1 FROM fa_cities WHERE code = '370200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370214', '城阳区', 1 FROM fa_cities WHERE code = '370200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370215', '即墨区', 1 FROM fa_cities WHERE code = '370200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370281', '胶州市', 1 FROM fa_cities WHERE code = '370200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370283', '平度市', 1 FROM fa_cities WHERE code = '370200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370285', '莱西市', 1 FROM fa_cities WHERE code = '370200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370302', '淄川区', 1 FROM fa_cities WHERE code = '370300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370303', '张店区', 1 FROM fa_cities WHERE code = '370300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370304', '博山区', 1 FROM fa_cities WHERE code = '370300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370305', '临淄区', 1 FROM fa_cities WHERE code = '370300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370306', '周村区', 1 FROM fa_cities WHERE code = '370300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370321', '桓台县', 1 FROM fa_cities WHERE code = '370300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370322', '高青县', 1 FROM fa_cities WHERE code = '370300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370323', '沂源县', 1 FROM fa_cities WHERE code = '370300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370402', '市中区', 1 FROM fa_cities WHERE code = '370400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370403', '薛城区', 1 FROM fa_cities WHERE code = '370400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370404', '峄城区', 1 FROM fa_cities WHERE code = '370400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370405', '台儿庄区', 1 FROM fa_cities WHERE code = '370400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370406', '山亭区', 1 FROM fa_cities WHERE code = '370400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370481', '滕州市', 1 FROM fa_cities WHERE code = '370400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370502', '东营区', 1 FROM fa_cities WHERE code = '370500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370503', '河口区', 1 FROM fa_cities WHERE code = '370500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370505', '垦利区', 1 FROM fa_cities WHERE code = '370500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370522', '利津县', 1 FROM fa_cities WHERE code = '370500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370523', '广饶县', 1 FROM fa_cities WHERE code = '370500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370571', '东营经济技术开发区', 1 FROM fa_cities WHERE code = '370500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370572', '东营港经济开发区', 1 FROM fa_cities WHERE code = '370500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370602', '芝罘区', 1 FROM fa_cities WHERE code = '370600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370611', '福山区', 1 FROM fa_cities WHERE code = '370600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370612', '牟平区', 1 FROM fa_cities WHERE code = '370600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370613', '莱山区', 1 FROM fa_cities WHERE code = '370600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370614', '蓬莱区', 1 FROM fa_cities WHERE code = '370600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370671', '烟台高新技术产业开发区', 1 FROM fa_cities WHERE code = '370600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370676', '烟台经济技术开发区', 1 FROM fa_cities WHERE code = '370600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370681', '龙口市', 1 FROM fa_cities WHERE code = '370600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370682', '莱阳市', 1 FROM fa_cities WHERE code = '370600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370683', '莱州市', 1 FROM fa_cities WHERE code = '370600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370685', '招远市', 1 FROM fa_cities WHERE code = '370600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370686', '栖霞市', 1 FROM fa_cities WHERE code = '370600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370687', '海阳市', 1 FROM fa_cities WHERE code = '370600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370702', '潍城区', 1 FROM fa_cities WHERE code = '370700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370703', '寒亭区', 1 FROM fa_cities WHERE code = '370700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370704', '坊子区', 1 FROM fa_cities WHERE code = '370700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370705', '奎文区', 1 FROM fa_cities WHERE code = '370700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370724', '临朐县', 1 FROM fa_cities WHERE code = '370700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370725', '昌乐县', 1 FROM fa_cities WHERE code = '370700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370772', '潍坊滨海经济技术开发区', 1 FROM fa_cities WHERE code = '370700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370781', '青州市', 1 FROM fa_cities WHERE code = '370700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370782', '诸城市', 1 FROM fa_cities WHERE code = '370700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370783', '寿光市', 1 FROM fa_cities WHERE code = '370700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370784', '安丘市', 1 FROM fa_cities WHERE code = '370700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370785', '高密市', 1 FROM fa_cities WHERE code = '370700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370786', '昌邑市', 1 FROM fa_cities WHERE code = '370700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370811', '任城区', 1 FROM fa_cities WHERE code = '370800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370812', '兖州区', 1 FROM fa_cities WHERE code = '370800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370826', '微山县', 1 FROM fa_cities WHERE code = '370800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370827', '鱼台县', 1 FROM fa_cities WHERE code = '370800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370828', '金乡县', 1 FROM fa_cities WHERE code = '370800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370829', '嘉祥县', 1 FROM fa_cities WHERE code = '370800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370830', '汶上县', 1 FROM fa_cities WHERE code = '370800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370831', '泗水县', 1 FROM fa_cities WHERE code = '370800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370832', '梁山县', 1 FROM fa_cities WHERE code = '370800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370871', '济宁高新技术产业开发区', 1 FROM fa_cities WHERE code = '370800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370881', '曲阜市', 1 FROM fa_cities WHERE code = '370800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370883', '邹城市', 1 FROM fa_cities WHERE code = '370800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370902', '泰山区', 1 FROM fa_cities WHERE code = '370900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370911', '岱岳区', 1 FROM fa_cities WHERE code = '370900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370921', '宁阳县', 1 FROM fa_cities WHERE code = '370900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370923', '东平县', 1 FROM fa_cities WHERE code = '370900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370982', '新泰市', 1 FROM fa_cities WHERE code = '370900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '370983', '肥城市', 1 FROM fa_cities WHERE code = '370900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371002', '环翠区', 1 FROM fa_cities WHERE code = '371000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371003', '文登区', 1 FROM fa_cities WHERE code = '371000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371071', '威海火炬高技术产业开发区', 1 FROM fa_cities WHERE code = '371000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371072', '威海经济技术开发区', 1 FROM fa_cities WHERE code = '371000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371073', '威海临港经济技术开发区', 1 FROM fa_cities WHERE code = '371000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371082', '荣成市', 1 FROM fa_cities WHERE code = '371000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371083', '乳山市', 1 FROM fa_cities WHERE code = '371000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371102', '东港区', 1 FROM fa_cities WHERE code = '371100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371103', '岚山区', 1 FROM fa_cities WHERE code = '371100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371121', '五莲县', 1 FROM fa_cities WHERE code = '371100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371122', '莒县', 1 FROM fa_cities WHERE code = '371100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371171', '日照经济技术开发区', 1 FROM fa_cities WHERE code = '371100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371302', '兰山区', 1 FROM fa_cities WHERE code = '371300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371311', '罗庄区', 1 FROM fa_cities WHERE code = '371300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371312', '河东区', 1 FROM fa_cities WHERE code = '371300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371321', '沂南县', 1 FROM fa_cities WHERE code = '371300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371322', '郯城县', 1 FROM fa_cities WHERE code = '371300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371323', '沂水县', 1 FROM fa_cities WHERE code = '371300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371324', '兰陵县', 1 FROM fa_cities WHERE code = '371300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371325', '费县', 1 FROM fa_cities WHERE code = '371300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371326', '平邑县', 1 FROM fa_cities WHERE code = '371300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371327', '莒南县', 1 FROM fa_cities WHERE code = '371300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371328', '蒙阴县', 1 FROM fa_cities WHERE code = '371300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371329', '临沭县', 1 FROM fa_cities WHERE code = '371300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371371', '临沂高新技术产业开发区', 1 FROM fa_cities WHERE code = '371300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371402', '德城区', 1 FROM fa_cities WHERE code = '371400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371403', '陵城区', 1 FROM fa_cities WHERE code = '371400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371422', '宁津县', 1 FROM fa_cities WHERE code = '371400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371423', '庆云县', 1 FROM fa_cities WHERE code = '371400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371424', '临邑县', 1 FROM fa_cities WHERE code = '371400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371425', '齐河县', 1 FROM fa_cities WHERE code = '371400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371426', '平原县', 1 FROM fa_cities WHERE code = '371400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371427', '夏津县', 1 FROM fa_cities WHERE code = '371400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371428', '武城县', 1 FROM fa_cities WHERE code = '371400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371471', '德州天衢新区', 1 FROM fa_cities WHERE code = '371400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371481', '乐陵市', 1 FROM fa_cities WHERE code = '371400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371482', '禹城市', 1 FROM fa_cities WHERE code = '371400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371502', '东昌府区', 1 FROM fa_cities WHERE code = '371500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371503', '茌平区', 1 FROM fa_cities WHERE code = '371500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371521', '阳谷县', 1 FROM fa_cities WHERE code = '371500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371522', '莘县', 1 FROM fa_cities WHERE code = '371500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371524', '东阿县', 1 FROM fa_cities WHERE code = '371500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371525', '冠县', 1 FROM fa_cities WHERE code = '371500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371526', '高唐县', 1 FROM fa_cities WHERE code = '371500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371581', '临清市', 1 FROM fa_cities WHERE code = '371500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371602', '滨城区', 1 FROM fa_cities WHERE code = '371600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371603', '沾化区', 1 FROM fa_cities WHERE code = '371600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371621', '惠民县', 1 FROM fa_cities WHERE code = '371600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371622', '阳信县', 1 FROM fa_cities WHERE code = '371600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371623', '无棣县', 1 FROM fa_cities WHERE code = '371600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371625', '博兴县', 1 FROM fa_cities WHERE code = '371600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371681', '邹平市', 1 FROM fa_cities WHERE code = '371600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371702', '牡丹区', 1 FROM fa_cities WHERE code = '371700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371703', '定陶区', 1 FROM fa_cities WHERE code = '371700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371721', '曹县', 1 FROM fa_cities WHERE code = '371700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371722', '单县', 1 FROM fa_cities WHERE code = '371700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371723', '成武县', 1 FROM fa_cities WHERE code = '371700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371724', '巨野县', 1 FROM fa_cities WHERE code = '371700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371725', '郓城县', 1 FROM fa_cities WHERE code = '371700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371726', '鄄城县', 1 FROM fa_cities WHERE code = '371700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371728', '东明县', 1 FROM fa_cities WHERE code = '371700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371771', '菏泽经济技术开发区', 1 FROM fa_cities WHERE code = '371700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '371772', '菏泽高新技术开发区', 1 FROM fa_cities WHERE code = '371700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410102', '中原区', 1 FROM fa_cities WHERE code = '410100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410103', '二七区', 1 FROM fa_cities WHERE code = '410100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410104', '管城回族区', 1 FROM fa_cities WHERE code = '410100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410105', '金水区', 1 FROM fa_cities WHERE code = '410100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410106', '上街区', 1 FROM fa_cities WHERE code = '410100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410108', '惠济区', 1 FROM fa_cities WHERE code = '410100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410122', '中牟县', 1 FROM fa_cities WHERE code = '410100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410171', '郑州经济技术开发区', 1 FROM fa_cities WHERE code = '410100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410172', '郑州高新技术产业开发区', 1 FROM fa_cities WHERE code = '410100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410173', '郑州航空港经济综合实验区', 1 FROM fa_cities WHERE code = '410100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410181', '巩义市', 1 FROM fa_cities WHERE code = '410100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410182', '荥阳市', 1 FROM fa_cities WHERE code = '410100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410183', '新密市', 1 FROM fa_cities WHERE code = '410100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410184', '新郑市', 1 FROM fa_cities WHERE code = '410100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410185', '登封市', 1 FROM fa_cities WHERE code = '410100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410202', '龙亭区', 1 FROM fa_cities WHERE code = '410200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410203', '顺河回族区', 1 FROM fa_cities WHERE code = '410200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410204', '鼓楼区', 1 FROM fa_cities WHERE code = '410200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410205', '禹王台区', 1 FROM fa_cities WHERE code = '410200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410212', '祥符区', 1 FROM fa_cities WHERE code = '410200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410221', '杞县', 1 FROM fa_cities WHERE code = '410200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410222', '通许县', 1 FROM fa_cities WHERE code = '410200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410223', '尉氏县', 1 FROM fa_cities WHERE code = '410200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410225', '兰考县', 1 FROM fa_cities WHERE code = '410200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410302', '老城区', 1 FROM fa_cities WHERE code = '410300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410303', '西工区', 1 FROM fa_cities WHERE code = '410300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410304', '瀍河回族区', 1 FROM fa_cities WHERE code = '410300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410305', '涧西区', 1 FROM fa_cities WHERE code = '410300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410307', '偃师区', 1 FROM fa_cities WHERE code = '410300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410308', '孟津区', 1 FROM fa_cities WHERE code = '410300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410311', '洛龙区', 1 FROM fa_cities WHERE code = '410300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410323', '新安县', 1 FROM fa_cities WHERE code = '410300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410324', '栾川县', 1 FROM fa_cities WHERE code = '410300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410325', '嵩县', 1 FROM fa_cities WHERE code = '410300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410326', '汝阳县', 1 FROM fa_cities WHERE code = '410300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410327', '宜阳县', 1 FROM fa_cities WHERE code = '410300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410328', '洛宁县', 1 FROM fa_cities WHERE code = '410300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410329', '伊川县', 1 FROM fa_cities WHERE code = '410300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410371', '洛阳高新技术产业开发区', 1 FROM fa_cities WHERE code = '410300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410402', '新华区', 1 FROM fa_cities WHERE code = '410400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410403', '卫东区', 1 FROM fa_cities WHERE code = '410400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410404', '石龙区', 1 FROM fa_cities WHERE code = '410400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410411', '湛河区', 1 FROM fa_cities WHERE code = '410400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410421', '宝丰县', 1 FROM fa_cities WHERE code = '410400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410422', '叶县', 1 FROM fa_cities WHERE code = '410400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410423', '鲁山县', 1 FROM fa_cities WHERE code = '410400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410425', '郏县', 1 FROM fa_cities WHERE code = '410400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410471', '平顶山高新技术产业开发区', 1 FROM fa_cities WHERE code = '410400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410472', '平顶山市城乡一体化示范区', 1 FROM fa_cities WHERE code = '410400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410481', '舞钢市', 1 FROM fa_cities WHERE code = '410400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410482', '汝州市', 1 FROM fa_cities WHERE code = '410400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410502', '文峰区', 1 FROM fa_cities WHERE code = '410500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410503', '北关区', 1 FROM fa_cities WHERE code = '410500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410505', '殷都区', 1 FROM fa_cities WHERE code = '410500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410506', '龙安区', 1 FROM fa_cities WHERE code = '410500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410522', '安阳县', 1 FROM fa_cities WHERE code = '410500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410523', '汤阴县', 1 FROM fa_cities WHERE code = '410500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410526', '滑县', 1 FROM fa_cities WHERE code = '410500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410527', '内黄县', 1 FROM fa_cities WHERE code = '410500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410571', '安阳高新技术产业开发区', 1 FROM fa_cities WHERE code = '410500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410581', '林州市', 1 FROM fa_cities WHERE code = '410500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410602', '鹤山区', 1 FROM fa_cities WHERE code = '410600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410603', '山城区', 1 FROM fa_cities WHERE code = '410600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410611', '淇滨区', 1 FROM fa_cities WHERE code = '410600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410621', '浚县', 1 FROM fa_cities WHERE code = '410600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410622', '淇县', 1 FROM fa_cities WHERE code = '410600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410671', '鹤壁经济技术开发区', 1 FROM fa_cities WHERE code = '410600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410702', '红旗区', 1 FROM fa_cities WHERE code = '410700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410703', '卫滨区', 1 FROM fa_cities WHERE code = '410700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410704', '凤泉区', 1 FROM fa_cities WHERE code = '410700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410711', '牧野区', 1 FROM fa_cities WHERE code = '410700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410721', '新乡县', 1 FROM fa_cities WHERE code = '410700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410724', '获嘉县', 1 FROM fa_cities WHERE code = '410700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410725', '原阳县', 1 FROM fa_cities WHERE code = '410700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410726', '延津县', 1 FROM fa_cities WHERE code = '410700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410727', '封丘县', 1 FROM fa_cities WHERE code = '410700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410771', '新乡高新技术产业开发区', 1 FROM fa_cities WHERE code = '410700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410772', '新乡经济技术开发区', 1 FROM fa_cities WHERE code = '410700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410773', '新乡市平原城乡一体化示范区', 1 FROM fa_cities WHERE code = '410700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410781', '卫辉市', 1 FROM fa_cities WHERE code = '410700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410782', '辉县市', 1 FROM fa_cities WHERE code = '410700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410783', '长垣市', 1 FROM fa_cities WHERE code = '410700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410802', '解放区', 1 FROM fa_cities WHERE code = '410800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410803', '中站区', 1 FROM fa_cities WHERE code = '410800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410804', '马村区', 1 FROM fa_cities WHERE code = '410800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410811', '山阳区', 1 FROM fa_cities WHERE code = '410800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410821', '修武县', 1 FROM fa_cities WHERE code = '410800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410822', '博爱县', 1 FROM fa_cities WHERE code = '410800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410823', '武陟县', 1 FROM fa_cities WHERE code = '410800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410825', '温县', 1 FROM fa_cities WHERE code = '410800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410871', '焦作城乡一体化示范区', 1 FROM fa_cities WHERE code = '410800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410882', '沁阳市', 1 FROM fa_cities WHERE code = '410800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410883', '孟州市', 1 FROM fa_cities WHERE code = '410800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410902', '华龙区', 1 FROM fa_cities WHERE code = '410900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410922', '清丰县', 1 FROM fa_cities WHERE code = '410900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410923', '南乐县', 1 FROM fa_cities WHERE code = '410900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410926', '范县', 1 FROM fa_cities WHERE code = '410900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410927', '台前县', 1 FROM fa_cities WHERE code = '410900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410928', '濮阳县', 1 FROM fa_cities WHERE code = '410900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410971', '河南濮阳工业园区', 1 FROM fa_cities WHERE code = '410900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '410972', '濮阳经济技术开发区', 1 FROM fa_cities WHERE code = '410900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411002', '魏都区', 1 FROM fa_cities WHERE code = '411000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411003', '建安区', 1 FROM fa_cities WHERE code = '411000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411024', '鄢陵县', 1 FROM fa_cities WHERE code = '411000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411025', '襄城县', 1 FROM fa_cities WHERE code = '411000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411071', '许昌经济技术开发区', 1 FROM fa_cities WHERE code = '411000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411081', '禹州市', 1 FROM fa_cities WHERE code = '411000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411082', '长葛市', 1 FROM fa_cities WHERE code = '411000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411102', '源汇区', 1 FROM fa_cities WHERE code = '411100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411103', '郾城区', 1 FROM fa_cities WHERE code = '411100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411104', '召陵区', 1 FROM fa_cities WHERE code = '411100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411121', '舞阳县', 1 FROM fa_cities WHERE code = '411100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411122', '临颍县', 1 FROM fa_cities WHERE code = '411100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411171', '漯河经济技术开发区', 1 FROM fa_cities WHERE code = '411100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411202', '湖滨区', 1 FROM fa_cities WHERE code = '411200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411203', '陕州区', 1 FROM fa_cities WHERE code = '411200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411221', '渑池县', 1 FROM fa_cities WHERE code = '411200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411224', '卢氏县', 1 FROM fa_cities WHERE code = '411200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411271', '河南三门峡经济开发区', 1 FROM fa_cities WHERE code = '411200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411281', '义马市', 1 FROM fa_cities WHERE code = '411200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411282', '灵宝市', 1 FROM fa_cities WHERE code = '411200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411302', '宛城区', 1 FROM fa_cities WHERE code = '411300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411303', '卧龙区', 1 FROM fa_cities WHERE code = '411300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411321', '南召县', 1 FROM fa_cities WHERE code = '411300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411322', '方城县', 1 FROM fa_cities WHERE code = '411300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411323', '西峡县', 1 FROM fa_cities WHERE code = '411300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411324', '镇平县', 1 FROM fa_cities WHERE code = '411300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411325', '内乡县', 1 FROM fa_cities WHERE code = '411300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411326', '淅川县', 1 FROM fa_cities WHERE code = '411300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411327', '社旗县', 1 FROM fa_cities WHERE code = '411300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411328', '唐河县', 1 FROM fa_cities WHERE code = '411300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411329', '新野县', 1 FROM fa_cities WHERE code = '411300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411330', '桐柏县', 1 FROM fa_cities WHERE code = '411300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411371', '南阳高新技术产业开发区', 1 FROM fa_cities WHERE code = '411300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411372', '南阳市城乡一体化示范区', 1 FROM fa_cities WHERE code = '411300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411381', '邓州市', 1 FROM fa_cities WHERE code = '411300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411402', '梁园区', 1 FROM fa_cities WHERE code = '411400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411403', '睢阳区', 1 FROM fa_cities WHERE code = '411400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411421', '民权县', 1 FROM fa_cities WHERE code = '411400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411422', '睢县', 1 FROM fa_cities WHERE code = '411400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411423', '宁陵县', 1 FROM fa_cities WHERE code = '411400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411424', '柘城县', 1 FROM fa_cities WHERE code = '411400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411425', '虞城县', 1 FROM fa_cities WHERE code = '411400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411426', '夏邑县', 1 FROM fa_cities WHERE code = '411400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411471', '豫东综合物流产业聚集区', 1 FROM fa_cities WHERE code = '411400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411472', '河南商丘经济开发区', 1 FROM fa_cities WHERE code = '411400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411481', '永城市', 1 FROM fa_cities WHERE code = '411400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411502', '浉河区', 1 FROM fa_cities WHERE code = '411500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411503', '平桥区', 1 FROM fa_cities WHERE code = '411500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411521', '罗山县', 1 FROM fa_cities WHERE code = '411500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411522', '光山县', 1 FROM fa_cities WHERE code = '411500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411523', '新县', 1 FROM fa_cities WHERE code = '411500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411524', '商城县', 1 FROM fa_cities WHERE code = '411500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411525', '固始县', 1 FROM fa_cities WHERE code = '411500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411526', '潢川县', 1 FROM fa_cities WHERE code = '411500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411527', '淮滨县', 1 FROM fa_cities WHERE code = '411500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411528', '息县', 1 FROM fa_cities WHERE code = '411500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411571', '信阳高新技术产业开发区', 1 FROM fa_cities WHERE code = '411500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411602', '川汇区', 1 FROM fa_cities WHERE code = '411600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411603', '淮阳区', 1 FROM fa_cities WHERE code = '411600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411621', '扶沟县', 1 FROM fa_cities WHERE code = '411600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411622', '西华县', 1 FROM fa_cities WHERE code = '411600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411623', '商水县', 1 FROM fa_cities WHERE code = '411600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411624', '沈丘县', 1 FROM fa_cities WHERE code = '411600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411625', '郸城县', 1 FROM fa_cities WHERE code = '411600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411627', '太康县', 1 FROM fa_cities WHERE code = '411600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411628', '鹿邑县', 1 FROM fa_cities WHERE code = '411600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411671', '周口临港开发区', 1 FROM fa_cities WHERE code = '411600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411681', '项城市', 1 FROM fa_cities WHERE code = '411600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411702', '驿城区', 1 FROM fa_cities WHERE code = '411700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411721', '西平县', 1 FROM fa_cities WHERE code = '411700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411722', '上蔡县', 1 FROM fa_cities WHERE code = '411700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411723', '平舆县', 1 FROM fa_cities WHERE code = '411700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411724', '正阳县', 1 FROM fa_cities WHERE code = '411700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411725', '确山县', 1 FROM fa_cities WHERE code = '411700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411726', '泌阳县', 1 FROM fa_cities WHERE code = '411700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411727', '汝南县', 1 FROM fa_cities WHERE code = '411700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411728', '遂平县', 1 FROM fa_cities WHERE code = '411700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411729', '新蔡县', 1 FROM fa_cities WHERE code = '411700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '411771', '河南驻马店经济开发区', 1 FROM fa_cities WHERE code = '411700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420102', '江岸区', 1 FROM fa_cities WHERE code = '420100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420103', '江汉区', 1 FROM fa_cities WHERE code = '420100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420104', '硚口区', 1 FROM fa_cities WHERE code = '420100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420105', '汉阳区', 1 FROM fa_cities WHERE code = '420100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420106', '武昌区', 1 FROM fa_cities WHERE code = '420100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420107', '青山区', 1 FROM fa_cities WHERE code = '420100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420111', '洪山区', 1 FROM fa_cities WHERE code = '420100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420112', '东西湖区', 1 FROM fa_cities WHERE code = '420100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420113', '汉南区', 1 FROM fa_cities WHERE code = '420100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420114', '蔡甸区', 1 FROM fa_cities WHERE code = '420100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420115', '江夏区', 1 FROM fa_cities WHERE code = '420100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420116', '黄陂区', 1 FROM fa_cities WHERE code = '420100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420117', '新洲区', 1 FROM fa_cities WHERE code = '420100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420202', '黄石港区', 1 FROM fa_cities WHERE code = '420200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420203', '西塞山区', 1 FROM fa_cities WHERE code = '420200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420204', '下陆区', 1 FROM fa_cities WHERE code = '420200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420205', '铁山区', 1 FROM fa_cities WHERE code = '420200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420222', '阳新县', 1 FROM fa_cities WHERE code = '420200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420281', '大冶市', 1 FROM fa_cities WHERE code = '420200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420302', '茅箭区', 1 FROM fa_cities WHERE code = '420300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420303', '张湾区', 1 FROM fa_cities WHERE code = '420300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420304', '郧阳区', 1 FROM fa_cities WHERE code = '420300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420322', '郧西县', 1 FROM fa_cities WHERE code = '420300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420323', '竹山县', 1 FROM fa_cities WHERE code = '420300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420324', '竹溪县', 1 FROM fa_cities WHERE code = '420300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420325', '房县', 1 FROM fa_cities WHERE code = '420300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420381', '丹江口市', 1 FROM fa_cities WHERE code = '420300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420502', '西陵区', 1 FROM fa_cities WHERE code = '420500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420503', '伍家岗区', 1 FROM fa_cities WHERE code = '420500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420504', '点军区', 1 FROM fa_cities WHERE code = '420500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420505', '猇亭区', 1 FROM fa_cities WHERE code = '420500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420506', '夷陵区', 1 FROM fa_cities WHERE code = '420500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420525', '远安县', 1 FROM fa_cities WHERE code = '420500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420526', '兴山县', 1 FROM fa_cities WHERE code = '420500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420527', '秭归县', 1 FROM fa_cities WHERE code = '420500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420528', '长阳土家族自治县', 1 FROM fa_cities WHERE code = '420500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420529', '五峰土家族自治县', 1 FROM fa_cities WHERE code = '420500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420581', '宜都市', 1 FROM fa_cities WHERE code = '420500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420582', '当阳市', 1 FROM fa_cities WHERE code = '420500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420583', '枝江市', 1 FROM fa_cities WHERE code = '420500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420602', '襄城区', 1 FROM fa_cities WHERE code = '420600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420606', '樊城区', 1 FROM fa_cities WHERE code = '420600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420607', '襄州区', 1 FROM fa_cities WHERE code = '420600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420624', '南漳县', 1 FROM fa_cities WHERE code = '420600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420625', '谷城县', 1 FROM fa_cities WHERE code = '420600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420626', '保康县', 1 FROM fa_cities WHERE code = '420600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420682', '老河口市', 1 FROM fa_cities WHERE code = '420600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420683', '枣阳市', 1 FROM fa_cities WHERE code = '420600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420684', '宜城市', 1 FROM fa_cities WHERE code = '420600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420702', '梁子湖区', 1 FROM fa_cities WHERE code = '420700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420703', '华容区', 1 FROM fa_cities WHERE code = '420700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420704', '鄂城区', 1 FROM fa_cities WHERE code = '420700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420802', '东宝区', 1 FROM fa_cities WHERE code = '420800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420804', '掇刀区', 1 FROM fa_cities WHERE code = '420800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420822', '沙洋县', 1 FROM fa_cities WHERE code = '420800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420881', '钟祥市', 1 FROM fa_cities WHERE code = '420800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420882', '京山市', 1 FROM fa_cities WHERE code = '420800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420902', '孝南区', 1 FROM fa_cities WHERE code = '420900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420921', '孝昌县', 1 FROM fa_cities WHERE code = '420900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420922', '大悟县', 1 FROM fa_cities WHERE code = '420900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420923', '云梦县', 1 FROM fa_cities WHERE code = '420900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420981', '应城市', 1 FROM fa_cities WHERE code = '420900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420982', '安陆市', 1 FROM fa_cities WHERE code = '420900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '420984', '汉川市', 1 FROM fa_cities WHERE code = '420900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '421002', '沙市区', 1 FROM fa_cities WHERE code = '421000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '421003', '荆州区', 1 FROM fa_cities WHERE code = '421000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '421022', '公安县', 1 FROM fa_cities WHERE code = '421000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '421024', '江陵县', 1 FROM fa_cities WHERE code = '421000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '421071', '荆州经济技术开发区', 1 FROM fa_cities WHERE code = '421000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '421081', '石首市', 1 FROM fa_cities WHERE code = '421000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '421083', '洪湖市', 1 FROM fa_cities WHERE code = '421000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '421087', '松滋市', 1 FROM fa_cities WHERE code = '421000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '421088', '监利市', 1 FROM fa_cities WHERE code = '421000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '421102', '黄州区', 1 FROM fa_cities WHERE code = '421100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '421121', '团风县', 1 FROM fa_cities WHERE code = '421100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '421122', '红安县', 1 FROM fa_cities WHERE code = '421100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '421123', '罗田县', 1 FROM fa_cities WHERE code = '421100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '421124', '英山县', 1 FROM fa_cities WHERE code = '421100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '421125', '浠水县', 1 FROM fa_cities WHERE code = '421100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '421126', '蕲春县', 1 FROM fa_cities WHERE code = '421100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '421127', '黄梅县', 1 FROM fa_cities WHERE code = '421100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '421171', '龙感湖管理区', 1 FROM fa_cities WHERE code = '421100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '421181', '麻城市', 1 FROM fa_cities WHERE code = '421100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '421182', '武穴市', 1 FROM fa_cities WHERE code = '421100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '421202', '咸安区', 1 FROM fa_cities WHERE code = '421200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '421221', '嘉鱼县', 1 FROM fa_cities WHERE code = '421200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '421222', '通城县', 1 FROM fa_cities WHERE code = '421200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '421223', '崇阳县', 1 FROM fa_cities WHERE code = '421200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '421224', '通山县', 1 FROM fa_cities WHERE code = '421200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '421281', '赤壁市', 1 FROM fa_cities WHERE code = '421200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '421303', '曾都区', 1 FROM fa_cities WHERE code = '421300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '421321', '随县', 1 FROM fa_cities WHERE code = '421300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '421381', '广水市', 1 FROM fa_cities WHERE code = '421300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '422801', '恩施市', 1 FROM fa_cities WHERE code = '422800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '422802', '利川市', 1 FROM fa_cities WHERE code = '422800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '422822', '建始县', 1 FROM fa_cities WHERE code = '422800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '422823', '巴东县', 1 FROM fa_cities WHERE code = '422800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '422825', '宣恩县', 1 FROM fa_cities WHERE code = '422800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '422826', '咸丰县', 1 FROM fa_cities WHERE code = '422800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '422827', '来凤县', 1 FROM fa_cities WHERE code = '422800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '422828', '鹤峰县', 1 FROM fa_cities WHERE code = '422800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430102', '芙蓉区', 1 FROM fa_cities WHERE code = '430100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430103', '天心区', 1 FROM fa_cities WHERE code = '430100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430104', '岳麓区', 1 FROM fa_cities WHERE code = '430100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430105', '开福区', 1 FROM fa_cities WHERE code = '430100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430111', '雨花区', 1 FROM fa_cities WHERE code = '430100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430112', '望城区', 1 FROM fa_cities WHERE code = '430100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430121', '长沙县', 1 FROM fa_cities WHERE code = '430100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430181', '浏阳市', 1 FROM fa_cities WHERE code = '430100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430182', '宁乡市', 1 FROM fa_cities WHERE code = '430100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430202', '荷塘区', 1 FROM fa_cities WHERE code = '430200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430203', '芦淞区', 1 FROM fa_cities WHERE code = '430200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430204', '石峰区', 1 FROM fa_cities WHERE code = '430200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430211', '天元区', 1 FROM fa_cities WHERE code = '430200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430212', '渌口区', 1 FROM fa_cities WHERE code = '430200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430223', '攸县', 1 FROM fa_cities WHERE code = '430200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430224', '茶陵县', 1 FROM fa_cities WHERE code = '430200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430225', '炎陵县', 1 FROM fa_cities WHERE code = '430200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430281', '醴陵市', 1 FROM fa_cities WHERE code = '430200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430302', '雨湖区', 1 FROM fa_cities WHERE code = '430300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430304', '岳塘区', 1 FROM fa_cities WHERE code = '430300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430321', '湘潭县', 1 FROM fa_cities WHERE code = '430300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430371', '湖南湘潭高新技术产业园区', 1 FROM fa_cities WHERE code = '430300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430372', '湘潭昭山示范区', 1 FROM fa_cities WHERE code = '430300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430373', '湘潭九华示范区', 1 FROM fa_cities WHERE code = '430300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430381', '湘乡市', 1 FROM fa_cities WHERE code = '430300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430382', '韶山市', 1 FROM fa_cities WHERE code = '430300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430405', '珠晖区', 1 FROM fa_cities WHERE code = '430400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430406', '雁峰区', 1 FROM fa_cities WHERE code = '430400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430407', '石鼓区', 1 FROM fa_cities WHERE code = '430400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430408', '蒸湘区', 1 FROM fa_cities WHERE code = '430400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430412', '南岳区', 1 FROM fa_cities WHERE code = '430400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430421', '衡阳县', 1 FROM fa_cities WHERE code = '430400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430422', '衡南县', 1 FROM fa_cities WHERE code = '430400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430423', '衡山县', 1 FROM fa_cities WHERE code = '430400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430424', '衡东县', 1 FROM fa_cities WHERE code = '430400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430426', '祁东县', 1 FROM fa_cities WHERE code = '430400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430473', '湖南衡阳松木经济开发区', 1 FROM fa_cities WHERE code = '430400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430476', '湖南衡阳高新技术产业园区', 1 FROM fa_cities WHERE code = '430400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430481', '耒阳市', 1 FROM fa_cities WHERE code = '430400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430482', '常宁市', 1 FROM fa_cities WHERE code = '430400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430502', '双清区', 1 FROM fa_cities WHERE code = '430500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430503', '大祥区', 1 FROM fa_cities WHERE code = '430500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430511', '北塔区', 1 FROM fa_cities WHERE code = '430500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430522', '新邵县', 1 FROM fa_cities WHERE code = '430500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430523', '邵阳县', 1 FROM fa_cities WHERE code = '430500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430524', '隆回县', 1 FROM fa_cities WHERE code = '430500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430525', '洞口县', 1 FROM fa_cities WHERE code = '430500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430527', '绥宁县', 1 FROM fa_cities WHERE code = '430500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430528', '新宁县', 1 FROM fa_cities WHERE code = '430500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430529', '城步苗族自治县', 1 FROM fa_cities WHERE code = '430500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430581', '武冈市', 1 FROM fa_cities WHERE code = '430500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430582', '邵东市', 1 FROM fa_cities WHERE code = '430500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430602', '岳阳楼区', 1 FROM fa_cities WHERE code = '430600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430603', '云溪区', 1 FROM fa_cities WHERE code = '430600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430611', '君山区', 1 FROM fa_cities WHERE code = '430600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430621', '岳阳县', 1 FROM fa_cities WHERE code = '430600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430623', '华容县', 1 FROM fa_cities WHERE code = '430600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430624', '湘阴县', 1 FROM fa_cities WHERE code = '430600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430626', '平江县', 1 FROM fa_cities WHERE code = '430600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430671', '岳阳市屈原管理区', 1 FROM fa_cities WHERE code = '430600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430681', '汨罗市', 1 FROM fa_cities WHERE code = '430600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430682', '临湘市', 1 FROM fa_cities WHERE code = '430600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430702', '武陵区', 1 FROM fa_cities WHERE code = '430700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430703', '鼎城区', 1 FROM fa_cities WHERE code = '430700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430721', '安乡县', 1 FROM fa_cities WHERE code = '430700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430722', '汉寿县', 1 FROM fa_cities WHERE code = '430700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430723', '澧县', 1 FROM fa_cities WHERE code = '430700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430724', '临澧县', 1 FROM fa_cities WHERE code = '430700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430725', '桃源县', 1 FROM fa_cities WHERE code = '430700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430726', '石门县', 1 FROM fa_cities WHERE code = '430700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430771', '常德市西洞庭管理区', 1 FROM fa_cities WHERE code = '430700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430781', '津市市', 1 FROM fa_cities WHERE code = '430700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430802', '永定区', 1 FROM fa_cities WHERE code = '430800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430811', '武陵源区', 1 FROM fa_cities WHERE code = '430800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430821', '慈利县', 1 FROM fa_cities WHERE code = '430800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430822', '桑植县', 1 FROM fa_cities WHERE code = '430800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430902', '资阳区', 1 FROM fa_cities WHERE code = '430900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430903', '赫山区', 1 FROM fa_cities WHERE code = '430900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430921', '南县', 1 FROM fa_cities WHERE code = '430900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430922', '桃江县', 1 FROM fa_cities WHERE code = '430900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430923', '安化县', 1 FROM fa_cities WHERE code = '430900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430971', '益阳市大通湖管理区', 1 FROM fa_cities WHERE code = '430900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430972', '湖南益阳高新技术产业园区', 1 FROM fa_cities WHERE code = '430900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '430981', '沅江市', 1 FROM fa_cities WHERE code = '430900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431002', '北湖区', 1 FROM fa_cities WHERE code = '431000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431003', '苏仙区', 1 FROM fa_cities WHERE code = '431000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431021', '桂阳县', 1 FROM fa_cities WHERE code = '431000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431022', '宜章县', 1 FROM fa_cities WHERE code = '431000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431023', '永兴县', 1 FROM fa_cities WHERE code = '431000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431024', '嘉禾县', 1 FROM fa_cities WHERE code = '431000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431025', '临武县', 1 FROM fa_cities WHERE code = '431000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431026', '汝城县', 1 FROM fa_cities WHERE code = '431000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431027', '桂东县', 1 FROM fa_cities WHERE code = '431000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431028', '安仁县', 1 FROM fa_cities WHERE code = '431000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431081', '资兴市', 1 FROM fa_cities WHERE code = '431000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431102', '零陵区', 1 FROM fa_cities WHERE code = '431100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431103', '冷水滩区', 1 FROM fa_cities WHERE code = '431100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431122', '东安县', 1 FROM fa_cities WHERE code = '431100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431123', '双牌县', 1 FROM fa_cities WHERE code = '431100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431124', '道县', 1 FROM fa_cities WHERE code = '431100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431125', '江永县', 1 FROM fa_cities WHERE code = '431100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431126', '宁远县', 1 FROM fa_cities WHERE code = '431100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431127', '蓝山县', 1 FROM fa_cities WHERE code = '431100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431128', '新田县', 1 FROM fa_cities WHERE code = '431100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431129', '江华瑶族自治县', 1 FROM fa_cities WHERE code = '431100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431171', '永州经济技术开发区', 1 FROM fa_cities WHERE code = '431100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431173', '永州市回龙圩管理区', 1 FROM fa_cities WHERE code = '431100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431181', '祁阳市', 1 FROM fa_cities WHERE code = '431100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431202', '鹤城区', 1 FROM fa_cities WHERE code = '431200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431221', '中方县', 1 FROM fa_cities WHERE code = '431200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431222', '沅陵县', 1 FROM fa_cities WHERE code = '431200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431223', '辰溪县', 1 FROM fa_cities WHERE code = '431200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431224', '溆浦县', 1 FROM fa_cities WHERE code = '431200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431225', '会同县', 1 FROM fa_cities WHERE code = '431200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431226', '麻阳苗族自治县', 1 FROM fa_cities WHERE code = '431200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431227', '新晃侗族自治县', 1 FROM fa_cities WHERE code = '431200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431228', '芷江侗族自治县', 1 FROM fa_cities WHERE code = '431200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431229', '靖州苗族侗族自治县', 1 FROM fa_cities WHERE code = '431200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431230', '通道侗族自治县', 1 FROM fa_cities WHERE code = '431200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431271', '怀化市洪江管理区', 1 FROM fa_cities WHERE code = '431200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431281', '洪江市', 1 FROM fa_cities WHERE code = '431200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431302', '娄星区', 1 FROM fa_cities WHERE code = '431300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431321', '双峰县', 1 FROM fa_cities WHERE code = '431300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431322', '新化县', 1 FROM fa_cities WHERE code = '431300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431381', '冷水江市', 1 FROM fa_cities WHERE code = '431300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '431382', '涟源市', 1 FROM fa_cities WHERE code = '431300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '433101', '吉首市', 1 FROM fa_cities WHERE code = '433100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '433122', '泸溪县', 1 FROM fa_cities WHERE code = '433100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '433123', '凤凰县', 1 FROM fa_cities WHERE code = '433100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '433124', '花垣县', 1 FROM fa_cities WHERE code = '433100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '433125', '保靖县', 1 FROM fa_cities WHERE code = '433100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '433126', '古丈县', 1 FROM fa_cities WHERE code = '433100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '433127', '永顺县', 1 FROM fa_cities WHERE code = '433100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '433130', '龙山县', 1 FROM fa_cities WHERE code = '433100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440103', '荔湾区', 1 FROM fa_cities WHERE code = '440100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440104', '越秀区', 1 FROM fa_cities WHERE code = '440100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440105', '海珠区', 1 FROM fa_cities WHERE code = '440100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440106', '天河区', 1 FROM fa_cities WHERE code = '440100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440111', '白云区', 1 FROM fa_cities WHERE code = '440100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440112', '黄埔区', 1 FROM fa_cities WHERE code = '440100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440113', '番禺区', 1 FROM fa_cities WHERE code = '440100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440114', '花都区', 1 FROM fa_cities WHERE code = '440100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440115', '南沙区', 1 FROM fa_cities WHERE code = '440100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440117', '从化区', 1 FROM fa_cities WHERE code = '440100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440118', '增城区', 1 FROM fa_cities WHERE code = '440100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440203', '武江区', 1 FROM fa_cities WHERE code = '440200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440204', '浈江区', 1 FROM fa_cities WHERE code = '440200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440205', '曲江区', 1 FROM fa_cities WHERE code = '440200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440222', '始兴县', 1 FROM fa_cities WHERE code = '440200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440224', '仁化县', 1 FROM fa_cities WHERE code = '440200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440229', '翁源县', 1 FROM fa_cities WHERE code = '440200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440232', '乳源瑶族自治县', 1 FROM fa_cities WHERE code = '440200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440233', '新丰县', 1 FROM fa_cities WHERE code = '440200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440281', '乐昌市', 1 FROM fa_cities WHERE code = '440200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440282', '南雄市', 1 FROM fa_cities WHERE code = '440200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440303', '罗湖区', 1 FROM fa_cities WHERE code = '440300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440304', '福田区', 1 FROM fa_cities WHERE code = '440300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440305', '南山区', 1 FROM fa_cities WHERE code = '440300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440306', '宝安区', 1 FROM fa_cities WHERE code = '440300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440307', '龙岗区', 1 FROM fa_cities WHERE code = '440300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440308', '盐田区', 1 FROM fa_cities WHERE code = '440300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440309', '龙华区', 1 FROM fa_cities WHERE code = '440300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440310', '坪山区', 1 FROM fa_cities WHERE code = '440300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440311', '光明区', 1 FROM fa_cities WHERE code = '440300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440402', '香洲区', 1 FROM fa_cities WHERE code = '440400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440403', '斗门区', 1 FROM fa_cities WHERE code = '440400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440404', '金湾区', 1 FROM fa_cities WHERE code = '440400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440507', '龙湖区', 1 FROM fa_cities WHERE code = '440500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440511', '金平区', 1 FROM fa_cities WHERE code = '440500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440512', '濠江区', 1 FROM fa_cities WHERE code = '440500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440513', '潮阳区', 1 FROM fa_cities WHERE code = '440500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440514', '潮南区', 1 FROM fa_cities WHERE code = '440500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440515', '澄海区', 1 FROM fa_cities WHERE code = '440500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440523', '南澳县', 1 FROM fa_cities WHERE code = '440500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440604', '禅城区', 1 FROM fa_cities WHERE code = '440600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440605', '南海区', 1 FROM fa_cities WHERE code = '440600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440606', '顺德区', 1 FROM fa_cities WHERE code = '440600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440607', '三水区', 1 FROM fa_cities WHERE code = '440600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440608', '高明区', 1 FROM fa_cities WHERE code = '440600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440703', '蓬江区', 1 FROM fa_cities WHERE code = '440700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440704', '江海区', 1 FROM fa_cities WHERE code = '440700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440705', '新会区', 1 FROM fa_cities WHERE code = '440700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440781', '台山市', 1 FROM fa_cities WHERE code = '440700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440783', '开平市', 1 FROM fa_cities WHERE code = '440700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440784', '鹤山市', 1 FROM fa_cities WHERE code = '440700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440785', '恩平市', 1 FROM fa_cities WHERE code = '440700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440802', '赤坎区', 1 FROM fa_cities WHERE code = '440800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440803', '霞山区', 1 FROM fa_cities WHERE code = '440800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440804', '坡头区', 1 FROM fa_cities WHERE code = '440800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440811', '麻章区', 1 FROM fa_cities WHERE code = '440800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440823', '遂溪县', 1 FROM fa_cities WHERE code = '440800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440825', '徐闻县', 1 FROM fa_cities WHERE code = '440800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440881', '廉江市', 1 FROM fa_cities WHERE code = '440800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440882', '雷州市', 1 FROM fa_cities WHERE code = '440800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440883', '吴川市', 1 FROM fa_cities WHERE code = '440800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440902', '茂南区', 1 FROM fa_cities WHERE code = '440900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440904', '电白区', 1 FROM fa_cities WHERE code = '440900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440981', '高州市', 1 FROM fa_cities WHERE code = '440900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440982', '化州市', 1 FROM fa_cities WHERE code = '440900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '440983', '信宜市', 1 FROM fa_cities WHERE code = '440900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441202', '端州区', 1 FROM fa_cities WHERE code = '441200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441203', '鼎湖区', 1 FROM fa_cities WHERE code = '441200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441204', '高要区', 1 FROM fa_cities WHERE code = '441200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441223', '广宁县', 1 FROM fa_cities WHERE code = '441200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441224', '怀集县', 1 FROM fa_cities WHERE code = '441200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441225', '封开县', 1 FROM fa_cities WHERE code = '441200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441226', '德庆县', 1 FROM fa_cities WHERE code = '441200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441284', '四会市', 1 FROM fa_cities WHERE code = '441200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441302', '惠城区', 1 FROM fa_cities WHERE code = '441300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441303', '惠阳区', 1 FROM fa_cities WHERE code = '441300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441322', '博罗县', 1 FROM fa_cities WHERE code = '441300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441323', '惠东县', 1 FROM fa_cities WHERE code = '441300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441324', '龙门县', 1 FROM fa_cities WHERE code = '441300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441402', '梅江区', 1 FROM fa_cities WHERE code = '441400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441403', '梅县区', 1 FROM fa_cities WHERE code = '441400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441422', '大埔县', 1 FROM fa_cities WHERE code = '441400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441423', '丰顺县', 1 FROM fa_cities WHERE code = '441400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441424', '五华县', 1 FROM fa_cities WHERE code = '441400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441426', '平远县', 1 FROM fa_cities WHERE code = '441400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441427', '蕉岭县', 1 FROM fa_cities WHERE code = '441400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441481', '兴宁市', 1 FROM fa_cities WHERE code = '441400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441502', '城区', 1 FROM fa_cities WHERE code = '441500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441521', '海丰县', 1 FROM fa_cities WHERE code = '441500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441523', '陆河县', 1 FROM fa_cities WHERE code = '441500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441581', '陆丰市', 1 FROM fa_cities WHERE code = '441500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441602', '源城区', 1 FROM fa_cities WHERE code = '441600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441621', '紫金县', 1 FROM fa_cities WHERE code = '441600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441622', '龙川县', 1 FROM fa_cities WHERE code = '441600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441623', '连平县', 1 FROM fa_cities WHERE code = '441600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441624', '和平县', 1 FROM fa_cities WHERE code = '441600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441625', '东源县', 1 FROM fa_cities WHERE code = '441600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441702', '江城区', 1 FROM fa_cities WHERE code = '441700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441704', '阳东区', 1 FROM fa_cities WHERE code = '441700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441721', '阳西县', 1 FROM fa_cities WHERE code = '441700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441781', '阳春市', 1 FROM fa_cities WHERE code = '441700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441802', '清城区', 1 FROM fa_cities WHERE code = '441800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441803', '清新区', 1 FROM fa_cities WHERE code = '441800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441821', '佛冈县', 1 FROM fa_cities WHERE code = '441800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441823', '阳山县', 1 FROM fa_cities WHERE code = '441800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441825', '连山壮族瑶族自治县', 1 FROM fa_cities WHERE code = '441800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441826', '连南瑶族自治县', 1 FROM fa_cities WHERE code = '441800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441881', '英德市', 1 FROM fa_cities WHERE code = '441800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '441882', '连州市', 1 FROM fa_cities WHERE code = '441800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '445102', '湘桥区', 1 FROM fa_cities WHERE code = '445100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '445103', '潮安区', 1 FROM fa_cities WHERE code = '445100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '445122', '饶平县', 1 FROM fa_cities WHERE code = '445100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '445202', '榕城区', 1 FROM fa_cities WHERE code = '445200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '445203', '揭东区', 1 FROM fa_cities WHERE code = '445200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '445222', '揭西县', 1 FROM fa_cities WHERE code = '445200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '445224', '惠来县', 1 FROM fa_cities WHERE code = '445200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '445281', '普宁市', 1 FROM fa_cities WHERE code = '445200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '445302', '云城区', 1 FROM fa_cities WHERE code = '445300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '445303', '云安区', 1 FROM fa_cities WHERE code = '445300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '445321', '新兴县', 1 FROM fa_cities WHERE code = '445300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '445322', '郁南县', 1 FROM fa_cities WHERE code = '445300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '445381', '罗定市', 1 FROM fa_cities WHERE code = '445300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450102', '兴宁区', 1 FROM fa_cities WHERE code = '450100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450103', '青秀区', 1 FROM fa_cities WHERE code = '450100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450105', '江南区', 1 FROM fa_cities WHERE code = '450100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450107', '西乡塘区', 1 FROM fa_cities WHERE code = '450100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450108', '良庆区', 1 FROM fa_cities WHERE code = '450100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450109', '邕宁区', 1 FROM fa_cities WHERE code = '450100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450110', '武鸣区', 1 FROM fa_cities WHERE code = '450100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450123', '隆安县', 1 FROM fa_cities WHERE code = '450100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450124', '马山县', 1 FROM fa_cities WHERE code = '450100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450125', '上林县', 1 FROM fa_cities WHERE code = '450100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450126', '宾阳县', 1 FROM fa_cities WHERE code = '450100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450181', '横州市', 1 FROM fa_cities WHERE code = '450100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450202', '城中区', 1 FROM fa_cities WHERE code = '450200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450203', '鱼峰区', 1 FROM fa_cities WHERE code = '450200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450204', '柳南区', 1 FROM fa_cities WHERE code = '450200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450205', '柳北区', 1 FROM fa_cities WHERE code = '450200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450206', '柳江区', 1 FROM fa_cities WHERE code = '450200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450222', '柳城县', 1 FROM fa_cities WHERE code = '450200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450223', '鹿寨县', 1 FROM fa_cities WHERE code = '450200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450224', '融安县', 1 FROM fa_cities WHERE code = '450200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450225', '融水苗族自治县', 1 FROM fa_cities WHERE code = '450200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450226', '三江侗族自治县', 1 FROM fa_cities WHERE code = '450200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450302', '秀峰区', 1 FROM fa_cities WHERE code = '450300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450303', '叠彩区', 1 FROM fa_cities WHERE code = '450300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450304', '象山区', 1 FROM fa_cities WHERE code = '450300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450305', '七星区', 1 FROM fa_cities WHERE code = '450300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450311', '雁山区', 1 FROM fa_cities WHERE code = '450300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450312', '临桂区', 1 FROM fa_cities WHERE code = '450300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450321', '阳朔县', 1 FROM fa_cities WHERE code = '450300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450323', '灵川县', 1 FROM fa_cities WHERE code = '450300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450324', '全州县', 1 FROM fa_cities WHERE code = '450300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450325', '兴安县', 1 FROM fa_cities WHERE code = '450300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450326', '永福县', 1 FROM fa_cities WHERE code = '450300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450327', '灌阳县', 1 FROM fa_cities WHERE code = '450300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450328', '龙胜各族自治县', 1 FROM fa_cities WHERE code = '450300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450329', '资源县', 1 FROM fa_cities WHERE code = '450300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450330', '平乐县', 1 FROM fa_cities WHERE code = '450300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450332', '恭城瑶族自治县', 1 FROM fa_cities WHERE code = '450300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450381', '荔浦市', 1 FROM fa_cities WHERE code = '450300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450403', '万秀区', 1 FROM fa_cities WHERE code = '450400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450405', '长洲区', 1 FROM fa_cities WHERE code = '450400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450406', '龙圩区', 1 FROM fa_cities WHERE code = '450400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450421', '苍梧县', 1 FROM fa_cities WHERE code = '450400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450422', '藤县', 1 FROM fa_cities WHERE code = '450400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450423', '蒙山县', 1 FROM fa_cities WHERE code = '450400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450481', '岑溪市', 1 FROM fa_cities WHERE code = '450400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450502', '海城区', 1 FROM fa_cities WHERE code = '450500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450503', '银海区', 1 FROM fa_cities WHERE code = '450500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450512', '铁山港区', 1 FROM fa_cities WHERE code = '450500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450521', '合浦县', 1 FROM fa_cities WHERE code = '450500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450602', '港口区', 1 FROM fa_cities WHERE code = '450600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450603', '防城区', 1 FROM fa_cities WHERE code = '450600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450621', '上思县', 1 FROM fa_cities WHERE code = '450600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450681', '东兴市', 1 FROM fa_cities WHERE code = '450600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450702', '钦南区', 1 FROM fa_cities WHERE code = '450700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450703', '钦北区', 1 FROM fa_cities WHERE code = '450700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450721', '灵山县', 1 FROM fa_cities WHERE code = '450700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450722', '浦北县', 1 FROM fa_cities WHERE code = '450700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450802', '港北区', 1 FROM fa_cities WHERE code = '450800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450803', '港南区', 1 FROM fa_cities WHERE code = '450800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450804', '覃塘区', 1 FROM fa_cities WHERE code = '450800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450821', '平南县', 1 FROM fa_cities WHERE code = '450800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450881', '桂平市', 1 FROM fa_cities WHERE code = '450800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450902', '玉州区', 1 FROM fa_cities WHERE code = '450900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450903', '福绵区', 1 FROM fa_cities WHERE code = '450900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450921', '容县', 1 FROM fa_cities WHERE code = '450900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450922', '陆川县', 1 FROM fa_cities WHERE code = '450900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450923', '博白县', 1 FROM fa_cities WHERE code = '450900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450924', '兴业县', 1 FROM fa_cities WHERE code = '450900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '450981', '北流市', 1 FROM fa_cities WHERE code = '450900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451002', '右江区', 1 FROM fa_cities WHERE code = '451000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451003', '田阳区', 1 FROM fa_cities WHERE code = '451000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451022', '田东县', 1 FROM fa_cities WHERE code = '451000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451024', '德保县', 1 FROM fa_cities WHERE code = '451000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451026', '那坡县', 1 FROM fa_cities WHERE code = '451000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451027', '凌云县', 1 FROM fa_cities WHERE code = '451000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451028', '乐业县', 1 FROM fa_cities WHERE code = '451000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451029', '田林县', 1 FROM fa_cities WHERE code = '451000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451030', '西林县', 1 FROM fa_cities WHERE code = '451000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451031', '隆林各族自治县', 1 FROM fa_cities WHERE code = '451000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451081', '靖西市', 1 FROM fa_cities WHERE code = '451000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451082', '平果市', 1 FROM fa_cities WHERE code = '451000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451102', '八步区', 1 FROM fa_cities WHERE code = '451100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451103', '平桂区', 1 FROM fa_cities WHERE code = '451100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451121', '昭平县', 1 FROM fa_cities WHERE code = '451100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451122', '钟山县', 1 FROM fa_cities WHERE code = '451100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451123', '富川瑶族自治县', 1 FROM fa_cities WHERE code = '451100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451202', '金城江区', 1 FROM fa_cities WHERE code = '451200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451203', '宜州区', 1 FROM fa_cities WHERE code = '451200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451221', '南丹县', 1 FROM fa_cities WHERE code = '451200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451222', '天峨县', 1 FROM fa_cities WHERE code = '451200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451223', '凤山县', 1 FROM fa_cities WHERE code = '451200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451224', '东兰县', 1 FROM fa_cities WHERE code = '451200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451225', '罗城仫佬族自治县', 1 FROM fa_cities WHERE code = '451200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451226', '环江毛南族自治县', 1 FROM fa_cities WHERE code = '451200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451227', '巴马瑶族自治县', 1 FROM fa_cities WHERE code = '451200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451228', '都安瑶族自治县', 1 FROM fa_cities WHERE code = '451200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451229', '大化瑶族自治县', 1 FROM fa_cities WHERE code = '451200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451302', '兴宾区', 1 FROM fa_cities WHERE code = '451300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451321', '忻城县', 1 FROM fa_cities WHERE code = '451300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451322', '象州县', 1 FROM fa_cities WHERE code = '451300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451323', '武宣县', 1 FROM fa_cities WHERE code = '451300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451324', '金秀瑶族自治县', 1 FROM fa_cities WHERE code = '451300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451381', '合山市', 1 FROM fa_cities WHERE code = '451300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451402', '江州区', 1 FROM fa_cities WHERE code = '451400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451421', '扶绥县', 1 FROM fa_cities WHERE code = '451400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451422', '宁明县', 1 FROM fa_cities WHERE code = '451400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451423', '龙州县', 1 FROM fa_cities WHERE code = '451400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451424', '大新县', 1 FROM fa_cities WHERE code = '451400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451425', '天等县', 1 FROM fa_cities WHERE code = '451400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '451481', '凭祥市', 1 FROM fa_cities WHERE code = '451400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '460105', '秀英区', 1 FROM fa_cities WHERE code = '460100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '460106', '龙华区', 1 FROM fa_cities WHERE code = '460100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '460107', '琼山区', 1 FROM fa_cities WHERE code = '460100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '460108', '美兰区', 1 FROM fa_cities WHERE code = '460100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '460202', '海棠区', 1 FROM fa_cities WHERE code = '460200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '460203', '吉阳区', 1 FROM fa_cities WHERE code = '460200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '460204', '天涯区', 1 FROM fa_cities WHERE code = '460200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '460205', '崖州区', 1 FROM fa_cities WHERE code = '460200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '460321', '西沙群岛', 1 FROM fa_cities WHERE code = '460300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '460322', '南沙群岛', 1 FROM fa_cities WHERE code = '460300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '460323', '中沙群岛的岛礁及其海域', 1 FROM fa_cities WHERE code = '460300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500101', '万州区', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500102', '涪陵区', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500103', '渝中区', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500104', '大渡口区', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500105', '江北区', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500106', '沙坪坝区', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500107', '九龙坡区', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500108', '南岸区', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500109', '北碚区', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500110', '綦江区', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500111', '大足区', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500112', '渝北区', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500113', '巴南区', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500114', '黔江区', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500115', '长寿区', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500116', '江津区', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500117', '合川区', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500118', '永川区', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500119', '南川区', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500120', '璧山区', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500151', '铜梁区', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500152', '潼南区', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500153', '荣昌区', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500154', '开州区', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500155', '梁平区', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500156', '武隆区', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500229', '城口县', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500230', '丰都县', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500231', '垫江县', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500233', '忠县', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500235', '云阳县', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500236', '奉节县', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500237', '巫山县', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500238', '巫溪县', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500240', '石柱土家族自治县', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500241', '秀山土家族苗族自治县', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500242', '酉阳土家族苗族自治县', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '500243', '彭水苗族土家族自治县', 1 FROM fa_cities WHERE code = '500100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510104', '锦江区', 1 FROM fa_cities WHERE code = '510100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510105', '青羊区', 1 FROM fa_cities WHERE code = '510100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510106', '金牛区', 1 FROM fa_cities WHERE code = '510100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510107', '武侯区', 1 FROM fa_cities WHERE code = '510100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510108', '成华区', 1 FROM fa_cities WHERE code = '510100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510112', '龙泉驿区', 1 FROM fa_cities WHERE code = '510100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510113', '青白江区', 1 FROM fa_cities WHERE code = '510100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510114', '新都区', 1 FROM fa_cities WHERE code = '510100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510115', '温江区', 1 FROM fa_cities WHERE code = '510100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510116', '双流区', 1 FROM fa_cities WHERE code = '510100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510117', '郫都区', 1 FROM fa_cities WHERE code = '510100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510118', '新津区', 1 FROM fa_cities WHERE code = '510100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510121', '金堂县', 1 FROM fa_cities WHERE code = '510100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510129', '大邑县', 1 FROM fa_cities WHERE code = '510100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510131', '蒲江县', 1 FROM fa_cities WHERE code = '510100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510181', '都江堰市', 1 FROM fa_cities WHERE code = '510100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510182', '彭州市', 1 FROM fa_cities WHERE code = '510100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510183', '邛崃市', 1 FROM fa_cities WHERE code = '510100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510184', '崇州市', 1 FROM fa_cities WHERE code = '510100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510185', '简阳市', 1 FROM fa_cities WHERE code = '510100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510302', '自流井区', 1 FROM fa_cities WHERE code = '510300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510303', '贡井区', 1 FROM fa_cities WHERE code = '510300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510304', '大安区', 1 FROM fa_cities WHERE code = '510300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510311', '沿滩区', 1 FROM fa_cities WHERE code = '510300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510321', '荣县', 1 FROM fa_cities WHERE code = '510300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510322', '富顺县', 1 FROM fa_cities WHERE code = '510300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510402', '东区', 1 FROM fa_cities WHERE code = '510400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510403', '西区', 1 FROM fa_cities WHERE code = '510400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510411', '仁和区', 1 FROM fa_cities WHERE code = '510400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510421', '米易县', 1 FROM fa_cities WHERE code = '510400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510422', '盐边县', 1 FROM fa_cities WHERE code = '510400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510502', '江阳区', 1 FROM fa_cities WHERE code = '510500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510503', '纳溪区', 1 FROM fa_cities WHERE code = '510500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510504', '龙马潭区', 1 FROM fa_cities WHERE code = '510500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510521', '泸县', 1 FROM fa_cities WHERE code = '510500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510522', '合江县', 1 FROM fa_cities WHERE code = '510500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510524', '叙永县', 1 FROM fa_cities WHERE code = '510500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510525', '古蔺县', 1 FROM fa_cities WHERE code = '510500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510603', '旌阳区', 1 FROM fa_cities WHERE code = '510600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510604', '罗江区', 1 FROM fa_cities WHERE code = '510600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510623', '中江县', 1 FROM fa_cities WHERE code = '510600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510681', '广汉市', 1 FROM fa_cities WHERE code = '510600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510682', '什邡市', 1 FROM fa_cities WHERE code = '510600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510683', '绵竹市', 1 FROM fa_cities WHERE code = '510600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510703', '涪城区', 1 FROM fa_cities WHERE code = '510700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510704', '游仙区', 1 FROM fa_cities WHERE code = '510700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510705', '安州区', 1 FROM fa_cities WHERE code = '510700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510722', '三台县', 1 FROM fa_cities WHERE code = '510700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510723', '盐亭县', 1 FROM fa_cities WHERE code = '510700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510725', '梓潼县', 1 FROM fa_cities WHERE code = '510700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510726', '北川羌族自治县', 1 FROM fa_cities WHERE code = '510700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510727', '平武县', 1 FROM fa_cities WHERE code = '510700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510781', '江油市', 1 FROM fa_cities WHERE code = '510700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510802', '利州区', 1 FROM fa_cities WHERE code = '510800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510811', '昭化区', 1 FROM fa_cities WHERE code = '510800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510812', '朝天区', 1 FROM fa_cities WHERE code = '510800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510821', '旺苍县', 1 FROM fa_cities WHERE code = '510800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510822', '青川县', 1 FROM fa_cities WHERE code = '510800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510823', '剑阁县', 1 FROM fa_cities WHERE code = '510800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510824', '苍溪县', 1 FROM fa_cities WHERE code = '510800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510903', '船山区', 1 FROM fa_cities WHERE code = '510900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510904', '安居区', 1 FROM fa_cities WHERE code = '510900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510921', '蓬溪县', 1 FROM fa_cities WHERE code = '510900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510923', '大英县', 1 FROM fa_cities WHERE code = '510900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '510981', '射洪市', 1 FROM fa_cities WHERE code = '510900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511002', '市中区', 1 FROM fa_cities WHERE code = '511000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511011', '东兴区', 1 FROM fa_cities WHERE code = '511000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511024', '威远县', 1 FROM fa_cities WHERE code = '511000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511025', '资中县', 1 FROM fa_cities WHERE code = '511000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511083', '隆昌市', 1 FROM fa_cities WHERE code = '511000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511102', '市中区', 1 FROM fa_cities WHERE code = '511100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511111', '沙湾区', 1 FROM fa_cities WHERE code = '511100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511112', '五通桥区', 1 FROM fa_cities WHERE code = '511100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511113', '金口河区', 1 FROM fa_cities WHERE code = '511100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511123', '犍为县', 1 FROM fa_cities WHERE code = '511100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511124', '井研县', 1 FROM fa_cities WHERE code = '511100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511126', '夹江县', 1 FROM fa_cities WHERE code = '511100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511129', '沐川县', 1 FROM fa_cities WHERE code = '511100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511132', '峨边彝族自治县', 1 FROM fa_cities WHERE code = '511100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511133', '马边彝族自治县', 1 FROM fa_cities WHERE code = '511100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511181', '峨眉山市', 1 FROM fa_cities WHERE code = '511100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511302', '顺庆区', 1 FROM fa_cities WHERE code = '511300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511303', '高坪区', 1 FROM fa_cities WHERE code = '511300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511304', '嘉陵区', 1 FROM fa_cities WHERE code = '511300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511321', '南部县', 1 FROM fa_cities WHERE code = '511300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511322', '营山县', 1 FROM fa_cities WHERE code = '511300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511323', '蓬安县', 1 FROM fa_cities WHERE code = '511300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511324', '仪陇县', 1 FROM fa_cities WHERE code = '511300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511325', '西充县', 1 FROM fa_cities WHERE code = '511300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511381', '阆中市', 1 FROM fa_cities WHERE code = '511300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511402', '东坡区', 1 FROM fa_cities WHERE code = '511400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511403', '彭山区', 1 FROM fa_cities WHERE code = '511400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511421', '仁寿县', 1 FROM fa_cities WHERE code = '511400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511423', '洪雅县', 1 FROM fa_cities WHERE code = '511400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511424', '丹棱县', 1 FROM fa_cities WHERE code = '511400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511425', '青神县', 1 FROM fa_cities WHERE code = '511400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511502', '翠屏区', 1 FROM fa_cities WHERE code = '511500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511503', '南溪区', 1 FROM fa_cities WHERE code = '511500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511504', '叙州区', 1 FROM fa_cities WHERE code = '511500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511523', '江安县', 1 FROM fa_cities WHERE code = '511500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511524', '长宁县', 1 FROM fa_cities WHERE code = '511500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511525', '高县', 1 FROM fa_cities WHERE code = '511500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511526', '珙县', 1 FROM fa_cities WHERE code = '511500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511527', '筠连县', 1 FROM fa_cities WHERE code = '511500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511528', '兴文县', 1 FROM fa_cities WHERE code = '511500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511529', '屏山县', 1 FROM fa_cities WHERE code = '511500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511602', '广安区', 1 FROM fa_cities WHERE code = '511600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511603', '前锋区', 1 FROM fa_cities WHERE code = '511600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511621', '岳池县', 1 FROM fa_cities WHERE code = '511600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511622', '武胜县', 1 FROM fa_cities WHERE code = '511600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511623', '邻水县', 1 FROM fa_cities WHERE code = '511600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511681', '华蓥市', 1 FROM fa_cities WHERE code = '511600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511702', '通川区', 1 FROM fa_cities WHERE code = '511700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511703', '达川区', 1 FROM fa_cities WHERE code = '511700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511722', '宣汉县', 1 FROM fa_cities WHERE code = '511700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511723', '开江县', 1 FROM fa_cities WHERE code = '511700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511724', '大竹县', 1 FROM fa_cities WHERE code = '511700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511725', '渠县', 1 FROM fa_cities WHERE code = '511700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511781', '万源市', 1 FROM fa_cities WHERE code = '511700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511802', '雨城区', 1 FROM fa_cities WHERE code = '511800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511803', '名山区', 1 FROM fa_cities WHERE code = '511800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511822', '荥经县', 1 FROM fa_cities WHERE code = '511800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511823', '汉源县', 1 FROM fa_cities WHERE code = '511800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511824', '石棉县', 1 FROM fa_cities WHERE code = '511800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511825', '天全县', 1 FROM fa_cities WHERE code = '511800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511826', '芦山县', 1 FROM fa_cities WHERE code = '511800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511827', '宝兴县', 1 FROM fa_cities WHERE code = '511800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511902', '巴州区', 1 FROM fa_cities WHERE code = '511900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511903', '恩阳区', 1 FROM fa_cities WHERE code = '511900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511921', '通江县', 1 FROM fa_cities WHERE code = '511900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511922', '南江县', 1 FROM fa_cities WHERE code = '511900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '511923', '平昌县', 1 FROM fa_cities WHERE code = '511900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '512002', '雁江区', 1 FROM fa_cities WHERE code = '512000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '512021', '安岳县', 1 FROM fa_cities WHERE code = '512000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '512022', '乐至县', 1 FROM fa_cities WHERE code = '512000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513201', '马尔康市', 1 FROM fa_cities WHERE code = '513200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513221', '汶川县', 1 FROM fa_cities WHERE code = '513200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513222', '理县', 1 FROM fa_cities WHERE code = '513200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513223', '茂县', 1 FROM fa_cities WHERE code = '513200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513224', '松潘县', 1 FROM fa_cities WHERE code = '513200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513225', '九寨沟县', 1 FROM fa_cities WHERE code = '513200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513226', '金川县', 1 FROM fa_cities WHERE code = '513200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513227', '小金县', 1 FROM fa_cities WHERE code = '513200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513228', '黑水县', 1 FROM fa_cities WHERE code = '513200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513230', '壤塘县', 1 FROM fa_cities WHERE code = '513200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513231', '阿坝县', 1 FROM fa_cities WHERE code = '513200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513232', '若尔盖县', 1 FROM fa_cities WHERE code = '513200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513233', '红原县', 1 FROM fa_cities WHERE code = '513200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513301', '康定市', 1 FROM fa_cities WHERE code = '513300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513322', '泸定县', 1 FROM fa_cities WHERE code = '513300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513323', '丹巴县', 1 FROM fa_cities WHERE code = '513300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513324', '九龙县', 1 FROM fa_cities WHERE code = '513300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513325', '雅江县', 1 FROM fa_cities WHERE code = '513300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513326', '道孚县', 1 FROM fa_cities WHERE code = '513300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513327', '炉霍县', 1 FROM fa_cities WHERE code = '513300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513328', '甘孜县', 1 FROM fa_cities WHERE code = '513300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513329', '新龙县', 1 FROM fa_cities WHERE code = '513300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513330', '德格县', 1 FROM fa_cities WHERE code = '513300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513331', '白玉县', 1 FROM fa_cities WHERE code = '513300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513332', '石渠县', 1 FROM fa_cities WHERE code = '513300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513333', '色达县', 1 FROM fa_cities WHERE code = '513300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513334', '理塘县', 1 FROM fa_cities WHERE code = '513300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513335', '巴塘县', 1 FROM fa_cities WHERE code = '513300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513336', '乡城县', 1 FROM fa_cities WHERE code = '513300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513337', '稻城县', 1 FROM fa_cities WHERE code = '513300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513338', '得荣县', 1 FROM fa_cities WHERE code = '513300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513401', '西昌市', 1 FROM fa_cities WHERE code = '513400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513402', '会理市', 1 FROM fa_cities WHERE code = '513400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513422', '木里藏族自治县', 1 FROM fa_cities WHERE code = '513400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513423', '盐源县', 1 FROM fa_cities WHERE code = '513400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513424', '德昌县', 1 FROM fa_cities WHERE code = '513400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513426', '会东县', 1 FROM fa_cities WHERE code = '513400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513427', '宁南县', 1 FROM fa_cities WHERE code = '513400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513428', '普格县', 1 FROM fa_cities WHERE code = '513400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513429', '布拖县', 1 FROM fa_cities WHERE code = '513400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513430', '金阳县', 1 FROM fa_cities WHERE code = '513400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513431', '昭觉县', 1 FROM fa_cities WHERE code = '513400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513432', '喜德县', 1 FROM fa_cities WHERE code = '513400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513433', '冕宁县', 1 FROM fa_cities WHERE code = '513400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513434', '越西县', 1 FROM fa_cities WHERE code = '513400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513435', '甘洛县', 1 FROM fa_cities WHERE code = '513400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513436', '美姑县', 1 FROM fa_cities WHERE code = '513400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '513437', '雷波县', 1 FROM fa_cities WHERE code = '513400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520102', '南明区', 1 FROM fa_cities WHERE code = '520100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520103', '云岩区', 1 FROM fa_cities WHERE code = '520100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520111', '花溪区', 1 FROM fa_cities WHERE code = '520100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520112', '乌当区', 1 FROM fa_cities WHERE code = '520100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520113', '白云区', 1 FROM fa_cities WHERE code = '520100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520115', '观山湖区', 1 FROM fa_cities WHERE code = '520100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520121', '开阳县', 1 FROM fa_cities WHERE code = '520100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520122', '息烽县', 1 FROM fa_cities WHERE code = '520100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520123', '修文县', 1 FROM fa_cities WHERE code = '520100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520181', '清镇市', 1 FROM fa_cities WHERE code = '520100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520201', '钟山区', 1 FROM fa_cities WHERE code = '520200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520203', '六枝特区', 1 FROM fa_cities WHERE code = '520200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520204', '水城区', 1 FROM fa_cities WHERE code = '520200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520281', '盘州市', 1 FROM fa_cities WHERE code = '520200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520302', '红花岗区', 1 FROM fa_cities WHERE code = '520300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520303', '汇川区', 1 FROM fa_cities WHERE code = '520300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520304', '播州区', 1 FROM fa_cities WHERE code = '520300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520322', '桐梓县', 1 FROM fa_cities WHERE code = '520300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520323', '绥阳县', 1 FROM fa_cities WHERE code = '520300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520324', '正安县', 1 FROM fa_cities WHERE code = '520300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520325', '道真仡佬族苗族自治县', 1 FROM fa_cities WHERE code = '520300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520326', '务川仡佬族苗族自治县', 1 FROM fa_cities WHERE code = '520300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520327', '凤冈县', 1 FROM fa_cities WHERE code = '520300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520328', '湄潭县', 1 FROM fa_cities WHERE code = '520300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520329', '余庆县', 1 FROM fa_cities WHERE code = '520300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520330', '习水县', 1 FROM fa_cities WHERE code = '520300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520381', '赤水市', 1 FROM fa_cities WHERE code = '520300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520382', '仁怀市', 1 FROM fa_cities WHERE code = '520300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520402', '西秀区', 1 FROM fa_cities WHERE code = '520400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520403', '平坝区', 1 FROM fa_cities WHERE code = '520400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520422', '普定县', 1 FROM fa_cities WHERE code = '520400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520423', '镇宁布依族苗族自治县', 1 FROM fa_cities WHERE code = '520400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520424', '关岭布依族苗族自治县', 1 FROM fa_cities WHERE code = '520400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520425', '紫云苗族布依族自治县', 1 FROM fa_cities WHERE code = '520400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520502', '七星关区', 1 FROM fa_cities WHERE code = '520500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520521', '大方县', 1 FROM fa_cities WHERE code = '520500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520523', '金沙县', 1 FROM fa_cities WHERE code = '520500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520524', '织金县', 1 FROM fa_cities WHERE code = '520500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520525', '纳雍县', 1 FROM fa_cities WHERE code = '520500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520526', '威宁彝族回族苗族自治县', 1 FROM fa_cities WHERE code = '520500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520527', '赫章县', 1 FROM fa_cities WHERE code = '520500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520581', '黔西市', 1 FROM fa_cities WHERE code = '520500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520602', '碧江区', 1 FROM fa_cities WHERE code = '520600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520603', '万山区', 1 FROM fa_cities WHERE code = '520600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520621', '江口县', 1 FROM fa_cities WHERE code = '520600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520622', '玉屏侗族自治县', 1 FROM fa_cities WHERE code = '520600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520623', '石阡县', 1 FROM fa_cities WHERE code = '520600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520624', '思南县', 1 FROM fa_cities WHERE code = '520600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520625', '印江土家族苗族自治县', 1 FROM fa_cities WHERE code = '520600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520626', '德江县', 1 FROM fa_cities WHERE code = '520600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520627', '沿河土家族自治县', 1 FROM fa_cities WHERE code = '520600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '520628', '松桃苗族自治县', 1 FROM fa_cities WHERE code = '520600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522301', '兴义市', 1 FROM fa_cities WHERE code = '522300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522302', '兴仁市', 1 FROM fa_cities WHERE code = '522300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522323', '普安县', 1 FROM fa_cities WHERE code = '522300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522324', '晴隆县', 1 FROM fa_cities WHERE code = '522300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522325', '贞丰县', 1 FROM fa_cities WHERE code = '522300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522326', '望谟县', 1 FROM fa_cities WHERE code = '522300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522327', '册亨县', 1 FROM fa_cities WHERE code = '522300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522328', '安龙县', 1 FROM fa_cities WHERE code = '522300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522601', '凯里市', 1 FROM fa_cities WHERE code = '522600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522622', '黄平县', 1 FROM fa_cities WHERE code = '522600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522623', '施秉县', 1 FROM fa_cities WHERE code = '522600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522624', '三穗县', 1 FROM fa_cities WHERE code = '522600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522625', '镇远县', 1 FROM fa_cities WHERE code = '522600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522626', '岑巩县', 1 FROM fa_cities WHERE code = '522600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522627', '天柱县', 1 FROM fa_cities WHERE code = '522600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522628', '锦屏县', 1 FROM fa_cities WHERE code = '522600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522629', '剑河县', 1 FROM fa_cities WHERE code = '522600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522630', '台江县', 1 FROM fa_cities WHERE code = '522600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522631', '黎平县', 1 FROM fa_cities WHERE code = '522600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522632', '榕江县', 1 FROM fa_cities WHERE code = '522600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522633', '从江县', 1 FROM fa_cities WHERE code = '522600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522634', '雷山县', 1 FROM fa_cities WHERE code = '522600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522635', '麻江县', 1 FROM fa_cities WHERE code = '522600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522636', '丹寨县', 1 FROM fa_cities WHERE code = '522600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522701', '都匀市', 1 FROM fa_cities WHERE code = '522700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522702', '福泉市', 1 FROM fa_cities WHERE code = '522700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522722', '荔波县', 1 FROM fa_cities WHERE code = '522700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522723', '贵定县', 1 FROM fa_cities WHERE code = '522700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522725', '瓮安县', 1 FROM fa_cities WHERE code = '522700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522726', '独山县', 1 FROM fa_cities WHERE code = '522700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522727', '平塘县', 1 FROM fa_cities WHERE code = '522700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522728', '罗甸县', 1 FROM fa_cities WHERE code = '522700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522729', '长顺县', 1 FROM fa_cities WHERE code = '522700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522730', '龙里县', 1 FROM fa_cities WHERE code = '522700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522731', '惠水县', 1 FROM fa_cities WHERE code = '522700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '522732', '三都水族自治县', 1 FROM fa_cities WHERE code = '522700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530102', '五华区', 1 FROM fa_cities WHERE code = '530100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530103', '盘龙区', 1 FROM fa_cities WHERE code = '530100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530111', '官渡区', 1 FROM fa_cities WHERE code = '530100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530112', '西山区', 1 FROM fa_cities WHERE code = '530100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530113', '东川区', 1 FROM fa_cities WHERE code = '530100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530114', '呈贡区', 1 FROM fa_cities WHERE code = '530100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530115', '晋宁区', 1 FROM fa_cities WHERE code = '530100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530124', '富民县', 1 FROM fa_cities WHERE code = '530100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530125', '宜良县', 1 FROM fa_cities WHERE code = '530100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530126', '石林彝族自治县', 1 FROM fa_cities WHERE code = '530100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530127', '嵩明县', 1 FROM fa_cities WHERE code = '530100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530128', '禄劝彝族苗族自治县', 1 FROM fa_cities WHERE code = '530100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530129', '寻甸回族彝族自治县', 1 FROM fa_cities WHERE code = '530100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530181', '安宁市', 1 FROM fa_cities WHERE code = '530100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530302', '麒麟区', 1 FROM fa_cities WHERE code = '530300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530303', '沾益区', 1 FROM fa_cities WHERE code = '530300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530304', '马龙区', 1 FROM fa_cities WHERE code = '530300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530322', '陆良县', 1 FROM fa_cities WHERE code = '530300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530323', '师宗县', 1 FROM fa_cities WHERE code = '530300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530324', '罗平县', 1 FROM fa_cities WHERE code = '530300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530325', '富源县', 1 FROM fa_cities WHERE code = '530300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530326', '会泽县', 1 FROM fa_cities WHERE code = '530300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530381', '宣威市', 1 FROM fa_cities WHERE code = '530300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530402', '红塔区', 1 FROM fa_cities WHERE code = '530400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530403', '江川区', 1 FROM fa_cities WHERE code = '530400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530423', '通海县', 1 FROM fa_cities WHERE code = '530400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530424', '华宁县', 1 FROM fa_cities WHERE code = '530400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530425', '易门县', 1 FROM fa_cities WHERE code = '530400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530426', '峨山彝族自治县', 1 FROM fa_cities WHERE code = '530400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530427', '新平彝族傣族自治县', 1 FROM fa_cities WHERE code = '530400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530428', '元江哈尼族彝族傣族自治县', 1 FROM fa_cities WHERE code = '530400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530481', '澄江市', 1 FROM fa_cities WHERE code = '530400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530502', '隆阳区', 1 FROM fa_cities WHERE code = '530500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530521', '施甸县', 1 FROM fa_cities WHERE code = '530500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530523', '龙陵县', 1 FROM fa_cities WHERE code = '530500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530524', '昌宁县', 1 FROM fa_cities WHERE code = '530500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530581', '腾冲市', 1 FROM fa_cities WHERE code = '530500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530602', '昭阳区', 1 FROM fa_cities WHERE code = '530600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530621', '鲁甸县', 1 FROM fa_cities WHERE code = '530600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530622', '巧家县', 1 FROM fa_cities WHERE code = '530600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530623', '盐津县', 1 FROM fa_cities WHERE code = '530600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530624', '大关县', 1 FROM fa_cities WHERE code = '530600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530625', '永善县', 1 FROM fa_cities WHERE code = '530600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530626', '绥江县', 1 FROM fa_cities WHERE code = '530600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530627', '镇雄县', 1 FROM fa_cities WHERE code = '530600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530628', '彝良县', 1 FROM fa_cities WHERE code = '530600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530629', '威信县', 1 FROM fa_cities WHERE code = '530600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530681', '水富市', 1 FROM fa_cities WHERE code = '530600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530702', '古城区', 1 FROM fa_cities WHERE code = '530700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530721', '玉龙纳西族自治县', 1 FROM fa_cities WHERE code = '530700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530722', '永胜县', 1 FROM fa_cities WHERE code = '530700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530723', '华坪县', 1 FROM fa_cities WHERE code = '530700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530724', '宁蒗彝族自治县', 1 FROM fa_cities WHERE code = '530700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530802', '思茅区', 1 FROM fa_cities WHERE code = '530800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530821', '宁洱哈尼族彝族自治县', 1 FROM fa_cities WHERE code = '530800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530822', '墨江哈尼族自治县', 1 FROM fa_cities WHERE code = '530800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530823', '景东彝族自治县', 1 FROM fa_cities WHERE code = '530800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530824', '景谷傣族彝族自治县', 1 FROM fa_cities WHERE code = '530800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530825', '镇沅彝族哈尼族拉祜族自治县', 1 FROM fa_cities WHERE code = '530800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530826', '江城哈尼族彝族自治县', 1 FROM fa_cities WHERE code = '530800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530827', '孟连傣族拉祜族佤族自治县', 1 FROM fa_cities WHERE code = '530800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530828', '澜沧拉祜族自治县', 1 FROM fa_cities WHERE code = '530800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530829', '西盟佤族自治县', 1 FROM fa_cities WHERE code = '530800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530902', '临翔区', 1 FROM fa_cities WHERE code = '530900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530921', '凤庆县', 1 FROM fa_cities WHERE code = '530900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530922', '云县', 1 FROM fa_cities WHERE code = '530900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530923', '永德县', 1 FROM fa_cities WHERE code = '530900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530924', '镇康县', 1 FROM fa_cities WHERE code = '530900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530925', '双江拉祜族佤族布朗族傣族自治县', 1 FROM fa_cities WHERE code = '530900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530926', '耿马傣族佤族自治县', 1 FROM fa_cities WHERE code = '530900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '530927', '沧源佤族自治县', 1 FROM fa_cities WHERE code = '530900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532301', '楚雄市', 1 FROM fa_cities WHERE code = '532300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532302', '禄丰市', 1 FROM fa_cities WHERE code = '532300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532322', '双柏县', 1 FROM fa_cities WHERE code = '532300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532323', '牟定县', 1 FROM fa_cities WHERE code = '532300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532324', '南华县', 1 FROM fa_cities WHERE code = '532300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532325', '姚安县', 1 FROM fa_cities WHERE code = '532300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532326', '大姚县', 1 FROM fa_cities WHERE code = '532300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532327', '永仁县', 1 FROM fa_cities WHERE code = '532300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532328', '元谋县', 1 FROM fa_cities WHERE code = '532300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532329', '武定县', 1 FROM fa_cities WHERE code = '532300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532501', '个旧市', 1 FROM fa_cities WHERE code = '532500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532502', '开远市', 1 FROM fa_cities WHERE code = '532500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532503', '蒙自市', 1 FROM fa_cities WHERE code = '532500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532504', '弥勒市', 1 FROM fa_cities WHERE code = '532500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532523', '屏边苗族自治县', 1 FROM fa_cities WHERE code = '532500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532524', '建水县', 1 FROM fa_cities WHERE code = '532500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532525', '石屏县', 1 FROM fa_cities WHERE code = '532500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532527', '泸西县', 1 FROM fa_cities WHERE code = '532500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532528', '元阳县', 1 FROM fa_cities WHERE code = '532500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532529', '红河县', 1 FROM fa_cities WHERE code = '532500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532530', '金平苗族瑶族傣族自治县', 1 FROM fa_cities WHERE code = '532500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532531', '绿春县', 1 FROM fa_cities WHERE code = '532500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532532', '河口瑶族自治县', 1 FROM fa_cities WHERE code = '532500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532601', '文山市', 1 FROM fa_cities WHERE code = '532600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532622', '砚山县', 1 FROM fa_cities WHERE code = '532600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532623', '西畴县', 1 FROM fa_cities WHERE code = '532600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532624', '麻栗坡县', 1 FROM fa_cities WHERE code = '532600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532625', '马关县', 1 FROM fa_cities WHERE code = '532600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532626', '丘北县', 1 FROM fa_cities WHERE code = '532600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532627', '广南县', 1 FROM fa_cities WHERE code = '532600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532628', '富宁县', 1 FROM fa_cities WHERE code = '532600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532801', '景洪市', 1 FROM fa_cities WHERE code = '532800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532822', '勐海县', 1 FROM fa_cities WHERE code = '532800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532823', '勐腊县', 1 FROM fa_cities WHERE code = '532800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532901', '大理市', 1 FROM fa_cities WHERE code = '532900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532922', '漾濞彝族自治县', 1 FROM fa_cities WHERE code = '532900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532923', '祥云县', 1 FROM fa_cities WHERE code = '532900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532924', '宾川县', 1 FROM fa_cities WHERE code = '532900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532925', '弥渡县', 1 FROM fa_cities WHERE code = '532900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532926', '南涧彝族自治县', 1 FROM fa_cities WHERE code = '532900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532927', '巍山彝族回族自治县', 1 FROM fa_cities WHERE code = '532900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532928', '永平县', 1 FROM fa_cities WHERE code = '532900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532929', '云龙县', 1 FROM fa_cities WHERE code = '532900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532930', '洱源县', 1 FROM fa_cities WHERE code = '532900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532931', '剑川县', 1 FROM fa_cities WHERE code = '532900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '532932', '鹤庆县', 1 FROM fa_cities WHERE code = '532900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '533102', '瑞丽市', 1 FROM fa_cities WHERE code = '533100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '533103', '芒市', 1 FROM fa_cities WHERE code = '533100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '533122', '梁河县', 1 FROM fa_cities WHERE code = '533100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '533123', '盈江县', 1 FROM fa_cities WHERE code = '533100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '533124', '陇川县', 1 FROM fa_cities WHERE code = '533100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '533301', '泸水市', 1 FROM fa_cities WHERE code = '533300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '533323', '福贡县', 1 FROM fa_cities WHERE code = '533300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '533324', '贡山独龙族怒族自治县', 1 FROM fa_cities WHERE code = '533300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '533325', '兰坪白族普米族自治县', 1 FROM fa_cities WHERE code = '533300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '533401', '香格里拉市', 1 FROM fa_cities WHERE code = '533400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '533422', '德钦县', 1 FROM fa_cities WHERE code = '533400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '533423', '维西傈僳族自治县', 1 FROM fa_cities WHERE code = '533400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540102', '城关区', 1 FROM fa_cities WHERE code = '540100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540103', '堆龙德庆区', 1 FROM fa_cities WHERE code = '540100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540104', '达孜区', 1 FROM fa_cities WHERE code = '540100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540121', '林周县', 1 FROM fa_cities WHERE code = '540100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540122', '当雄县', 1 FROM fa_cities WHERE code = '540100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540123', '尼木县', 1 FROM fa_cities WHERE code = '540100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540124', '曲水县', 1 FROM fa_cities WHERE code = '540100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540127', '墨竹工卡县', 1 FROM fa_cities WHERE code = '540100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540171', '格尔木藏青工业园区', 1 FROM fa_cities WHERE code = '540100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540172', '拉萨经济技术开发区', 1 FROM fa_cities WHERE code = '540100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540173', '西藏文化旅游创意园区', 1 FROM fa_cities WHERE code = '540100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540174', '达孜工业园区', 1 FROM fa_cities WHERE code = '540100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540202', '桑珠孜区', 1 FROM fa_cities WHERE code = '540200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540221', '南木林县', 1 FROM fa_cities WHERE code = '540200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540222', '江孜县', 1 FROM fa_cities WHERE code = '540200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540223', '定日县', 1 FROM fa_cities WHERE code = '540200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540224', '萨迦县', 1 FROM fa_cities WHERE code = '540200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540225', '拉孜县', 1 FROM fa_cities WHERE code = '540200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540226', '昂仁县', 1 FROM fa_cities WHERE code = '540200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540227', '谢通门县', 1 FROM fa_cities WHERE code = '540200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540228', '白朗县', 1 FROM fa_cities WHERE code = '540200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540229', '仁布县', 1 FROM fa_cities WHERE code = '540200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540230', '康马县', 1 FROM fa_cities WHERE code = '540200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540231', '定结县', 1 FROM fa_cities WHERE code = '540200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540232', '仲巴县', 1 FROM fa_cities WHERE code = '540200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540233', '亚东县', 1 FROM fa_cities WHERE code = '540200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540234', '吉隆县', 1 FROM fa_cities WHERE code = '540200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540235', '聂拉木县', 1 FROM fa_cities WHERE code = '540200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540236', '萨嘎县', 1 FROM fa_cities WHERE code = '540200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540237', '岗巴县', 1 FROM fa_cities WHERE code = '540200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540302', '卡若区', 1 FROM fa_cities WHERE code = '540300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540321', '江达县', 1 FROM fa_cities WHERE code = '540300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540322', '贡觉县', 1 FROM fa_cities WHERE code = '540300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540323', '类乌齐县', 1 FROM fa_cities WHERE code = '540300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540324', '丁青县', 1 FROM fa_cities WHERE code = '540300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540325', '察雅县', 1 FROM fa_cities WHERE code = '540300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540326', '八宿县', 1 FROM fa_cities WHERE code = '540300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540327', '左贡县', 1 FROM fa_cities WHERE code = '540300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540328', '芒康县', 1 FROM fa_cities WHERE code = '540300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540329', '洛隆县', 1 FROM fa_cities WHERE code = '540300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540330', '边坝县', 1 FROM fa_cities WHERE code = '540300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540402', '巴宜区', 1 FROM fa_cities WHERE code = '540400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540421', '工布江达县', 1 FROM fa_cities WHERE code = '540400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540423', '墨脱县', 1 FROM fa_cities WHERE code = '540400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540424', '波密县', 1 FROM fa_cities WHERE code = '540400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540425', '察隅县', 1 FROM fa_cities WHERE code = '540400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540426', '朗县', 1 FROM fa_cities WHERE code = '540400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540481', '米林市', 1 FROM fa_cities WHERE code = '540400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540502', '乃东区', 1 FROM fa_cities WHERE code = '540500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540521', '扎囊县', 1 FROM fa_cities WHERE code = '540500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540522', '贡嘎县', 1 FROM fa_cities WHERE code = '540500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540523', '桑日县', 1 FROM fa_cities WHERE code = '540500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540524', '琼结县', 1 FROM fa_cities WHERE code = '540500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540525', '曲松县', 1 FROM fa_cities WHERE code = '540500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540526', '措美县', 1 FROM fa_cities WHERE code = '540500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540527', '洛扎县', 1 FROM fa_cities WHERE code = '540500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540528', '加查县', 1 FROM fa_cities WHERE code = '540500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540529', '隆子县', 1 FROM fa_cities WHERE code = '540500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540531', '浪卡子县', 1 FROM fa_cities WHERE code = '540500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540581', '错那市', 1 FROM fa_cities WHERE code = '540500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540602', '色尼区', 1 FROM fa_cities WHERE code = '540600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540621', '嘉黎县', 1 FROM fa_cities WHERE code = '540600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540622', '比如县', 1 FROM fa_cities WHERE code = '540600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540623', '聂荣县', 1 FROM fa_cities WHERE code = '540600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540624', '安多县', 1 FROM fa_cities WHERE code = '540600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540625', '申扎县', 1 FROM fa_cities WHERE code = '540600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540626', '索县', 1 FROM fa_cities WHERE code = '540600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540627', '班戈县', 1 FROM fa_cities WHERE code = '540600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540628', '巴青县', 1 FROM fa_cities WHERE code = '540600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540629', '尼玛县', 1 FROM fa_cities WHERE code = '540600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '540630', '双湖县', 1 FROM fa_cities WHERE code = '540600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '542521', '普兰县', 1 FROM fa_cities WHERE code = '542500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '542522', '札达县', 1 FROM fa_cities WHERE code = '542500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '542523', '噶尔县', 1 FROM fa_cities WHERE code = '542500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '542524', '日土县', 1 FROM fa_cities WHERE code = '542500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '542525', '革吉县', 1 FROM fa_cities WHERE code = '542500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '542526', '改则县', 1 FROM fa_cities WHERE code = '542500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '542527', '措勤县', 1 FROM fa_cities WHERE code = '542500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610102', '新城区', 1 FROM fa_cities WHERE code = '610100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610103', '碑林区', 1 FROM fa_cities WHERE code = '610100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610104', '莲湖区', 1 FROM fa_cities WHERE code = '610100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610111', '灞桥区', 1 FROM fa_cities WHERE code = '610100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610112', '未央区', 1 FROM fa_cities WHERE code = '610100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610113', '雁塔区', 1 FROM fa_cities WHERE code = '610100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610114', '阎良区', 1 FROM fa_cities WHERE code = '610100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610115', '临潼区', 1 FROM fa_cities WHERE code = '610100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610116', '长安区', 1 FROM fa_cities WHERE code = '610100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610117', '高陵区', 1 FROM fa_cities WHERE code = '610100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610118', '鄠邑区', 1 FROM fa_cities WHERE code = '610100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610122', '蓝田县', 1 FROM fa_cities WHERE code = '610100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610124', '周至县', 1 FROM fa_cities WHERE code = '610100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610202', '王益区', 1 FROM fa_cities WHERE code = '610200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610203', '印台区', 1 FROM fa_cities WHERE code = '610200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610204', '耀州区', 1 FROM fa_cities WHERE code = '610200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610222', '宜君县', 1 FROM fa_cities WHERE code = '610200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610302', '渭滨区', 1 FROM fa_cities WHERE code = '610300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610303', '金台区', 1 FROM fa_cities WHERE code = '610300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610304', '陈仓区', 1 FROM fa_cities WHERE code = '610300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610305', '凤翔区', 1 FROM fa_cities WHERE code = '610300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610323', '岐山县', 1 FROM fa_cities WHERE code = '610300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610324', '扶风县', 1 FROM fa_cities WHERE code = '610300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610326', '眉县', 1 FROM fa_cities WHERE code = '610300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610327', '陇县', 1 FROM fa_cities WHERE code = '610300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610328', '千阳县', 1 FROM fa_cities WHERE code = '610300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610329', '麟游县', 1 FROM fa_cities WHERE code = '610300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610330', '凤县', 1 FROM fa_cities WHERE code = '610300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610331', '太白县', 1 FROM fa_cities WHERE code = '610300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610402', '秦都区', 1 FROM fa_cities WHERE code = '610400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610403', '杨陵区', 1 FROM fa_cities WHERE code = '610400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610404', '渭城区', 1 FROM fa_cities WHERE code = '610400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610422', '三原县', 1 FROM fa_cities WHERE code = '610400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610423', '泾阳县', 1 FROM fa_cities WHERE code = '610400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610424', '乾县', 1 FROM fa_cities WHERE code = '610400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610425', '礼泉县', 1 FROM fa_cities WHERE code = '610400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610426', '永寿县', 1 FROM fa_cities WHERE code = '610400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610428', '长武县', 1 FROM fa_cities WHERE code = '610400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610429', '旬邑县', 1 FROM fa_cities WHERE code = '610400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610430', '淳化县', 1 FROM fa_cities WHERE code = '610400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610431', '武功县', 1 FROM fa_cities WHERE code = '610400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610481', '兴平市', 1 FROM fa_cities WHERE code = '610400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610482', '彬州市', 1 FROM fa_cities WHERE code = '610400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610502', '临渭区', 1 FROM fa_cities WHERE code = '610500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610503', '华州区', 1 FROM fa_cities WHERE code = '610500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610522', '潼关县', 1 FROM fa_cities WHERE code = '610500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610523', '大荔县', 1 FROM fa_cities WHERE code = '610500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610524', '合阳县', 1 FROM fa_cities WHERE code = '610500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610525', '澄城县', 1 FROM fa_cities WHERE code = '610500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610526', '蒲城县', 1 FROM fa_cities WHERE code = '610500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610527', '白水县', 1 FROM fa_cities WHERE code = '610500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610528', '富平县', 1 FROM fa_cities WHERE code = '610500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610581', '韩城市', 1 FROM fa_cities WHERE code = '610500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610582', '华阴市', 1 FROM fa_cities WHERE code = '610500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610602', '宝塔区', 1 FROM fa_cities WHERE code = '610600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610603', '安塞区', 1 FROM fa_cities WHERE code = '610600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610621', '延长县', 1 FROM fa_cities WHERE code = '610600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610622', '延川县', 1 FROM fa_cities WHERE code = '610600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610625', '志丹县', 1 FROM fa_cities WHERE code = '610600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610626', '吴起县', 1 FROM fa_cities WHERE code = '610600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610627', '甘泉县', 1 FROM fa_cities WHERE code = '610600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610628', '富县', 1 FROM fa_cities WHERE code = '610600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610629', '洛川县', 1 FROM fa_cities WHERE code = '610600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610630', '宜川县', 1 FROM fa_cities WHERE code = '610600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610631', '黄龙县', 1 FROM fa_cities WHERE code = '610600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610632', '黄陵县', 1 FROM fa_cities WHERE code = '610600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610681', '子长市', 1 FROM fa_cities WHERE code = '610600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610702', '汉台区', 1 FROM fa_cities WHERE code = '610700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610703', '南郑区', 1 FROM fa_cities WHERE code = '610700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610722', '城固县', 1 FROM fa_cities WHERE code = '610700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610723', '洋县', 1 FROM fa_cities WHERE code = '610700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610724', '西乡县', 1 FROM fa_cities WHERE code = '610700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610725', '勉县', 1 FROM fa_cities WHERE code = '610700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610726', '宁强县', 1 FROM fa_cities WHERE code = '610700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610727', '略阳县', 1 FROM fa_cities WHERE code = '610700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610728', '镇巴县', 1 FROM fa_cities WHERE code = '610700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610729', '留坝县', 1 FROM fa_cities WHERE code = '610700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610730', '佛坪县', 1 FROM fa_cities WHERE code = '610700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610802', '榆阳区', 1 FROM fa_cities WHERE code = '610800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610803', '横山区', 1 FROM fa_cities WHERE code = '610800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610822', '府谷县', 1 FROM fa_cities WHERE code = '610800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610824', '靖边县', 1 FROM fa_cities WHERE code = '610800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610825', '定边县', 1 FROM fa_cities WHERE code = '610800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610826', '绥德县', 1 FROM fa_cities WHERE code = '610800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610827', '米脂县', 1 FROM fa_cities WHERE code = '610800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610828', '佳县', 1 FROM fa_cities WHERE code = '610800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610829', '吴堡县', 1 FROM fa_cities WHERE code = '610800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610830', '清涧县', 1 FROM fa_cities WHERE code = '610800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610831', '子洲县', 1 FROM fa_cities WHERE code = '610800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610881', '神木市', 1 FROM fa_cities WHERE code = '610800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610902', '汉滨区', 1 FROM fa_cities WHERE code = '610900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610921', '汉阴县', 1 FROM fa_cities WHERE code = '610900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610922', '石泉县', 1 FROM fa_cities WHERE code = '610900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610923', '宁陕县', 1 FROM fa_cities WHERE code = '610900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610924', '紫阳县', 1 FROM fa_cities WHERE code = '610900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610925', '岚皋县', 1 FROM fa_cities WHERE code = '610900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610926', '平利县', 1 FROM fa_cities WHERE code = '610900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610927', '镇坪县', 1 FROM fa_cities WHERE code = '610900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610929', '白河县', 1 FROM fa_cities WHERE code = '610900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '610981', '旬阳市', 1 FROM fa_cities WHERE code = '610900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '611002', '商州区', 1 FROM fa_cities WHERE code = '611000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '611021', '洛南县', 1 FROM fa_cities WHERE code = '611000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '611022', '丹凤县', 1 FROM fa_cities WHERE code = '611000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '611023', '商南县', 1 FROM fa_cities WHERE code = '611000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '611024', '山阳县', 1 FROM fa_cities WHERE code = '611000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '611025', '镇安县', 1 FROM fa_cities WHERE code = '611000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '611026', '柞水县', 1 FROM fa_cities WHERE code = '611000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620102', '城关区', 1 FROM fa_cities WHERE code = '620100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620103', '七里河区', 1 FROM fa_cities WHERE code = '620100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620104', '西固区', 1 FROM fa_cities WHERE code = '620100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620105', '安宁区', 1 FROM fa_cities WHERE code = '620100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620111', '红古区', 1 FROM fa_cities WHERE code = '620100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620121', '永登县', 1 FROM fa_cities WHERE code = '620100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620122', '皋兰县', 1 FROM fa_cities WHERE code = '620100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620123', '榆中县', 1 FROM fa_cities WHERE code = '620100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620171', '兰州新区', 1 FROM fa_cities WHERE code = '620100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620302', '金川区', 1 FROM fa_cities WHERE code = '620300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620321', '永昌县', 1 FROM fa_cities WHERE code = '620300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620402', '白银区', 1 FROM fa_cities WHERE code = '620400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620403', '平川区', 1 FROM fa_cities WHERE code = '620400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620421', '靖远县', 1 FROM fa_cities WHERE code = '620400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620422', '会宁县', 1 FROM fa_cities WHERE code = '620400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620423', '景泰县', 1 FROM fa_cities WHERE code = '620400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620502', '秦州区', 1 FROM fa_cities WHERE code = '620500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620503', '麦积区', 1 FROM fa_cities WHERE code = '620500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620521', '清水县', 1 FROM fa_cities WHERE code = '620500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620522', '秦安县', 1 FROM fa_cities WHERE code = '620500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620523', '甘谷县', 1 FROM fa_cities WHERE code = '620500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620524', '武山县', 1 FROM fa_cities WHERE code = '620500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620525', '张家川回族自治县', 1 FROM fa_cities WHERE code = '620500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620602', '凉州区', 1 FROM fa_cities WHERE code = '620600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620621', '民勤县', 1 FROM fa_cities WHERE code = '620600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620622', '古浪县', 1 FROM fa_cities WHERE code = '620600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620623', '天祝藏族自治县', 1 FROM fa_cities WHERE code = '620600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620702', '甘州区', 1 FROM fa_cities WHERE code = '620700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620721', '肃南裕固族自治县', 1 FROM fa_cities WHERE code = '620700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620722', '民乐县', 1 FROM fa_cities WHERE code = '620700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620723', '临泽县', 1 FROM fa_cities WHERE code = '620700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620724', '高台县', 1 FROM fa_cities WHERE code = '620700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620725', '山丹县', 1 FROM fa_cities WHERE code = '620700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620802', '崆峒区', 1 FROM fa_cities WHERE code = '620800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620821', '泾川县', 1 FROM fa_cities WHERE code = '620800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620822', '灵台县', 1 FROM fa_cities WHERE code = '620800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620823', '崇信县', 1 FROM fa_cities WHERE code = '620800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620825', '庄浪县', 1 FROM fa_cities WHERE code = '620800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620826', '静宁县', 1 FROM fa_cities WHERE code = '620800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620881', '华亭市', 1 FROM fa_cities WHERE code = '620800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620902', '肃州区', 1 FROM fa_cities WHERE code = '620900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620921', '金塔县', 1 FROM fa_cities WHERE code = '620900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620922', '瓜州县', 1 FROM fa_cities WHERE code = '620900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620923', '肃北蒙古族自治县', 1 FROM fa_cities WHERE code = '620900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620924', '阿克塞哈萨克族自治县', 1 FROM fa_cities WHERE code = '620900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620981', '玉门市', 1 FROM fa_cities WHERE code = '620900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '620982', '敦煌市', 1 FROM fa_cities WHERE code = '620900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '621002', '西峰区', 1 FROM fa_cities WHERE code = '621000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '621021', '庆城县', 1 FROM fa_cities WHERE code = '621000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '621022', '环县', 1 FROM fa_cities WHERE code = '621000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '621023', '华池县', 1 FROM fa_cities WHERE code = '621000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '621024', '合水县', 1 FROM fa_cities WHERE code = '621000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '621025', '正宁县', 1 FROM fa_cities WHERE code = '621000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '621026', '宁县', 1 FROM fa_cities WHERE code = '621000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '621027', '镇原县', 1 FROM fa_cities WHERE code = '621000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '621102', '安定区', 1 FROM fa_cities WHERE code = '621100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '621121', '通渭县', 1 FROM fa_cities WHERE code = '621100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '621122', '陇西县', 1 FROM fa_cities WHERE code = '621100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '621123', '渭源县', 1 FROM fa_cities WHERE code = '621100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '621124', '临洮县', 1 FROM fa_cities WHERE code = '621100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '621125', '漳县', 1 FROM fa_cities WHERE code = '621100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '621126', '岷县', 1 FROM fa_cities WHERE code = '621100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '621202', '武都区', 1 FROM fa_cities WHERE code = '621200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '621221', '成县', 1 FROM fa_cities WHERE code = '621200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '621222', '文县', 1 FROM fa_cities WHERE code = '621200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '621223', '宕昌县', 1 FROM fa_cities WHERE code = '621200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '621224', '康县', 1 FROM fa_cities WHERE code = '621200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '621225', '西和县', 1 FROM fa_cities WHERE code = '621200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '621226', '礼县', 1 FROM fa_cities WHERE code = '621200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '621227', '徽县', 1 FROM fa_cities WHERE code = '621200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '621228', '两当县', 1 FROM fa_cities WHERE code = '621200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '622901', '临夏市', 1 FROM fa_cities WHERE code = '622900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '622921', '临夏县', 1 FROM fa_cities WHERE code = '622900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '622922', '康乐县', 1 FROM fa_cities WHERE code = '622900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '622923', '永靖县', 1 FROM fa_cities WHERE code = '622900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '622924', '广河县', 1 FROM fa_cities WHERE code = '622900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '622925', '和政县', 1 FROM fa_cities WHERE code = '622900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '622926', '东乡族自治县', 1 FROM fa_cities WHERE code = '622900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '622927', '积石山保安族东乡族撒拉族自治县', 1 FROM fa_cities WHERE code = '622900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '623001', '合作市', 1 FROM fa_cities WHERE code = '623000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '623021', '临潭县', 1 FROM fa_cities WHERE code = '623000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '623022', '卓尼县', 1 FROM fa_cities WHERE code = '623000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '623023', '舟曲县', 1 FROM fa_cities WHERE code = '623000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '623024', '迭部县', 1 FROM fa_cities WHERE code = '623000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '623025', '玛曲县', 1 FROM fa_cities WHERE code = '623000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '623026', '碌曲县', 1 FROM fa_cities WHERE code = '623000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '623027', '夏河县', 1 FROM fa_cities WHERE code = '623000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '630102', '城东区', 1 FROM fa_cities WHERE code = '630100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '630103', '城中区', 1 FROM fa_cities WHERE code = '630100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '630104', '城西区', 1 FROM fa_cities WHERE code = '630100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '630105', '城北区', 1 FROM fa_cities WHERE code = '630100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '630106', '湟中区', 1 FROM fa_cities WHERE code = '630100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '630121', '大通回族土族自治县', 1 FROM fa_cities WHERE code = '630100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '630123', '湟源县', 1 FROM fa_cities WHERE code = '630100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '630202', '乐都区', 1 FROM fa_cities WHERE code = '630200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '630203', '平安区', 1 FROM fa_cities WHERE code = '630200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '630222', '民和回族土族自治县', 1 FROM fa_cities WHERE code = '630200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '630223', '互助土族自治县', 1 FROM fa_cities WHERE code = '630200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '630224', '化隆回族自治县', 1 FROM fa_cities WHERE code = '630200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '630225', '循化撒拉族自治县', 1 FROM fa_cities WHERE code = '630200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632221', '门源回族自治县', 1 FROM fa_cities WHERE code = '632200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632222', '祁连县', 1 FROM fa_cities WHERE code = '632200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632223', '海晏县', 1 FROM fa_cities WHERE code = '632200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632224', '刚察县', 1 FROM fa_cities WHERE code = '632200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632301', '同仁市', 1 FROM fa_cities WHERE code = '632300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632322', '尖扎县', 1 FROM fa_cities WHERE code = '632300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632323', '泽库县', 1 FROM fa_cities WHERE code = '632300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632324', '河南蒙古族自治县', 1 FROM fa_cities WHERE code = '632300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632521', '共和县', 1 FROM fa_cities WHERE code = '632500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632522', '同德县', 1 FROM fa_cities WHERE code = '632500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632523', '贵德县', 1 FROM fa_cities WHERE code = '632500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632524', '兴海县', 1 FROM fa_cities WHERE code = '632500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632525', '贵南县', 1 FROM fa_cities WHERE code = '632500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632621', '玛沁县', 1 FROM fa_cities WHERE code = '632600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632622', '班玛县', 1 FROM fa_cities WHERE code = '632600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632623', '甘德县', 1 FROM fa_cities WHERE code = '632600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632624', '达日县', 1 FROM fa_cities WHERE code = '632600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632625', '久治县', 1 FROM fa_cities WHERE code = '632600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632626', '玛多县', 1 FROM fa_cities WHERE code = '632600' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632701', '玉树市', 1 FROM fa_cities WHERE code = '632700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632722', '杂多县', 1 FROM fa_cities WHERE code = '632700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632723', '称多县', 1 FROM fa_cities WHERE code = '632700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632724', '治多县', 1 FROM fa_cities WHERE code = '632700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632725', '囊谦县', 1 FROM fa_cities WHERE code = '632700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632726', '曲麻莱县', 1 FROM fa_cities WHERE code = '632700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632801', '格尔木市', 1 FROM fa_cities WHERE code = '632800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632802', '德令哈市', 1 FROM fa_cities WHERE code = '632800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632803', '茫崖市', 1 FROM fa_cities WHERE code = '632800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632821', '乌兰县', 1 FROM fa_cities WHERE code = '632800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632822', '都兰县', 1 FROM fa_cities WHERE code = '632800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632823', '天峻县', 1 FROM fa_cities WHERE code = '632800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '632857', '大柴旦行政委员会', 1 FROM fa_cities WHERE code = '632800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '640104', '兴庆区', 1 FROM fa_cities WHERE code = '640100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '640105', '西夏区', 1 FROM fa_cities WHERE code = '640100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '640106', '金凤区', 1 FROM fa_cities WHERE code = '640100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '640121', '永宁县', 1 FROM fa_cities WHERE code = '640100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '640122', '贺兰县', 1 FROM fa_cities WHERE code = '640100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '640181', '灵武市', 1 FROM fa_cities WHERE code = '640100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '640202', '大武口区', 1 FROM fa_cities WHERE code = '640200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '640205', '惠农区', 1 FROM fa_cities WHERE code = '640200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '640221', '平罗县', 1 FROM fa_cities WHERE code = '640200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '640302', '利通区', 1 FROM fa_cities WHERE code = '640300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '640303', '红寺堡区', 1 FROM fa_cities WHERE code = '640300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '640323', '盐池县', 1 FROM fa_cities WHERE code = '640300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '640324', '同心县', 1 FROM fa_cities WHERE code = '640300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '640381', '青铜峡市', 1 FROM fa_cities WHERE code = '640300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '640402', '原州区', 1 FROM fa_cities WHERE code = '640400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '640422', '西吉县', 1 FROM fa_cities WHERE code = '640400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '640423', '隆德县', 1 FROM fa_cities WHERE code = '640400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '640424', '泾源县', 1 FROM fa_cities WHERE code = '640400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '640425', '彭阳县', 1 FROM fa_cities WHERE code = '640400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '640502', '沙坡头区', 1 FROM fa_cities WHERE code = '640500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '640521', '中宁县', 1 FROM fa_cities WHERE code = '640500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '640522', '海原县', 1 FROM fa_cities WHERE code = '640500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '650102', '天山区', 1 FROM fa_cities WHERE code = '650100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '650103', '沙依巴克区', 1 FROM fa_cities WHERE code = '650100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '650104', '新市区', 1 FROM fa_cities WHERE code = '650100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '650105', '水磨沟区', 1 FROM fa_cities WHERE code = '650100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '650106', '头屯河区', 1 FROM fa_cities WHERE code = '650100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '650107', '达坂城区', 1 FROM fa_cities WHERE code = '650100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '650109', '米东区', 1 FROM fa_cities WHERE code = '650100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '650121', '乌鲁木齐县', 1 FROM fa_cities WHERE code = '650100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '650202', '独山子区', 1 FROM fa_cities WHERE code = '650200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '650203', '克拉玛依区', 1 FROM fa_cities WHERE code = '650200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '650204', '白碱滩区', 1 FROM fa_cities WHERE code = '650200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '650205', '乌尔禾区', 1 FROM fa_cities WHERE code = '650200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '650402', '高昌区', 1 FROM fa_cities WHERE code = '650400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '650421', '鄯善县', 1 FROM fa_cities WHERE code = '650400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '650422', '托克逊县', 1 FROM fa_cities WHERE code = '650400' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '650502', '伊州区', 1 FROM fa_cities WHERE code = '650500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '650521', '巴里坤哈萨克自治县', 1 FROM fa_cities WHERE code = '650500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '650522', '伊吾县', 1 FROM fa_cities WHERE code = '650500' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '652301', '昌吉市', 1 FROM fa_cities WHERE code = '652300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '652302', '阜康市', 1 FROM fa_cities WHERE code = '652300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '652323', '呼图壁县', 1 FROM fa_cities WHERE code = '652300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '652324', '玛纳斯县', 1 FROM fa_cities WHERE code = '652300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '652325', '奇台县', 1 FROM fa_cities WHERE code = '652300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '652327', '吉木萨尔县', 1 FROM fa_cities WHERE code = '652300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '652328', '木垒哈萨克自治县', 1 FROM fa_cities WHERE code = '652300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '652701', '博乐市', 1 FROM fa_cities WHERE code = '652700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '652702', '阿拉山口市', 1 FROM fa_cities WHERE code = '652700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '652722', '精河县', 1 FROM fa_cities WHERE code = '652700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '652723', '温泉县', 1 FROM fa_cities WHERE code = '652700' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '652801', '库尔勒市', 1 FROM fa_cities WHERE code = '652800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '652822', '轮台县', 1 FROM fa_cities WHERE code = '652800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '652823', '尉犁县', 1 FROM fa_cities WHERE code = '652800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '652824', '若羌县', 1 FROM fa_cities WHERE code = '652800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '652825', '且末县', 1 FROM fa_cities WHERE code = '652800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '652826', '焉耆回族自治县', 1 FROM fa_cities WHERE code = '652800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '652827', '和静县', 1 FROM fa_cities WHERE code = '652800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '652828', '和硕县', 1 FROM fa_cities WHERE code = '652800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '652829', '博湖县', 1 FROM fa_cities WHERE code = '652800' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '652901', '阿克苏市', 1 FROM fa_cities WHERE code = '652900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '652902', '库车市', 1 FROM fa_cities WHERE code = '652900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '652922', '温宿县', 1 FROM fa_cities WHERE code = '652900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '652924', '沙雅县', 1 FROM fa_cities WHERE code = '652900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '652925', '新和县', 1 FROM fa_cities WHERE code = '652900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '652926', '拜城县', 1 FROM fa_cities WHERE code = '652900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '652927', '乌什县', 1 FROM fa_cities WHERE code = '652900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '652928', '阿瓦提县', 1 FROM fa_cities WHERE code = '652900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '652929', '柯坪县', 1 FROM fa_cities WHERE code = '652900' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '653001', '阿图什市', 1 FROM fa_cities WHERE code = '653000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '653022', '阿克陶县', 1 FROM fa_cities WHERE code = '653000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '653023', '阿合奇县', 1 FROM fa_cities WHERE code = '653000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '653024', '乌恰县', 1 FROM fa_cities WHERE code = '653000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '653101', '喀什市', 1 FROM fa_cities WHERE code = '653100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '653121', '疏附县', 1 FROM fa_cities WHERE code = '653100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '653122', '疏勒县', 1 FROM fa_cities WHERE code = '653100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '653123', '英吉沙县', 1 FROM fa_cities WHERE code = '653100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '653124', '泽普县', 1 FROM fa_cities WHERE code = '653100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '653125', '莎车县', 1 FROM fa_cities WHERE code = '653100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '653126', '叶城县', 1 FROM fa_cities WHERE code = '653100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '653127', '麦盖提县', 1 FROM fa_cities WHERE code = '653100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '653128', '岳普湖县', 1 FROM fa_cities WHERE code = '653100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '653129', '伽师县', 1 FROM fa_cities WHERE code = '653100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '653130', '巴楚县', 1 FROM fa_cities WHERE code = '653100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '653131', '塔什库尔干塔吉克自治县', 1 FROM fa_cities WHERE code = '653100' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '653201', '和田市', 1 FROM fa_cities WHERE code = '653200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '653221', '和田县', 1 FROM fa_cities WHERE code = '653200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '653222', '墨玉县', 1 FROM fa_cities WHERE code = '653200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '653223', '皮山县', 1 FROM fa_cities WHERE code = '653200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '653224', '洛浦县', 1 FROM fa_cities WHERE code = '653200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '653225', '策勒县', 1 FROM fa_cities WHERE code = '653200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '653226', '于田县', 1 FROM fa_cities WHERE code = '653200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '653227', '民丰县', 1 FROM fa_cities WHERE code = '653200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '654002', '伊宁市', 1 FROM fa_cities WHERE code = '654000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '654003', '奎屯市', 1 FROM fa_cities WHERE code = '654000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '654004', '霍尔果斯市', 1 FROM fa_cities WHERE code = '654000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '654021', '伊宁县', 1 FROM fa_cities WHERE code = '654000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '654022', '察布查尔锡伯自治县', 1 FROM fa_cities WHERE code = '654000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '654023', '霍城县', 1 FROM fa_cities WHERE code = '654000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '654024', '巩留县', 1 FROM fa_cities WHERE code = '654000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '654025', '新源县', 1 FROM fa_cities WHERE code = '654000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '654026', '昭苏县', 1 FROM fa_cities WHERE code = '654000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '654027', '特克斯县', 1 FROM fa_cities WHERE code = '654000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '654028', '尼勒克县', 1 FROM fa_cities WHERE code = '654000' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '654201', '塔城市', 1 FROM fa_cities WHERE code = '654200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '654202', '乌苏市', 1 FROM fa_cities WHERE code = '654200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '654203', '沙湾市', 1 FROM fa_cities WHERE code = '654200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '654221', '额敏县', 1 FROM fa_cities WHERE code = '654200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '654224', '托里县', 1 FROM fa_cities WHERE code = '654200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '654225', '裕民县', 1 FROM fa_cities WHERE code = '654200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '654226', '和布克赛尔蒙古自治县', 1 FROM fa_cities WHERE code = '654200' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '654301', '阿勒泰市', 1 FROM fa_cities WHERE code = '654300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '654321', '布尔津县', 1 FROM fa_cities WHERE code = '654300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '654322', '富蕴县', 1 FROM fa_cities WHERE code = '654300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '654323', '福海县', 1 FROM fa_cities WHERE code = '654300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '654324', '哈巴河县', 1 FROM fa_cities WHERE code = '654300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '654325', '青河县', 1 FROM fa_cities WHERE code = '654300' LIMIT 1;
INSERT INTO fa_districts (city_id, code, name, status) SELECT id, '654326', '吉木乃县', 1 FROM fa_cities WHERE code = '654300' LIMIT 1;

SET FOREIGN_KEY_CHECKS = 1;

-- 验证导入结果
SELECT 'fa_provinces' AS 表名, COUNT(*) AS 记录数 FROM fa_provinces
UNION ALL
SELECT 'fa_cities', COUNT(*) FROM fa_cities
UNION ALL
SELECT 'fa_districts', COUNT(*) FROM fa_districts;
