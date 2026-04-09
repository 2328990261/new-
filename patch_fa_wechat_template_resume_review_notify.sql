-- 教师简历审核通知模板配置（服务号）
-- 目标模板ID：d188-NwSNYGaSvzjSkPgU97JVvuS8qNNGZt7-jm-GS8

-- 1) 若已存在模板代码 resume_review_notify，则更新为新模板ID并启用
UPDATE `fa_wechat_templates`
SET
  `template_id` = 'd188-NwSNYGaSvzjSkPgU97JVvuS8qNNGZt7-jm-GS8',
  `template_name` = '教师简历审核结果通知',
  `title` = '教师简历审核结果通知',
  `is_enabled` = 1,
  `field_mapping` = '{"const1":"{{result}}","time3":"{{review_time}}","const2":"{{remark}}"}',
  `remark` = '审核结果通知模板（const1=审核结果，time3=审核时间，const2=驳回原因）'
WHERE `template_code` = 'resume_review_notify';

-- 2) 若不存在模板代码 resume_review_notify，则新增
INSERT INTO `fa_wechat_templates`
(`template_code`, `template_name`, `template_id`, `title`, `content`, `is_enabled`, `field_mapping`, `remark`)
SELECT
  'resume_review_notify',
  '教师简历审核结果通知',
  'd188-NwSNYGaSvzjSkPgU97JVvuS8qNNGZt7-jm-GS8',
  '教师简历审核结果通知',
  '审核结果：{{const1.DATA}}\n审核时间：{{time3.DATA}}\n驳回原因：{{const2.DATA}}',
  1,
  '{"const1":"{{result}}","time3":"{{review_time}}","const2":"{{remark}}"}',
  '审核结果通知模板（const1=审核结果，time3=审核时间，const2=驳回原因）'
WHERE NOT EXISTS (
  SELECT 1 FROM `fa_wechat_templates` WHERE `template_code` = 'resume_review_notify'
);

