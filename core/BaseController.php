<?php
/**
 * 基础控制器
 * 
 * 提供：
 *   - 统一的 success/fail 响应
 *   - Request 实例访问
 *   - 分页参数获取
 */
abstract class BaseController
{
    /** @var Request */
    protected Request $request;

    public function __construct()
    {
        $this->request = Request::getInstance();
    }

    /**
     * 成功响应
     */
    protected function success(mixed $data = null, string $message = 'success'): JsonResponse
    {
        return JsonResponse::success($data, $message);
    }

    /**
     * 失败响应
     */
    protected function fail(string $message = 'fail', int $code = 400): JsonResponse
    {
        return JsonResponse::fail($message, $code);
    }

    /**
     * 分页响应
     */
    protected function page(array $list, int $total, ?int $page = null, ?int $pageSize = null): JsonResponse
    {
        if ($page === null) $page = $this->request->int('page', 1);
        if ($pageSize === null) $pageSize = $this->request->int('pageSize', 20);
        return JsonResponse::page($list, $total, $page, $pageSize);
    }

    /**
     * 未找到
     */
    protected function notFound(string $message = '资源不存在'): JsonResponse
    {
        return JsonResponse::notFound($message);
    }

    /**
     * 获取分页参数
     */
    protected function getPage(): array
    {
        return $this->request->getPage();
    }

    /**
     * 验证必填参数
     * 
     * @param array $rules ['name' => 'required', 'price' => 'required|numeric']
     * @return array|null 返回首个错误信息，验证通过返回 null
     */
    protected function validate(array $rules): ?string
    {
        foreach ($rules as $field => $ruleStr) {
            $ruleList = explode('|', $ruleStr);
            $value = $this->request->get($field);

            foreach ($ruleList as $rule) {
                $rule = trim($rule);

                if ($rule === 'required' && ($value === null || $value === '')) {
                    return "{$field} 不能为空";
                }
                
                if ($rule === 'numeric' && $value !== null && $value !== '' && !is_numeric($value)) {
                    return "{$field} 必须为数字";
                }
                
                if ($rule === 'integer' && $value !== null && $value !== '' && !ctype_digit((string)$value)) {
                    return "{$field} 必须为整数";
                }
                
                if (strpos($rule, 'min:') === 0 && is_numeric($value)) {
                    $min = (float)substr($rule, 4);
                    if ($value < $min) return "{$field} 不能小于 {$min}";
                }
                
                if (strpos($rule, 'max:') === 0 && is_numeric($value)) {
                    $max = (float)substr($rule, 4);
                    if ($value > $max) return "{$field} 不能大于 {$max}";
                }
            }
        }
        return null;
    }

    /**
     * 快速验证并失败响应
     * 
     * @return bool 验证通过返回 true，否则发送错误响应
     */
    protected function validateOrFail(array $rules): bool
    {
        $error = $this->validate($rules);
        if ($error !== null) {
            JsonResponse::validateFail($error)->send();
            // 不会执行到这里
        }
        return true;
    }
}
