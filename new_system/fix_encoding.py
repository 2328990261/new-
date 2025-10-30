#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
修复Vue文件中的字符编码问题
"""

import os
import re
import sys

# 字符映射表 - 常见的乱码到正确字符的映射
CHAR_MAP = {
    '�?': '！',
    '？': '？',
    '�?': '吗',
    '�?': '呢',
    '�?': '么',
    '�?': '啊',
    '�?': '吧',
    '�?': '器',
    '�?': '与',
    '�?': '中',
    '�?': '的',
    '�?': '单',
    '�?': '务',
    '�?': '友',
    '�?': '：',
    '�?': '市',
    '�?': '与',
    '�?': '加',
    '�?': '次',
    '�?': '词',
    '�?': '部',
    '�?': '式',
    '�?': '化',
    '�?': '计',
    '�?': '态',
    '�?': '共',
}

# 常见的词组修复
WORD_MAP = {
    '找家教，就这�?': '找家教，就这么',
    '简�?': '简单',
    '分享给好�?': '分享给好友',
    '�?': '✨',
    '待点亮城�?': '待点亮城市',
    '市民已参�?': '市民已参与',
    '即将开�?': '即将开通',
    '轮播�?': '轮播器',
    '轮播指示�?': '轮播指示器',
    '搜索筛选区�?': '搜索筛选区域',
    '玻璃态效�?': '玻璃态效果',
    '现代化设�?': '现代化设计',
    '筛选面�?': '筛选面板',
    '列表标题和Tab': '列表标题和Tab',
    '加载�?': '加载中',
    '空状�?': '空状态',
    '试试调整筛选条件或搜索关键�?': '试试调整筛选条件或搜索关键词',
    '重置筛�?': '重置筛选',
    '加载中提�?': '加载中提示',
    '滚动加载触发�?': '滚动加载触发器',
    '已加载全�?': '已加载全部',
    '条信�?': '条信息',
    '�?': '共',
    '浏览人次计数�?': '浏览人次计数器',
    '是否加�?': '是否加急',
    '初始化浏览人�?': '初始化浏览人次',
    '�?': '从',
    '秒切�?': '秒切换',
    '使用默认�?': '使用默认值',
    '跳转到点亮城市页�?': '跳转到点亮城市页面',
    '提前300px开始加载（优化速度�?': '提前300px开始加载（优化速度）',
    '降低阈值，更快触发': '降低阈值，更快触发',
    '加载更多时追加数�?': '加载更多时追加数据',
    '专业的家教信息平台，为您提供优质的家教服�?': '专业的家教信息平台，为您提供优质的家教服务',
    '同步关键词搜�?': '同步关键词搜索',
    '重新设置观察�?': '重新设置观察器',
    '升级�?': '升级版',
    '搜索�?': '搜索框',
    '热门标签 - 现代化设�?': '热门标签 - 现代化设计',
    '搜索筛选置顶区�?': '搜索筛选置顶区域',
    '筛选面板包�?': '筛选面板包装',
    'Tab容器 - 现代化设�?': 'Tab容器 - 现代化设计',
    '无更多数�?': '无更多数据',
    '小屏幕优�?': '小屏幕优化',
    '搜索框优�?': '搜索框优化',
    '和统计信�?': '和统计信息',
    '更紧�?': '更紧凑',
    '仍保�?': '仍保持',
    '即刻开通你所在城市的家教服务�?': '即刻开通你所在城市的家教服务！',
}

def fix_encoding(content):
    """修复字符编码问题"""
    # 先修复词组
    for wrong, right in WORD_MAP.items():
        content = content.replace(wrong, right)
    
    # 再修复单个字符
    for wrong, right in CHAR_MAP.items():
        content = content.replace(wrong, right)
    
    return content

def fix_file(filepath):
    """修复单个文件"""
    try:
        with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
        
        original_content = content
        fixed_content = fix_encoding(content)
        
        if fixed_content != original_content:
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(fixed_content)
            print(f"✓ Fixed: {filepath}")
            return True
        else:
            print(f"○ No changes: {filepath}")
            return False
    except Exception as e:
        print(f"✗ Error fixing {filepath}: {e}")
        return False

def main():
    """主函数"""
    base_dir = os.path.dirname(os.path.abspath(__file__))
    frontend_dirs = [
        os.path.join(base_dir, 'frontend', 'user', 'src'),
        os.path.join(base_dir, 'frontend', 'admin', 'src'),
    ]
    
    total_files = 0
    fixed_files = 0
    
    for frontend_dir in frontend_dirs:
        if not os.path.exists(frontend_dir):
            continue
            
        for root, dirs, files in os.walk(frontend_dir):
            for file in files:
                if file.endswith(('.vue', '.js', '.ts')):
                    filepath = os.path.join(root, file)
                    total_files += 1
                    if fix_file(filepath):
                        fixed_files += 1
    
    print(f"\n{'='*50}")
    print(f"Total files scanned: {total_files}")
    print(f"Files fixed: {fixed_files}")
    print(f"{'='*50}")

if __name__ == '__main__':
    main()

