<?php

/**
 * Execute sql query
 */
class dbAdminExecuteQueryProcessor extends modObjectProcessor {
    public $languageTopics = array('dbadmin');
    public $permission = 'sql_query_execute';

    /**
     * @return mixed
     * @throws Exception
     */
    public function process() {
        $query = trim(ltrim($this->getProperty('query'),'\n'));
        $select = preg_match('/^(select|show)/i',$query) ? true : false;
        // Заменяем класс на таблицу
        if (preg_match_all('/\{(\w+)\}/',$query,$match)) {
            for ($i=0; $i<count($match[0]); $i++) {
                $q = $this->modx->newQuery('dbAdminTable');
                $q->where(array(
                    'class:LIKE' => "{$match[1][$i]}",
                ));
                $q->select('name');
                $tableName = $this->modx->getValue($q->prepare());
                $query = str_replace($match[0][$i], $tableName, $query);
            }
        }
        $res = array();
        if (!empty($query)) {
            try {
                if ($stmt = $this->modx->prepare($query)) {
                    if (!$stmt->execute()) throw new PDOException($this->modx->lexicon('dbadmin_sql_executed_failed'));
                    $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
            } catch (PDOException $e) {
                return $this->failure($e->getMessage());
            }
        }
        return $this->success('', print_r($res,1), $select, count($res));
    }

    public function success($msg, $data, $select, $number){
        return $this->modx->toJSON(array('success'=>true,'message'=>$msg,'data'=>$data,'select'=>$select, 'number'=>$number));
    }
}
return 'dbAdminExecuteQueryProcessor';