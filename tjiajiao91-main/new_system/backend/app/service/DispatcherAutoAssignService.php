<?php

namespace app\service;

use app\model\Admin;
use app\model\TutorOrder;

/**
 * 家教单自动派单：按订单城市匹配派单员归属城市，在同等工作量下随机选出一人，避免多人重复认领。
 */
class DispatcherAutoAssignService
{
    /**
     * 归一化派单员归属城市（支持数组或逗号分隔字符串）
     */
    public static function normalizeDispatcherCityIds($cityValue): array
    {
        if (is_array($cityValue)) {
            $raw = $cityValue;
        } else {
            $raw = explode(',', (string)($cityValue ?? ''));
        }
        $ids = array_map('intval', $raw);
        $ids = array_filter($ids, function ($id) {
            return $id > 0;
        });

        return array_values(array_unique($ids));
    }

    /**
     * 将订单派给一名符合条件的派单员（城市匹配 + 工作量最少者中随机）
     *
     * @param TutorOrder|\think\Model $order
     * @return bool 是否成功写入派单员
     */
    public static function assignToDispatcher($order): bool
    {
        try {
            $dispatchers = Admin::where('role', 'dispatcher')
                ->where('status', 1)
                ->order('id', 'asc')
                ->select()
                ->toArray();

            if (empty($dispatchers)) {
                trace('自动派单 - 没有可用的派单组成员', 'info');

                return false;
            }

            $orderCityId = (int)($order->city_id ?? 0);
            $eligible = $dispatchers;

            if ($orderCityId > 0) {
                $eligible = [];
                foreach ($dispatchers as $d) {
                    $cities = self::normalizeDispatcherCityIds($d['city_id'] ?? null);
                    if (in_array($orderCityId, $cities, true)) {
                        $eligible[] = $d;
                    }
                }
                if (empty($eligible)) {
                    trace("自动派单 - 订单 {$order->id} 城市 {$orderCityId} 无匹配归属城市的派单员", 'info');

                    return false;
                }
            }

            $dispatcherWorkloads = [];
            foreach ($eligible as $dispatcher) {
                $workloadQuery = TutorOrder::where('dispatcher_id', $dispatcher['id'])
                    ->where('status', 1);
                // 有订单城市时按“同城单量”均衡；无城市时退化为全量单量均衡
                if ($orderCityId > 0) {
                    $workloadQuery->where('city_id', $orderCityId);
                }
                $workload = $workloadQuery->count();

                $dispatcherWorkloads[] = [
                    'id' => $dispatcher['id'],
                    'nickname' => $dispatcher['nickname'] ?? $dispatcher['username'],
                    'contact_info' => $dispatcher['contact'] ?? '',
                    'workload' => $workload,
                ];
            }

            usort($dispatcherWorkloads, function ($a, $b) {
                if ($a['workload'] === $b['workload']) {
                    return $a['id'] - $b['id'];
                }

                return $a['workload'] - $b['workload'];
            });

            $minWorkload = $dispatcherWorkloads[0]['workload'];
            $candidates = array_values(array_filter(
                $dispatcherWorkloads,
                function ($d) use ($minWorkload) {
                    return $d['workload'] === $minWorkload;
                }
            ));
            $selectedDispatcher = $candidates[array_rand($candidates)];

            $dispatcher_info = Admin::where('id', $selectedDispatcher['id'])
                ->where('role', 'dispatcher')
                ->where('status', 1)
                ->find();

            if (!$dispatcher_info) {
                trace("警告：自动派单验证失败，派单员ID {$selectedDispatcher['id']}", 'warning');

                return false;
            }

            trace(
                "订单 {$order->id} 自动派单给: ID={$selectedDispatcher['id']}, nickname={$selectedDispatcher['nickname']}, workload={$selectedDispatcher['workload']}（同城最少单量内随机）",
                'info'
            );

            $order->dispatcher_id = $selectedDispatcher['id'];
            $order->contact_info = $selectedDispatcher['contact_info'];
            $order->assigned_time = date('Y-m-d H:i:s');
            $order->save();

            return true;
        } catch (\Exception $e) {
            trace('自动派单失败，订单ID: ' . ($order->id ?? '') . '，错误: ' . $e->getMessage(), 'error');

            return false;
        }
    }
}
