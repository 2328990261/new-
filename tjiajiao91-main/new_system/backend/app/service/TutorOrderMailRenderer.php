<?php
namespace app\service;

use think\facade\Db;

/**
 * 家教单（TutorOrder）通知邮件 HTML 渲染
 *
 * 说明：
 * - 后台「添加家教信息」与 API 侧入队逻辑历史上各自拼了一次模板，容易出现“改了 Order.php 但队列仍是旧样式”。
 * - 这里统一渲染，保证 body 一致。
 */
class TutorOrderMailRenderer
{
    /**
     * @param mixed $order TutorOrder（需已加载 city/district/subject 关联更佳）
     * @param array $config notification_config 行（find(1)）
     */
    public static function renderHtml($order, array $config): string
    {
        $cityName = $order->city ? (string)$order->city->name : '';
        $districtName = $order->district ? (string)$order->district->name : '';
        $subjectName = $order->subject ? (string)$order->subject->name : '';

        $rawContent = trim((string)($order->content ?? ''));
        if ($rawContent === '') {
            $lines = [];
            $loc = trim($cityName . ' ' . $districtName);
            if ($loc !== '') {
                $lines[] = '【城市区域】' . $loc;
            }
            if (trim((string)($order->grade ?? '')) !== '') {
                $lines[] = '【年级】' . trim((string)$order->grade);
            }
            if ($subjectName !== '') {
                $lines[] = '【科目】' . $subjectName;
            }
            if (trim((string)($order->salary ?? '')) !== '') {
                $lines[] = '【薪资】' . trim((string)$order->salary);
            }
            if (trim((string)($order->teacher_type ?? '')) !== '') {
                $lines[] = '【老师类型】' . trim((string)$order->teacher_type);
            }
            $rawContent = trim(implode("\n", $lines));
        }

        $customTpl = trim((string)($config['email_template'] ?? ''));
        if ($customTpl !== '' && strpos($customTpl, '{{content}}') !== false) {
            $replacements = [
                '{{city}}' => $cityName,
                '{{district}}' => $districtName,
                '{{grade}}' => (string)($order->grade ?: ''),
                '{{subject}}' => $subjectName,
                '{{salary}}' => (string)($order->salary ?: ''),
                '{{content}}' => nl2br(htmlspecialchars(trim((string)($order->content ?? '')))),
            ];

            return str_replace(array_keys($replacements), array_values($replacements), $customTpl);
        }

        // 默认模板（卡片风格，主色调绿色）
        $brandGreen = '#16a34a';
        $bg = '#f3f6fb';
        $cardBorder = '#e8eef6';
        $textStrong = '#0f172a';
        $text = '#111827';
        $muted = '#475569';
        $html = '<div style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Arial, \'PingFang SC\', \'Microsoft YaHei\', sans-serif; max-width: 720px; margin: 0 auto; padding: 18px 10px; background: ' . $bg . ';">';
        $html .= '<div style="border-radius: 18px; overflow: hidden; box-shadow: 0 10px 30px rgba(15,23,42,.08); border: 1px solid ' . $cardBorder . '; background: #ffffff;">';
        $html .= '<div style="background: linear-gradient(90deg,' . $brandGreen . ',#22c55e); color: #fff; padding: 22px 22px 18px; text-align: left;">';
        $html .= '<div style="font-size: 18px; font-weight: 800; line-height: 1.25; letter-spacing: .5px;">【91 家教中心】新家教单通知</div>';
        $html .= '<div style="font-size: 12px; opacity: .92; margin-top: 8px;">91家教中心—全国家教信息综合平台</div>';
        $html .= '</div>';
        $html .= '<div style="padding: 18px 18px 8px; background: ' . $bg . ';">';

        if ($rawContent !== '') {
            $html .= '<div style="background: #fff; padding: 16px; border-radius: 16px; margin-bottom: 12px; border: 1px solid ' . $cardBorder . '; box-shadow: 0 6px 18px rgba(15,23,42,.06);">';
            $html .= '<pre style="margin:0; white-space: pre-wrap; word-break: break-word; line-height: 1.8; font-size: 14px; color:' . $text . '; user-select:text;">' . htmlspecialchars($rawContent) . '</pre>';
            $html .= '</div>';
        }

        // 相关推荐：固定最多展示 3 条；候选多取一些以便过滤后仍够用
        $recommendations = [];
        try {
            $maxRecommend = 3;
            $scanLimit = 30;
            $q = Db::name('tutor_orders_new')->order('id', 'desc')->limit($scanLimit);
            $curId = (string)($order->id ?? '');
            if ($curId !== '') {
                $q->where('id', '<>', $curId);
            }
            if (!empty($order->city_id)) {
                $q->where('city_id', (int)$order->city_id);
            }
            if (!empty($order->district_id)) {
                $q->where('district_id', (int)$order->district_id);
            }
            if (!empty($order->subject_id)) {
                $q->where('subject_id', (int)$order->subject_id);
            }
            $rows = $q->select()->toArray();

            $gradeText = trim((string)($order->grade ?? ''));
            foreach ($rows as $r) {
                if ($gradeText !== '') {
                    $rGrade = trim((string)($r['grade'] ?? ''));
                    if ($rGrade !== '' && mb_strpos($gradeText, $rGrade) === false && mb_strpos($rGrade, $gradeText) === false) {
                        continue;
                    }
                }
                $recommendations[] = $r;
                if (count($recommendations) >= $maxRecommend) break;
            }
        } catch (\Throwable $e) {
            $recommendations = [];
        }

        if (!empty($recommendations)) {
            $html .= '<div style="background: #fff; padding: 16px; border-radius: 16px; margin-bottom: 12px; border: 1px solid ' . $cardBorder . '; box-shadow: 0 6px 18px rgba(15,23,42,.06);">';
            $html .= '<div style="font-weight: 800; color:' . $textStrong . '; margin-bottom: 12px; font-size: 15px;">相关推荐</div>';
            foreach ($recommendations as $r) {
                $rid = (string)($r['id'] ?? '');
                $rContent = trim((string)($r['content'] ?? ''));
                if ($rContent === '') {
                    $parts = [];
                    if (!empty($r['grade'])) $parts[] = '【年级】' . trim((string)$r['grade']);
                    if (!empty($r['salary'])) $parts[] = '【薪资】' . trim((string)$r['salary']);
                    if (!empty($r['teacher_type'])) $parts[] = '【老师类型】' . trim((string)$r['teacher_type']);
                    $rContent = trim(implode("\n", $parts));
                }
                if (mb_strlen($rContent) > 260) {
                    $rContent = mb_substr($rContent, 0, 260) . '…';
                }

                $meta = [];
                if (!empty($r['grade'])) $meta[] = (string)$r['grade'];
                if ($subjectName !== '') $meta[] = $subjectName;
                $loc = trim($cityName . ' ' . $districtName);
                if ($loc !== '') $meta[] = $loc;
                $metaText = trim(implode(' / ', array_filter($meta)));

                // 每条推荐用“左侧强调边”卡片（参考图2样式），主色调绿色
                $html .= '<div style="margin-top: 10px; background: #ffffff; border: 1px solid ' . $cardBorder . '; border-radius: 14px; overflow: hidden;">';
                $html .= '<div style="display:block; border-left: 5px solid ' . $brandGreen . '; padding: 12px 12px 12px 14px; background: #f8fffb;">';
                $html .= '<div style="font-size: 14px; font-weight: 800; color:' . $textStrong . '; line-height: 1.5;">家教单编号：' . htmlspecialchars($rid) . '</div>';
                if ($metaText !== '') {
                    $html .= '<div style="margin-top: 6px; font-size: 12px; color:' . $muted . '; line-height: 1.5;">' . htmlspecialchars($metaText) . '</div>';
                }
                if ($rContent !== '') {
                    $html .= '<div style="margin-top: 10px; font-size: 13px; color:' . $text . '; white-space: pre-wrap; word-break: break-word; line-height: 1.75;">' . htmlspecialchars($rContent) . '</div>';
                }
                $html .= '</div>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }

        $html .= '<div style="background: #fff; padding: 14px 16px; border-radius: 16px; border: 1px solid ' . $cardBorder . '; box-shadow: 0 6px 18px rgba(15,23,42,.06); margin-bottom: 12px;">';
        $html .= '<div style="font-size: 13px; color:' . $muted . '; line-height: 1.8;">如需联系方式，请通过平台按流程对接。此邮件由系统自动发送，请勿直接回复。</div>';
        $html .= '<div style="margin-top: 12px;">';
        $html .= '<a href="https://t.jiajiao91.com/user/city-tutor" style="display:inline-block; background: ' . $brandGreen . '; color:#ffffff; text-decoration:none; padding: 10px 14px; border-radius: 999px; font-size: 13px; font-weight: 700;">去平台查看更多家教单</a>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div style="text-align: center; padding: 8px 0 16px; color: #94a3b8; font-size: 12px;">';
        $html .= '<div style="margin-top: 2px;">© ' . date('Y') . ' 91家教中心</div>';
        $html .= '</div>';
        $html .= '</div>'; // content container
        $html .= '</div>'; // outer card
        $html .= '</div>'; // page bg wrapper

        return $html;
    }
}
