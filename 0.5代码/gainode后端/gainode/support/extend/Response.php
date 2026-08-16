<?php

namespace support\extend;

/**
 * 响应体结构定义基类
 *
 * $fields   — 所有字段元数据 {field: {type, description, children?}}
 *              children 值为 $children 中的 key，表示该数组字段的子元素字段组
 * $scenes   — 每个 action 的顶层返回字段
 * $children — 可复用的子字段组 {groupKey: [field1, field2, ...]}
 *
 * ————— 无限层级嵌套（Dot Notation）—————
 * $children / $scenes 中的字段支持用 点号 表示嵌套结构，任意深度：
 * ```
 * 'role.id'           → role { id }
 * 'role.name'         → role { id, name }
 * 'role.permission.id'→ role { permission { id } }  // 三层嵌套
 * 'a.b.c.d'           → a { b { c { d } } }        // 四层嵌套
 * ```
 * 所有出现相同父级的 dot 字段会自动合并到同一个对象节点下，
 * 同时兼容原有的 'children' => 'groupKey' 引用写法。
 */
class Response
{
    protected array $fields = [];
    protected array $scenes = [];
    protected array $children = [];

    /**
     * 构建树形响应结构（递归解析 children 引用）
     * 返回 [{field, type, description, children?}, ...]
     */
    public function buildTree(string $action): array
    {
        // scene 未定义时默认返回全部字段；scene 显式定义为空数组时表示无响应数据
        if (!array_key_exists($action, $this->scenes)) {
            $sceneFields = [];
        } else {
            $sceneFields = $this->scenes[$action];
        }
        return $this->resolveFields($sceneFields);
    }

    /**
     * 判断指定 action 是否声明了无响应数据
     */
    public function hasResponse(string $action): bool
    {
        if (!array_key_exists($action, $this->scenes)) {
            return true;
        }
        return !empty($this->scenes[$action]);
    }

    /**
     * 递归解析字段列表为树
     *
     * 支持两种嵌套方式（可混用）：
     * 1. 点号嵌套  — 'role.id', 'role.name' 自动生成 role { id, name } 对象
     * 2. 显式引用  — 'data' 字段通过 children => 'groupKey' 引用子字段组
     *
     * 点号嵌套支持任意深度：'role.permission.id' → role { permission { id } }
     *
     * 叶子字段会优先使用完整点路径的元数据（如 'role.id'），未定义时回退到根字段。
     */
    private function resolveFields(array $fieldKeys, string $prefix = ''): array
    {
        $result = [];
        $dotGroups = []; // parent => { _rendered: bool, _children: [childKey, ...] }

        // 第一遍：识别所有点号嵌套键
        foreach ($fieldKeys as $i => $key) {
            if (str_contains($key, '.')) {
                [$parent, $child] = explode('.', $key, 2);
                if (!isset($dotGroups[$parent])) {
                    $dotGroups[$parent] = ['_rendered' => false, '_children' => []];
                }
                $dotGroups[$parent]['_children'][] = $child;
            }
        }

        // 第二遍：按原始顺序构建结果，遇到点号父节点时展开为对象
        foreach ($fieldKeys as $key) {
            if (str_contains($key, '.')) {
                [$parent] = explode('.', $key, 2);
                if (isset($dotGroups[$parent]) && !$dotGroups[$parent]['_rendered']) {
                    $parentMeta = $this->fields[$parent] ?? [];
                    $fullPath = $prefix !== '' ? "{$prefix}.{$parent}" : $parent;
                    $node = [
                        'field'       => $parent,
                        'type'        => $parentMeta['type'] ?? 'object',
                        'description' => $parentMeta['description'] ?? '',
                        'children'    => $this->resolveFields($dotGroups[$parent]['_children'], $fullPath),
                    ];
                    $result[] = $node;
                    $dotGroups[$parent]['_rendered'] = true;
                }
                continue;
            }

            // 优先使用带前缀的完整路径元数据，如 'role.id'；未定义时回退到 'id'
            $fullKey = $prefix !== '' ? "{$prefix}.{$key}" : $key;
            $metaKey = isset($this->fields[$fullKey]) ? $fullKey : $key;

            if (!isset($this->fields[$metaKey])) {
                continue;
            }

            $meta = $this->fields[$metaKey];
            $node = [
                'field'       => $key,
                'type'        => $meta['type'] ?? 'string',
                'description' => $meta['description'] ?? '',
            ];

            // 兼容原有 children 引用写法
            if (!empty($meta['children']) && isset($this->children[$meta['children']])) {
                $node['children'] = $this->resolveFields($this->children[$meta['children']], '');
            }

            $result[] = $node;
        }

        return $result;
    }

    /**
     * 获取所有字段定义
     */
    public function getAllFields(): array
    {
        return $this->fields;
    }

    /**
     * 获取子字段组
     */
    public function getChildren(): array
    {
        return $this->children;
    }
}

