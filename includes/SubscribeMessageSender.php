<?php
/**
 * 微信小程序订阅消息推送类
 * 
 * 用于发送办理进度通知等订阅消息
 */

class SubscribeMessageSender
{
    private $appId;
    private $appSecret;
    private $templateId;
    private $accessToken;
    
    /**
     * 构造函数
     * @param string $appId 小程序 APPID
     * @param string $appSecret 小程序 SECRET
     * @param string $templateId 订阅消息模板 ID
     */
    public function __construct($appId, $appSecret, $templateId = null)
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->templateId = $templateId;
    }
    
    /**
     * 获取访问令牌
     * @return string access_token
     */
    public function getAccessToken()
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }
        
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->appId}&secret={$this->appSecret}";
        
        $response = $this->httpGet($url);
        $data = json_decode($response, true);
        
        if (isset($data['access_token'])) {
            $this->accessToken = $data['access_token'];
            return $this->accessToken;
        }
        
        throw new Exception('获取 access_token 失败：' . ($data['errmsg'] ?? '未知错误'));
    }
    
    /**
     * 发送订阅消息
     * 
     * @param string $openid 用户 openid
     * @param string $page 点击消息跳转的页面路径
     * @param array $data 模板数据，格式：['thing1' => ['value' => '内容'], 'time2' => ['value' => '2024-01-01']]
     * @param string $templateId 模板 ID（可选，不传则使用构造函数的）
     * @return array 返回结果 ['errcode' => 0, 'errmsg' => 'ok']
     */
    public function send($openid, $page, $data, $templateId = null)
    {
        $templateId = $templateId ?: $this->templateId;
        
        if (empty($templateId)) {
            throw new Exception('模板 ID 不能为空');
        }
        
        $accessToken = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token={$accessToken}";
        
        $postData = [
            'touser' => $openid,
            'template_id' => $templateId,
            'page' => $page,
            'miniprogram_state' => 'formal', // formal-正式版 trial-体验版 developer-开发版
            'lang' => 'zh_CN',
            'data' => $data
        ];
        
        $response = $this->httpPost($url, json_encode($postData));
        $result = json_decode($response, true);
        
        return $result;
    }
    
    /**
     * 发送办理进度通知（签证订单办理通知模板）
     * 
     * 模板字段：
     * - thing3: 办理人
     * - character_string1: 订单号
     * - thing5: 服务名称
     * - phone_number4: 联系电话
     * - time2: 办理时间
     * 
     * @param string $openid 用户 openid
     * @param array $progressData 进度数据，包含：
     *   - handler: 办理人
     *   - order_no: 订单号
     *   - service_name: 服务名称
     *   - phone: 联系电话
     *   - handle_time: 办理时间
     * @param string $templateId 模板 ID（可选）
     * @return array 返回结果
     */
    public function sendProgressNotification($openid, $progressData, $templateId = null)
    {
        // 根据模板字段构造数据
        $data = [
            'thing3' => ['value' => $progressData['handler'] ?? '青园营地'],
            'character_string1' => ['value' => $progressData['order_no'] ?? 'N/A'],
            'thing5' => ['value' => $progressData['service_name'] ?? '签证办理'],
            'phone_number4' => ['value' => $progressData['phone'] ?? '暂无'],
            'time2' => ['value' => $progressData['handle_time'] ?? date('Y-m-d H:i:s')]
        ];
        
        return $this->send($openid, 'pages/progress/progress', $data, $templateId);
    }
    
    /**
     * 批量发送订阅消息
     * 
     * @param array $users 用户列表，每项包含 ['openid' => 'xxx', 'data' => [...]]
     * @param string $templateId 模板 ID（可选）
     * @return array 返回结果 ['success' => count, 'failed' => [...]]
     */
    public function batchSend($users, $templateId = null)
    {
        $results = [
            'success' => 0,
            'failed' => []
        ];
        
        foreach ($users as $user) {
            try {
                $openid = $user['openid'];
                $page = $user['page'] ?? 'pages/progress/progress';
                $data = $user['data'] ?? [];
                
                $result = $this->send($openid, $page, $data, $templateId);
                
                if (isset($result['errcode']) && $result['errcode'] == 0) {
                    $results['success']++;
                } else {
                    $results['failed'][] = [
                        'openid' => $openid,
                        'error' => $result['errmsg'] ?? '发送失败'
                    ];
                }
            } catch (Exception $e) {
                $results['failed'][] = [
                    'openid' => $user['openid'],
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * HTTP GET 请求
     */
    private function httpGet($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('HTTP 请求失败：' . $error);
        }
        
        return $response;
    }
    
    /**
     * HTTP POST 请求
     */
    private function httpPost($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)
        ]);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('HTTP 请求失败：' . $error);
        }
        
        return $response;
    }
}
