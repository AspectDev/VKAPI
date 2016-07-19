<?php
class APIVK{
    /**
     * Токен пользователя от лица которого будут вызваны методы. 
     * 
     * @var string token
     */
    private $token;
    /**
     * Ссылка на апи
     * 
     * @var string url
     */
    private $apiUrl = "https://api.vk.com/method/";
    
    /**
     * Версия для работы api vk.com
     * 
     * @var float/string
     */
    private $apiVer = 5.52;
    
    /**
     * Установить токен пользователя.
     * 
     * @param string token
     */
    public function setToken($token){
        $this->token = $token;
        return true;
    }
    
    /**
     * Получить установленный токен пользователя.
     * 
     * @return string token
     */
    public function getToken(){
       return $this->token;
    }
    /**
     * Запрос статуса владельца токена (если не указан $userId), 
     * какого-либо пользователя соц.сети (если $userId положительно) 
     * или сообщества (если $userId отрицательно)
     * 
     * @param type $userId
     * @return bool/string Возвращает false в случае ошибки, строку со статусом в случае успеха.
     */
    public function getStatus($userId = false){
        $params = $this->getBaseParams();
        if(is_integer($userId)){
            $params["user_id"] = $userId;
        }
        if($response = $this->query("status.get", $params)){
            return $response["response"]["text"];
        }
        return false;
    }
    /**
     * Установить статус текущего пользователя или группы
     * 
     * @param string $text Текст статуса
     * @param int $group_id Целочисленный идентификатор группы.
     * @return bool Возвращает false в случае ошибки, true в случае успеха.
     */
    public function setStatus($text, $group_id = false){
        $params = $this->getBaseParams();
        $params["text"] = $text;
        if(!empty($group_id)){
            $params["group_id"] = $group_id;
        }
        if($this->query("status.set", $params)){
            return true;
        }
        return false;
    }
    /**
     * Помечает текущего пользователя как offline. 
     * 
     * @return boolean Возвращает false в случае ошибки, true в случае успеха.
     */
    public function setOffline(){
        $params = $this->getBaseParams();
        if($this->query("account.setOffline", $params)){
            return true;
        }
        return false;
    }
    
    /**
     * Помечает текущего пользователя как online на 15 минут. 
     * 
     * @return boolean Возвращает false в случае ошибки, true в случае успеха.
     */
    public function setOnline(){
        $params = $this->getBaseParams();
        $params["voip"] = 1; // Возможны ли видеозвонки для данного устройства 
        if($this->query("account.setOnline", $params)){
            return true;
        }
        return false;
    }
    /**
     * Поиск контактов.
     * 
     * @param array $contacts список контактов для поиска в виде массива
     * @param string $service сервис поиска ( email phone  twitter facebook odnoklassniki instagram google)
     * @return boolean/array Возвращает false в случае ошибки, массив в случае успеха.
     */
    public function lookupContacts($contacts,$service = "phone"){
        $params = $this->getBaseParams();
        $params["contacts"] = implode(",", $contacts);
        $params["service"] = $service;
        $params["return_all"] = 0;
        $params["fields"] = "nickname, domain, sex, bdate, city, country, timezone, photo_50, photo_100, photo_200_orig, has_mobile, contacts, education, online, relation, last_seen, status, can_write_private_message, can_see_all_posts, can_post, universities";
        
        if($response = $this->query("account.lookupContacts", $params)){
            return $response["response"];
        }
        return false;
    }
    /**
     * Добавить пользователя в черный список
     * 
     * @param int $userId
     * @return boolean Возвращает false в случае ошибки, true в случае успеха.
     */
    public function AddUser2BlackList($userId) {
        $params = $this->getBaseParams();
        $params["user_id"] = $userId;
        if($this->query("account.banUser", $params)){
            return true;
        }
        return false;
    }
    
    /**
     * Удалить пользователя из черного списка
     * 
     * @param int $userId
     * @return boolean Возвращает false в случае ошибки, true в случае успеха.
     */
    public function RemoveUserFromBlackList($userId) {
        $params = $this->getBaseParams();
        $params["user_id"] = $userId;
        if($this->query("account.unbanUser", $params)){
            return true;
        }
        return false;
    }
    /**
     * Получить тип объекта и его идентификатор по "короткому имени"
     * @param string $screenName
     * @return boolean/array Возвращает false в случае ошибки, array в случае успеха.
     */
    public function GetIdByScreenName($screenName) {
        $params = $this->getBaseParams();
        $params["screen_name"] = $screenName;
        if($response = $this->query("utils.resolveScreenName", $params)){
            return $response["response"];
        }
        return false;
    }
    /**
     * Передача запроса в API vk.com средствами cUrl
     * @param string название метода
     * @param array массив данных для передачи
     * @return string json ответ сервера
     */
    private function Request($method, $params){
        $get = http_build_query($params);
        if($ch = curl_init()){
            curl_setopt($ch, CURLOPT_URL, $this->apiUrl . $method."?".$get);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            return $result = curl_exec($ch);
        }else{
            die("Для работы класса ".get_class()." необходима библиотека cUrl");
        }
    }
    
    /**
     * Конвертирование json строки в ассоциативный массив
     * @param string $json
     * @return array
     */
    private function Json2Arr($json){
        return json_decode($json,TRUE);
    }
    /**
     * Менеджер ошибок. Я считаю, что каждый должен написать его самостоятельно под конкретно свои нужды. 
     * Поэтому тут просто набросок.
     * @param array $arrayError
     */
    private function ErrorManager($arrayError){
        switch ($arrayError["error"]["error_code"]){
            case 6:
                // Слишком много запросов в секунду. 
                break;
            case 14:
                //Требуется ввод кода с картинки (Captcha). 
                break;
            case 15:
                //Доступ запрещён. 
                break;
            case 16:
                //Требуется выполнение запросов по протоколу HTTPS, т.к. пользователь включил настройку, требующую работу через безопасное соединение. 
                break;
            case 17:
                //Нужно обновить токен. 
                break;
        }
    }
    /**
     * Получение базовых параметров
     * 
     * @return array
     */
    private function getBaseParams(){
        return array(
          'access_token'  => $this->token,
          'v' => $this->apiVer
        );
    }
    /**
     * Запрос к апи, конвертация ответа в массив, вызов менеджера ошибок, возвращение ответа
     * @param string $method вызываемый метод
     * @param array $params параметры запроса
     * @return boolean/array false в случае ошибки, array в случае успеха
     */
    private function query($method,$params){
        $json = $this->Request($method, $params);
        $response = $this->Json2Arr($json);
        if(!empty($response["error"])){
            $this->ErrorManager($response["error"]);
            return FALSE;
        }
        return $response;
    }
}











$token = "08127e68b9e3433d70f1bfc634def157f577a4effa5dc0a473eec4012a10496d66181f02a78092f7064b0";
$api = new APIVK;
$api->setToken($token);
$status = $api->GetIdByScreenName("php_ini");
var_dump($status);