-- 人员薪酬测试数据
-- 注意：此脚本仅用于测试，请根据实际情况修改personnel_id

-- 假设已有人员ID为1、2、3的员工

-- 员工1的薪酬记录（有效）
INSERT INTO `fa_personnel_salary` (
  `personnel_id`, 
  `base_salary`, 
  `performance_salary`, 
  `post_allowance`, 
  `housing_allowance`, 
  `meal_allowance`, 
  `transport_allowance`, 
  `other_allowance`, 
  `total_salary`, 
  `effective_date`, 
  `end_date`, 
  `status`, 
  `remark`, 
  `create_time`, 
  `update_time`
) VALUES (
  1, 
  8000.00, 
  2000.00, 
  1000.00, 
  1500.00, 
  500.00, 
  300.00, 
  200.00, 
  13500.00, 
  '2024-01-01', 
  NULL, 
  1, 
  '2024年薪酬标准', 
  UNIX_TIMESTAMP(), 
  UNIX_TIMESTAMP()
);

-- 员工1的历史薪酬记录（已失效）
INSERT INTO `fa_personnel_salary` (
  `personnel_id`, 
  `base_salary`, 
  `performance_salary`, 
  `post_allowance`, 
  `housing_allowance`, 
  `meal_allowance`, 
  `transport_allowance`, 
  `other_allowance`, 
  `total_salary`, 
  `effective_date`, 
  `end_date`, 
  `status`, 
  `remark`, 
  `create_time`, 
  `update_time`
) VALUES (
  1, 
  7000.00, 
  1500.00, 
  800.00, 
  1200.00, 
  400.00, 
  300.00, 
  100.00, 
  11300.00, 
  '2023-01-01', 
  '2023-12-31', 
  0, 
  '2023年薪酬标准', 
  UNIX_TIMESTAMP(), 
  UNIX_TIMESTAMP()
);

-- 员工2的薪酬记录（有效）
INSERT INTO `fa_personnel_salary` (
  `personnel_id`, 
  `base_salary`, 
  `performance_salary`, 
  `post_allowance`, 
  `housing_allowance`, 
  `meal_allowance`, 
  `transport_allowance`, 
  `other_allowance`, 
  `total_salary`, 
  `effective_date`, 
  `end_date`, 
  `status`, 
  `remark`, 
  `create_time`, 
  `update_time`
) VALUES (
  2, 
  10000.00, 
  3000.00, 
  1500.00, 
  2000.00, 
  600.00, 
  400.00, 
  500.00, 
  18000.00, 
  '2024-01-01', 
  NULL, 
  1, 
  '管理层薪酬标准', 
  UNIX_TIMESTAMP(), 
  UNIX_TIMESTAMP()
);

-- 员工3的薪酬记录（有效）
INSERT INTO `fa_personnel_salary` (
  `personnel_id`, 
  `base_salary`, 
  `performance_salary`, 
  `post_allowance`, 
  `housing_allowance`, 
  `meal_allowance`, 
  `transport_allowance`, 
  `other_allowance`, 
  `total_salary`, 
  `effective_date`, 
  `end_date`, 
  `status`, 
  `remark`, 
  `create_time`, 
  `update_time`
) VALUES (
  3, 
  6000.00, 
  1000.00, 
  500.00, 
  1000.00, 
  400.00, 
  200.00, 
  100.00, 
  9200.00, 
  '2024-01-01', 
  NULL, 
  1, 
  '实习生薪酬标准', 
  UNIX_TIMESTAMP(), 
  UNIX_TIMESTAMP()
);

-- 查询验证
SELECT 
  ps.id,
  ps.personnel_id,
  p.name AS personnel_name,
  p.phone,
  p.position_name,
  ps.base_salary,
  ps.performance_salary,
  ps.total_salary,
  ps.effective_date,
  ps.end_date,
  ps.status,
  ps.remark
FROM fa_personnel_salary ps
LEFT JOIN fa_personnel p ON ps.personnel_id = p.id
WHERE ps.delete_time = 0
ORDER BY ps.id DESC;
